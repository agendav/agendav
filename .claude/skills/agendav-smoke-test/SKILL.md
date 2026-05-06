---
name: agendav-smoke-test
description: Run the AgenDAV docker-compose stack smoke test (login, calendar/event CRUD, preferences, CSRF, 404, HTTP Basic). Use when the user asks to "smoke test", "rerun the smoke test", "verify the stack", or wants end-to-end validation of agendav after a backend change. The skill drives a checked-in bash script — do not roll your own curl loop.
---

# AgenDAV smoke test

A single bash script (`docker/smoke-test.sh`) brings up a docker-compose stack (mariadb + ckulka/baikal:nginx + custom php:8.5-apache), seeds Baikal with a `test/test` user, applies migrations, and runs ~23 assertions covering login (form + HTTP Basic), calendar/event CRUD round-tripped through Baikal, preferences persistence, CSRF rejection, 404, and logout.

## How to invoke

From the project root (`web/composer.json` must exist). On first run the script
auto-generates `web/config/settings.php` with random `csrf.secret` and
`session.encryption.key` values — the file is gitignored, so fresh clones
bootstrap themselves. Delete the file to force-regenerate (e.g. to rotate
secrets); existing sessions get invalidated.

```bash
bash docker/smoke-test.sh           # idempotent; reuses stack + Baikal seed if already in place
bash docker/smoke-test.sh --reset   # docker compose down -v + full rebuild + re-seed
bash docker/smoke-test.sh --down    # tear the stack down and exit
```

Default behavior is the right choice in almost every case. Use `--reset` only when:
- The user explicitly asks for a clean rebuild
- A previous run failed during the seed phase (mid-seed Baikal is not idempotent)
- You suspect stale schema (e.g., after editing a Doctrine migration)

The script exits 0 if all assertions PASS, 1 if any fail. Failed assertions are printed at the end with the relevant log excerpt from `web/var/log/<today>.log`.

## What to report back

After the run:
- The PASS / FAIL counts
- For any failures: the assertion name and the failure reason from the script's output
- If the failure is clearly logic-level (e.g., "POST /events/save: ...result:ERROR..."), pull the matching stack trace out of `web/var/log/<today>.log` and surface the exception class + file:line
- Don't paste the entire script output unless asked — keep it tight

## Constraints

- The script targets **dev mode** (`AGENDAV_ENVIRONMENT=dev`). The prod-mode error-template path is exercised by a single dedicated assertion via `docker compose exec` with `AGENDAV_ENVIRONMENT=prod`, so we don't need to flip the stack.
- Don't add curl loops outside the script. If a new assertion is needed, add it to `docker/smoke-test.sh` so future runs benefit.
- Side-effect verification (event in baikal sqlite, prefs in mariadb) is intentional — it's how we caught the `PlainSerializer` signature bug in the original by-hand run. Preserve that pattern for new assertions.

## Test fixture

- AgenDAV: http://localhost:8080
- Baikal admin: http://localhost:8081/admin/  (admin / admin)
- Test user: `test` / `test` (Baikal principal `principals/test`, default calendar `default`)
- DB: `docker compose exec db mysql -uagendav -pagendav agendav`
- Baikal sqlite: `docker compose exec baikal sqlite3 /var/www/baikal/Specific/db/db.sqlite`
