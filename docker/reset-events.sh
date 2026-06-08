#!/usr/bin/env bash
# Resets example calendar events in the local development stack.
#
# Usage:
#   bash docker/reset-events.sh
#
# Requires the docker-compose stack to be running (docker compose up -d).
# Safe to re-run: events are PUT by fixed UID and overwrite existing ones.
# If the Baikal test user does not exist yet it will be created automatically.

set -euo pipefail

cd "$(dirname "$0")/.."

# ----- preflight -----
for bin in docker curl python3 sha256sum md5sum; do
  command -v "$bin" >/dev/null 2>&1 || { echo "missing dependency: $bin" >&2; exit 2; }
done
docker compose ps --services --filter status=running 2>/dev/null | grep -q "^web$" \
  || { echo "Stack not running. Start it with: docker compose up -d" >&2; exit 2; }

APP_ENV=$(docker compose exec -T web printenv AGENDAV_ENVIRONMENT 2>/dev/null | tr -d '\r')
[[ "$APP_ENV" == "dev" ]] \
  || { echo "ERROR: AGENDAV_ENVIRONMENT is '$APP_ENV', not 'dev'. Refusing to run against a non-dev stack." >&2; exit 2; }

if [[ ! -f config/settings.php ]]; then
  echo "==> copying tests/fixtures/settings.docker.php -> config/settings.php"
  cp tests/fixtures/settings.docker.php config/settings.php
else
  echo "==> using existing config/settings.php"
fi

CAL_BASE="http://127.0.0.1:8081/dav.php/calendars/test/default"

# ----- ensure Baikal test user exists -----
STATUS=$(curl -s -o /dev/null -w '%{http_code}' -u test:test \
  -X PROPFIND -H 'Depth: 0' "$CAL_BASE/" 2>/dev/null || echo 000)

if [[ "$STATUS" != "207" ]]; then
  echo "==> Baikal test user not found (HTTP $STATUS) - creating"
  ADMIN_PASS_HASH=$(printf 'admin:BaikalDAV:admin' | sha256sum | awk '{print $1}')
  TEST_DIGEST=$(printf 'test:BaikalDAV:test' | md5sum | awk '{print $1}')
  ENC_KEY=$(python3 -c 'import secrets; print(secrets.token_hex(16))')
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
  echo "==> Baikal test user created"
fi

# ----- run AgenDAV migrations  -----
docker compose exec -T web php /app/bin/agendavcli migrations:migrate --no-interaction >/dev/null 2>&1

# ----- clear existing events and extra calendars -----
echo "==> clearing existing events and extra calendars"
docker compose exec -T baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite \
  "DELETE FROM calendarobjects WHERE calendarid IN (SELECT calendarid FROM calendarinstances WHERE principaluri='principals/test');
  DELETE FROM calendarinstances WHERE principaluri='principals/test' AND uri != 'default';
  DELETE FROM calendars WHERE id NOT IN (SELECT calendarid FROM calendarinstances);"

# ----- PUT events via Python -----
echo "==> seeding events"

python3 << 'PY'
import urllib.request, urllib.error, base64, sys
from datetime import date, timedelta, datetime, timezone

today = date.today()
tom   = today + timedelta(1)
two_ago     = today - timedelta(2)
four_ahead  = today + timedelta(5)
next_mon    = today + timedelta(days=(7 - today.weekday()) % 7 or 7)
next_sat    = today + timedelta(days=(5 - today.weekday()) % 7 or 7)
next_sun    = next_sat + timedelta(1)

dtstamp = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%SZ')
crlf    = '\r\n'

def fmt(d):
  return d.strftime('%Y%m%d')

def fmtt(d, h, m=0):
  return f':{fmt(d)}T{h:02d}{m:02d}00Z'

def fmtd(d):
  return f';VALUE=DATE:{fmt(d)}'

def make_ics(uid, summary, dtstart, dtend, extra=None):
  # dtstart/dtend are full property suffixes, e.g. ':20260601T100000Z'
  # or ';VALUE=DATE:20260601' for all-day events.
  lines = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//AgenDAV//seed-events//EN',
    'BEGIN:VEVENT',
    f'UID:{uid}@agendav-dev',
    f'DTSTAMP:{dtstamp}',
    f'DTSTART{dtstart}',
    f'DTEND{dtend}',
    f'SUMMARY:{summary}',
  ]
  if extra:
    lines.extend(extra)
  lines += ['END:VEVENT', 'END:VCALENDAR', '']
  return (crlf.join(lines)).encode('utf-8')

def put(uid, content):
  url = f'http://127.0.0.1:8081/dav.php/calendars/test/default/{uid}.ics'
  req = urllib.request.Request(url, data=content, method='PUT')
  req.add_header('Content-Type', 'text/calendar; charset=utf-8')
  req.add_header('Authorization', 'Basic ' + base64.b64encode(b'test:test').decode())
  try:
    urllib.request.urlopen(req)
    print(f'  PUT {uid}')
  except urllib.error.HTTPError as e:
    print(f'  FAIL {uid} [HTTP {e.code}]', file=sys.stderr)

# 1-hour meeting
put('seed-1h', make_ics(
  'seed-1h', '1-hour team standup',
  fmtt(today, 10), fmtt(today, 11),
))

# 4-hour workshop
put('seed-4h', make_ics(
  'seed-4h', '4-hour workshop',
  fmtt(today, 14), fmtt(today, 18),
))

# Concurrent event (overlaps the 1-hour meeting) - red to mark urgency
put('seed-concurrent', make_ics(
  'seed-concurrent', 'Client call (concurrent)',
  fmtt(today, 10, 30), fmtt(today, 11, 30),
  extra=['COLOR:#FFCDD2'],
))

# All-day single event
put('seed-allday', make_ics(
  'seed-allday', 'All-day: team offsite',
  fmtd(today), fmtd(tom),
))

# Overnight event (today 20:00 - tomorrow 09:00)
put('seed-overnight', make_ics(
  'seed-overnight', 'Overnight server maintenance',
  fmtt(today, 20), fmtt(tom, 9),
  extra=['COLOR:#D7CCC8'],
))

# Multi-day all-day event
put('seed-multiday', make_ics(
  'seed-multiday', 'Multi-day conference',
  fmtd(two_ago), fmtd(four_ahead),
  extra=['COLOR:#C5CAE9'],
))

# Recurring weekly meeting (next Monday, repeating)
put('seed-recurring', make_ics(
  'seed-recurring', 'Weekly sync (recurring)',
  fmtt(next_mon, 9), fmtt(next_mon, 9, 30),
  extra=['RRULE:FREQ=WEEKLY;BYDAY=MO'],
))

# Event with location (tomorrow)
put('seed-location', make_ics(
  'seed-location', 'Sprint review',
  fmtt(tom, 9), fmtt(tom, 10),
  extra=['LOCATION:Conference Room A'],
))

# Weekend all-day event
put('seed-weekend', make_ics(
  'seed-weekend', 'Weekend getaway',
  fmtd(next_sat), fmtd(next_sun + timedelta(1)),
))

PY

# ----- seed ICS subscription -----
echo "==> seeding ICS subscription"
docker compose exec -T db mysql -uagendav -pagendav agendav 2>/dev/null <<'SQL'
DELETE FROM subscriptions WHERE calendar='http://localhost/test-calendar.ics';
INSERT INTO subscriptions (owner, calendar, options) VALUES (
  '/dav.php/principals/test/',
  'http://localhost/test-calendar.ics',
  '{"{DAV:}displayname":"Public test calendar","{http://apple.com/ns/ical/}calendar-color":"#4CAF50"}'
);
SQL

echo "==> done. Open http://localhost:8080 and log in as test/test"
