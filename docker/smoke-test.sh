#!/usr/bin/env bash
# AgenDAV docker-compose smoke test.
#
# Usage:
#   bash docker/smoke-test.sh           # idempotent: bring up if needed, seed if needed, run assertions
#   bash docker/smoke-test.sh --reset   # docker compose down -v, then full rebuild + re-seed
#   bash docker/smoke-test.sh --down    # tear the stack down and exit
#   bash docker/smoke-test.sh --help    # this help
#
# Exit code: 0 if all assertions PASS, 1 otherwise.

set -euo pipefail

cd "$(dirname "$0")/.."

# ----- args -----
RESET=0
DOWN_ONLY=0
for arg in "$@"; do
  case "$arg" in
    --reset)   RESET=1 ;;
    --down)    DOWN_ONLY=1 ;;
    --keep-up) ;;  # no-op, default behavior
    --help|-h)
      sed -n '2,11p' "$0" | sed 's/^# \?//'
      exit 0 ;;
    *) echo "Unknown arg: $arg" >&2; exit 2 ;;
  esac
done

# ----- preflight -----
for bin in docker curl sha256sum md5sum python3; do
  command -v "$bin" >/dev/null 2>&1 || { echo "missing dependency: $bin" >&2; exit 2; }
done
[[ -f docker-compose.yml ]] || { echo "docker-compose.yml not found in $(pwd)" >&2; exit 2; }

# Bootstrap a dev-only config/settings.php on first run. The file is
# gitignored, so a fresh clone has no settings; the fixture provides
# fixed dev-only values. Existing files are kept untouched.
if [[ ! -f config/settings.php ]]; then
  echo "==> copying tests/fixtures/settings.docker.php -> config/settings.php"
  cp tests/fixtures/settings.docker.php config/settings.php
else
  echo "==> using existing config/settings.php"
fi

# ----- helpers -----
PASSES=0
FAILS=0
FAIL_LINES=()
pass() { echo "  PASS  $1"; PASSES=$((PASSES+1)); }
fail() { echo "  FAIL  $1"; FAILS=$((FAILS+1)); FAIL_LINES+=("$1"); }

assert_status() {  # name url expected [extra_curl_args...]
  local name=$1 url=$2 expected=$3; shift 3
  local code
  code=$(curl -s -o /dev/null -w '%{http_code}' "$@" "$url" || echo 000)
  if [[ "$code" == "$expected" ]]; then pass "$name [$code]"; else fail "$name expected $expected got $code ($url)"; fi
}

assert_content_type() {  # name url expected_prefix
  # Catches the "missing dist/* file falls through .htaccess to Slim's 404
  # HTML page" regression: any text/html response for a static asset path
  # means the file isn't on disk (asset build never ran).
  local name=$1 url=$2 expected=$3
  local ct
  ct=$(curl -sI "$url" | awk -F': ' 'tolower($1)=="content-type"{sub(/[\r;].*/,"",$2);print $2;exit}')
  if [[ "$ct" == "$expected"* ]]; then pass "$name [$ct]"; else fail "$name expected ${expected}* got '${ct}' ($url)"; fi
}

# ----- teardown -----
teardown() {
  echo "==> docker compose down -v"
  docker compose down -v >/dev/null 2>&1 || true
}

if [[ $DOWN_ONLY -eq 1 ]]; then teardown; exit 0; fi
if [[ $RESET -eq 1 ]]; then teardown; fi

# ----- bring-up -----
echo "==> docker compose up -d --build"
docker compose up -d --build >/dev/null

echo -n "==> waiting for db healthcheck"
for _ in $(seq 1 60); do
  health=$(docker compose ps --format json db 2>/dev/null | python3 -c 'import sys,json
for line in sys.stdin:
    line=line.strip()
    if line:
        print(json.loads(line).get("Health",""))' || true)
  [[ "$health" == "healthy" ]] && { echo " ok"; break; }
  echo -n "."; sleep 1
done
[[ "${health:-}" == "healthy" ]] || { echo " TIMEOUT"; exit 1; }

echo -n "==> waiting for web on :8080"
for _ in $(seq 1 30); do
  code=$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8080/login || echo 000)
  [[ "$code" =~ ^[2345] ]] && { echo " ok ($code)"; break; }
  echo -n "."; sleep 1
done
[[ "${code:-000}" =~ ^[2345] ]] || { echo " TIMEOUT"; exit 1; }

APP_ENV=$(docker compose exec -T web printenv AGENDAV_ENVIRONMENT 2>/dev/null | tr -d '\r')
[[ "$APP_ENV" == "dev" ]] \
  || { echo "ERROR: AGENDAV_ENVIRONMENT is '$APP_ENV', not 'dev'. Refusing to run destructive tests against a non-dev stack." >&2; exit 1; }

# ----- ensure host-side writable dirs (container runs www-data) -----
chmod -R a+rwX var/log var/cache 2>/dev/null || true

# ----- migrate -----
echo "==> agendavcli migrations:migrate"
# Three legacy migrations only apply when upgrading from AgenDAV 1.x and call
# skipIf() — they never get inserted into schema_versions on a fresh install,
# so on every subsequent run Doctrine Migrations 3 sees them as pending and
# `all_or_nothing: true` aborts a batch of all-skipped migrations with
# "Transaction commit failed because the transaction has been marked for
# rollback only". Pre-mark them as executed (idempotent: --add returns
# non-zero if already present, hence || true).
for v in Version20140812113548 Version20140812200547 Version20140812203419; do
  docker compose exec -T web php /app/bin/agendavcli migrations:version "AgenDAV\\DB\\Migrations\\$v" --add --no-interaction >/dev/null 2>&1 || true
done
docker compose exec -T web php /app/bin/agendavcli migrations:migrate --no-interaction >/dev/null

# ----- seed Baikal (idempotent: skip if config already present) -----
if docker compose exec -T baikal test -s /var/www/baikal/config/baikal.yaml 2>/dev/null; then
  echo "==> baikal already seeded, skipping"
else
  echo "==> seeding baikal"
  ADMIN_PASS_HASH=$(printf 'admin:BaikalDAV:admin' | sha256sum | awk '{print $1}')
  TEST_DIGEST=$(printf 'test:BaikalDAV:test' | md5sum | awk '{print $1}')
  ENC_KEY=$(printf '%s%d' "$(date +%s%N)" "$RANDOM" | md5sum | awk '{print $1}')
  TMP_YAML=$(mktemp)
  cat > "$TMP_YAML" <<YAML
system:
    configured_version: '0.10.1'
    timezone: 'Europe/Paris'
    card_enabled: true
    cal_enabled: true
    dav_auth_type: 'Basic'
    admin_passwordhash: '$ADMIN_PASS_HASH'
    failed_access_message: 'user %u authentication failure for Baikal'
    auth_realm: 'BaikalDAV'
    base_uri: ''
    invite_from: 'noreply@baikal'

database:
    backend: 'sqlite'
    sqlite_file: '/var/www/baikal/Specific/db/db.sqlite'
    mysql_host: ''
    mysql_dbname: ''
    mysql_username: ''
    mysql_password: ''
    encryption_key: '$ENC_KEY'
    pgsql_host: ''
    pgsql_dbname: ''
    pgsql_username: ''
    pgsql_password: ''
YAML
  docker compose cp "$TMP_YAML" baikal:/var/www/baikal/config/baikal.yaml
  rm -f "$TMP_YAML"
  docker compose exec -T baikal sh -c '
    touch /var/www/baikal/Specific/INSTALL_DISABLED
    touch /var/www/baikal/Specific/db/db.sqlite
    cat /var/www/baikal/Core/Resources/Db/SQLite/db.sql | sqlite3 /var/www/baikal/Specific/db/db.sqlite
    chown -R nginx:nginx /var/www/baikal/Specific /var/www/baikal/config
    chmod 664 /var/www/baikal/Specific/db/db.sqlite
  '
  docker compose exec -T baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite "
    INSERT INTO principals (uri, email, displayname) VALUES ('principals/test', 'test@example.org', 'Test User');
    INSERT INTO users (username, digesta1) VALUES ('test', '$TEST_DIGEST');
    INSERT INTO calendarinstances (principaluri, uri, displayname, description, calendarid, transparent, share_href, share_invitestatus, access)
      VALUES ('principals/test', 'default', 'Default calendar', 'Default Baikal calendar', 1, 0, NULL, 2, 1);
    INSERT INTO calendars (synctoken, components) VALUES (1, 'VEVENT,VTODO');
  "
fi

# ----- smoke assertions -----
echo
echo "==> running assertions"

# Clean any stale events or calendars left by an interrupted previous run
docker compose exec -T baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite \
  "DELETE FROM calendarobjects; DELETE FROM calendarchanges;
   DELETE FROM calendarinstances WHERE uri != 'default';
   DELETE FROM calendars WHERE id NOT IN (SELECT calendarid FROM calendarinstances);" \
  >/dev/null 2>&1 || true
docker compose exec -T db mysql -uagendav -pagendav agendav \
  -e "DELETE FROM subscriptions;" >/dev/null 2>&1 || true

JAR=$(mktemp)
JAR_FRESH=$(mktemp)
trap 'rm -f "$JAR" "$JAR_FRESH"' EXIT

# 0. front-end bundle is actually on disk (and not falling through .htaccess
#    to Slim's 404 HTML page). Without these, the calendar UI shows a
#    permanent loading spinner because the JS never loads.
assert_content_type "GET /dist/css/agendav.css" http://localhost:8080/dist/css/agendav.css text/css
assert_content_type "GET /dist/js/agendav.min.js" http://localhost:8080/dist/js/agendav.min.js text/javascript

# 1. login form GET
assert_status "GET /login" http://localhost:8080/login 200 -c "$JAR"
LOGIN_HTML=$(curl -s -b "$JAR" -c "$JAR" http://localhost:8080/login)
T_LOGIN=$(echo "$LOGIN_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
[[ -n "$T_LOGIN" ]] && pass "login form contains _token" || fail "login form missing _token"

# 2. login form POST
LOGIN_CODE=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST \
  --data-urlencode "_token=$T_LOGIN" \
  --data "user=test&password=test&login=Log+in" \
  http://localhost:8080/login)
[[ "$LOGIN_CODE" == "302" ]] && pass "POST /login [302]" || fail "POST /login expected 302 got $LOGIN_CODE"

# 3. calendar UI
assert_status "GET / (calendar UI)" http://localhost:8080/ 200 -b "$JAR"

# 4. /jssettings
JSCT=$(curl -s -o /dev/null -w '%{content_type}' -b "$JAR" http://localhost:8080/jssettings)
[[ "$JSCT" == text/javascript* ]] && pass "GET /jssettings ct=$JSCT" || fail "GET /jssettings ct=$JSCT"

# 5. /calendars
CAL_LIST=$(curl -s -b "$JAR" http://localhost:8080/calendars)
echo "$CAL_LIST" | grep -q '"displayname":"Default calendar"' && pass "GET /calendars contains default" || fail "GET /calendars missing default ($CAL_LIST)"

# 6. POST /calendars (create)
PREF_HTML=$(curl -s -b "$JAR" -c "$JAR" http://localhost:8080/preferences)
T=$(echo "$PREF_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "displayname=smoke-test cal" \
  --data-urlencode "calendar_color=FF0000" \
  http://localhost:8080/calendars)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /calendars create" || fail "POST /calendars create: $RESP"

# 7. /calendars now lists 2
CAL_COUNT=$(curl -s -b "$JAR" http://localhost:8080/calendars | python3 -c 'import sys,json;print(len(json.load(sys.stdin)["data"]))')
[[ "$CAL_COUNT" == "2" ]] && pass "calendars count == 2" || fail "calendars count == $CAL_COUNT"

# locate the just-created calendar (URL is stable across rename — reused for update + delete)
NEW_URL=$(curl -s -b "$JAR" http://localhost:8080/calendars | python3 -c 'import sys,json;cs=json.load(sys.stdin)["data"];print([c for c in cs if c["displayname"]=="smoke-test cal"][0]["url"])')

# 7b. POST /calendars/save (rename + recolor)
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=$NEW_URL" \
  --data-urlencode "displayname=smoke-test cal RENAMED" \
  --data-urlencode "calendar_color=00FF00" \
  http://localhost:8080/calendars/save)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /calendars/save (rename+recolor)" || fail "POST /calendars/save: $RESP"

# 7c. /calendars reflects the rename (new name present, old name gone)
CAL_LIST=$(curl -s -b "$JAR" http://localhost:8080/calendars)
if echo "$CAL_LIST" | python3 -c 'import sys,json;ns=[c["displayname"] for c in json.load(sys.stdin)["data"]];sys.exit(0 if ("smoke-test cal RENAMED" in ns and "smoke-test cal" not in ns) else 1)'; then
  pass "/calendars shows renamed cal (old name gone)"
else
  fail "/calendars rename not reflected: $CAL_LIST"
fi

# 8. POST /calendars/delete
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=$NEW_URL" \
  http://localhost:8080/calendars/delete)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /calendars/delete" || fail "POST /calendars/delete: $RESP"

# 8b. POST /calendars (subscribe to ICS)
ICS_URL='http://localhost/test-calendar.ics'
ICS_URL_ENC='http%3A%2F%2Flocalhost%2Ftest-calendar.ics'
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "is_subscribed=true" \
  --data-urlencode "displayname=Test ICS subscription" \
  --data-urlencode "calendar_color=#4CAF50" \
  --data-urlencode "url=$ICS_URL" \
  http://localhost:8080/calendars)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /calendars (subscribe)" || fail "POST /calendars subscribe: $RESP"

# 8c. /calendars lists the subscription
CALS=$(curl -s -b "$JAR" http://localhost:8080/calendars)
echo "$CALS" | python3 -c '
import sys, json
cs = json.load(sys.stdin)["data"]
match = [c for c in cs if c.get("is_subscribed") and c.get("displayname") == "Test ICS subscription"]
assert match, "subscription not found in: " + str(cs)
' && pass "GET /calendars contains subscription" || fail "GET /calendars missing subscription"

# 8d. GET /events for subscribed calendar returns the ICS events
EVT_RESP=$(curl -s -b "$JAR" \
  "http://localhost:8080/events?calendar=${ICS_URL_ENC}&is_subscribed=true&timezone=UTC&start=2026-06-01&end=2026-08-01")
echo "$EVT_RESP" | python3 -c '
import sys, json
d = json.load(sys.stdin)
assert len(d) > 0, "expected events, got: " + str(d)
' && pass "GET /events (subscribed calendar)" || fail "GET /events subscribed: $EVT_RESP"

# 8e. POST /calendars/delete (subscription)
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "is_subscribed=true" \
  --data-urlencode "calendar=$ICS_URL" \
  http://localhost:8080/calendars/delete)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /calendars/delete (subscription)" || fail "POST /calendars/delete subscription: $RESP"

# 9. POST /events/save (create event; controller generates the uid)
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "summary=Smoke event" \
  --data "timezone=UTC" \
  --data "start=2026-05-15T09:00:00.000Z" \
  --data "end=2026-05-15T10:00:00.000Z" \
  --data "allday=false" \
  http://localhost:8080/events/save)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /events/save" || fail "POST /events/save: $RESP"

# 10. event lands in baikal sqlite
EVENT_IN_DB=$(docker compose exec -T baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite "SELECT COUNT(*) FROM calendarobjects WHERE componenttype='VEVENT';" | tr -d '\r')
[[ "$EVENT_IN_DB" -ge "1" ]] && pass "event in baikal sqlite ($EVENT_IN_DB rows)" || fail "no event in baikal sqlite"

# 11. GET /events lists the event
EVT_LIST=$(curl -s -b "$JAR" "http://localhost:8080/events?calendar=%2Fdav.php%2Fcalendars%2Ftest%2Fdefault%2F&timezone=UTC&start=2026-05-01&end=2026-06-01")
echo "$EVT_LIST" | grep -q '"title":"Smoke event"' && pass "GET /events contains created event" || fail "GET /events missing event ($EVT_LIST)"

EVT_UID=$(echo "$EVT_LIST" | python3 -c 'import sys,json;e=json.load(sys.stdin)[0];print(e["uid"])')
EVT_HREF=$(echo "$EVT_LIST" | python3 -c 'import sys,json;e=json.load(sys.stdin)[0];print(e["href"])')
EVT_ETAG=$(echo "$EVT_LIST" | python3 -c 'import sys,json;e=json.load(sys.stdin)[0];print(e["etag"])')

# 11b. POST /events/save in modify mode (edit summary + description in place)
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "original_calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "uid=$EVT_UID" \
  --data-urlencode "etag=$EVT_ETAG" \
  --data-urlencode "summary=Smoke event UPDATED" \
  --data-urlencode "description=smoke description added" \
  --data "timezone=UTC" \
  --data "start=2026-05-15T09:00:00.000Z" \
  --data "end=2026-05-15T10:00:00.000Z" \
  --data "allday=false" \
  http://localhost:8080/events/save)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /events/save (edit summary+description)" || fail "POST /events/save edit: $RESP"

# 11c. GET /events reflects the new title
EVT_LIST=$(curl -s -b "$JAR" "http://localhost:8080/events?calendar=%2Fdav.php%2Fcalendars%2Ftest%2Fdefault%2F&timezone=UTC&start=2026-05-01&end=2026-06-01")
echo "$EVT_LIST" | grep -q '"title":"Smoke event UPDATED"' && pass "GET /events shows updated title" || fail "GET /events missing updated title ($EVT_LIST)"

# 11d. baikal sqlite: ICS blob carries new SUMMARY + DESCRIPTION (proves edit reached CalDAV storage)
ICS=$(docker compose exec -T baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite "SELECT calendardata FROM calendarobjects WHERE componenttype='VEVENT' LIMIT 1;")
if echo "$ICS" | grep -q 'SUMMARY:Smoke event UPDATED' && echo "$ICS" | grep -q 'DESCRIPTION:smoke description added'; then
  pass "baikal ICS has updated SUMMARY + DESCRIPTION"
else
  fail "baikal ICS missing updates: $ICS"
fi

# 12. POST /events/drop
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "uid=$EVT_UID" \
  --data "timezone=UTC&delta=60&was_allday=false&allday=false" \
  http://localhost:8080/events/drop)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /events/drop" || fail "POST /events/drop: $RESP"

# 13. POST /events/resize
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "uid=$EVT_UID" \
  --data "timezone=UTC&delta=30" \
  http://localhost:8080/events/resize)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /events/resize" || fail "POST /events/resize: $RESP"

# 14. POST /events/delete (need fresh etag after resize)
EVT_LIST=$(curl -s -b "$JAR" "http://localhost:8080/events?calendar=%2Fdav.php%2Fcalendars%2Ftest%2Fdefault%2F&timezone=UTC&start=2026-05-01&end=2026-06-01")
EVT_ETAG=$(echo "$EVT_LIST" | python3 -c 'import sys,json;e=json.load(sys.stdin)[0];print(e["etag"])')
RESP=$(curl -s -b "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data-urlencode "calendar=/dav.php/calendars/test/default/" \
  --data-urlencode "uid=$EVT_UID" \
  --data-urlencode "href=$EVT_HREF" \
  --data-urlencode "etag=$EVT_ETAG" \
  http://localhost:8080/events/delete)
echo "$RESP" | grep -q '"result":"SUCCESS"' && pass "POST /events/delete" || fail "POST /events/delete: $RESP"

# 15. /preferences GET
assert_status "GET /preferences" http://localhost:8080/preferences 200 -b "$JAR"

# 16. POST /preferences (save) → 302
PREF_HTML=$(curl -s -b "$JAR" -c "$JAR" http://localhost:8080/preferences)
T=$(echo "$PREF_HTML" | grep -oP 'name="_token" value="\K[^"]+' | head -1)
PREF_CODE=$(curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" -X POST \
  --data-urlencode "_token=$T" \
  --data "language=fr&timezone=Europe%2FParis&default_calendar=%2Fdav.php%2Fcalendars%2Ftest%2Fdefault%2F&date_format=dmy&time_format=24&weekstart=1&show_week_nb=true&show_now_indicator=true&list_days=14&default_view=week" \
  http://localhost:8080/preferences)
[[ "$PREF_CODE" == "302" ]] && pass "POST /preferences [302]" || fail "POST /preferences expected 302 got $PREF_CODE"

# 17. DB check: prefs persisted
PREF_DB=$(docker compose exec -T db mysql -N -uagendav -pagendav agendav -e "SELECT options FROM prefs WHERE username='test';" 2>/dev/null)
echo "$PREF_DB" | grep -q '"language":"fr"' && pass "prefs persisted to DB" || fail "prefs not persisted: $PREF_DB"

# 18. /keepalive
assert_status "GET /keepalive" http://localhost:8080/keepalive 200 -b "$JAR"

# 19. /logout → 302 → /login
LOGOUT_LOC=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" -c "$JAR" http://localhost:8080/logout)
[[ "$LOGOUT_LOC" == */login ]] && pass "GET /logout redirects to /login" || fail "GET /logout redirected to: $LOGOUT_LOC"

# 20. logged-out /preferences → 302 → /login
LO_LOC=$(curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" http://localhost:8080/preferences)
[[ "$LO_LOC" == */login ]] && pass "logged-out /preferences redirects to /login" || fail "logged-out /preferences redirected to: $LO_LOC"

# 21. unknown route → 404
assert_status "GET /this-does-not-exist" http://localhost:8080/this-does-not-exist 404

# 21b. unknown route in PROD env renders the error template (regression guard:
# routing-mw throws before TwigGlobalsMiddleware runs, so the error handler
# must still see title/lang/favicon globals or layout.html crashes).
PROD_404=$(docker compose exec -T -e AGENDAV_ENVIRONMENT=prod web php -r '
$_SERVER["REQUEST_METHOD"]="GET"; $_SERVER["REQUEST_URI"]="/nope"; $_SERVER["HTTP_HOST"]="localhost";
$_SERVER["SERVER_NAME"]="localhost"; $_SERVER["SERVER_PORT"]="80"; $_SERVER["SCRIPT_NAME"]="/index.php";
$_SERVER["HTTPS"]=""; $_SERVER["QUERY_STRING"]="";
chdir("/app/public"); ob_start(); require "/app/public/index.php";
$body = ob_get_clean();
echo http_response_code() . "|" . strlen($body) . "|" . (strpos($body, "<title>") !== false ? "1" : "0");
' 2>/dev/null)
IFS='|' read -r STATUS LEN HAS_TITLE <<<"$PROD_404"
if [[ "$STATUS" == "404" && "${LEN:-0}" -gt 200 && "$HAS_TITLE" == "1" ]]; then
  pass "prod-mode /nope renders 404 template [$STATUS, ${LEN}B]"
else
  fail "prod-mode 404 template (got status=$STATUS len=$LEN has_title=$HAS_TITLE)"
fi

# 22. POST without _token → 401
assert_status "POST /preferences (no _token)" http://localhost:8080/preferences 401 -b "$JAR_FRESH" -X POST -d "x=1"

# 23. HTTP Basic auth → 200
assert_status "GET /calendars (HTTP Basic)" http://localhost:8080/calendars 200 -u test:test

# 24. shares.options PHP-serialized -> JSON data migration (Version20260524120000).
# DBAL 4 removed the 'array' type, so Share::$options is now 'json'. This drives
# the migration's up() against real MySQL: seed a legacy serialized row, then
# down()+up() the migration (it already ran on the fresh DB) and assert the row
# is now JSON. Idempotent: the test row is deleted up-front and cleaned up after.
SHARE_MIG='AgenDAV\DB\Migrations\Version20260524120000'
docker compose exec -T db mysql -uagendav -pagendav agendav <<'SQL' 2>/dev/null
DELETE FROM shares WHERE owner='smoke-owner';
INSERT INTO shares (owner, calendar, `with`, options, rw) VALUES ('smoke-owner','smoke-cal','smoke-with','a:1:{s:5:"color";s:7:"#ff0000";}', 1);
SQL
docker compose exec -T web php /app/agendavcli migrations:execute "$SHARE_MIG" --down --no-interaction >/dev/null 2>&1 || true
docker compose exec -T web php /app/agendavcli migrations:execute "$SHARE_MIG" --up --no-interaction >/dev/null 2>&1
SHARE_OPTS=$(docker compose exec -T db mysql -N -uagendav -pagendav agendav -e "SELECT options FROM shares WHERE owner='smoke-owner';" 2>/dev/null | tr -d '\r')
if echo "$SHARE_OPTS" | grep -q '{"color":"#ff0000"}'; then
  pass "shares.options serialized->JSON migration ($SHARE_OPTS)"
else
  fail "shares.options not converted to JSON: got '$SHARE_OPTS'"
fi
docker compose exec -T db mysql -uagendav -pagendav agendav -e "DELETE FROM shares WHERE owner='smoke-owner';" 2>/dev/null

# ----- summary -----
echo
echo "================================================="
echo "  PASS: $PASSES   FAIL: $FAILS"
echo "================================================="
if [[ $FAILS -gt 0 ]]; then
  echo "Failed assertions:"
  for line in "${FAIL_LINES[@]}"; do echo "  - $line"; done
  echo
  echo "Last 20 log lines (var/log/$(date +%F).log):"
  tail -20 "var/log/$(date +%F).log" 2>/dev/null || echo "  (no log file)"
  exit 1
fi
echo "All assertions passed."
exit 0
