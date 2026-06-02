# Contributing guidelines for AgenDAV

Great to have you here. Here are a few ways you can help make this project better!

**Please note that this project was put into [maintenance mode](./README.md#maintenance-mode)**

## Issues

### Bugs

When reporting a bug make sure you specify the following data:

* Your AgenDAV version
* What CalDAV server you are using
* A brief description of the issue
* Step by step guide of what you did, screenshots are welcome
* *What you were expecting to happen and what actualy happened*
* Any logs that could help to identify the cause

**You are encouraged to send fixes for bug reports as Pull Request**

### Features

Please explain how this feature could help the project and what is required to
implement it.

## Translation

AgenDAV uses [Transifex](https://www.transifex.com/adobo/agendav/) for translations.

Have a look at [Transifex documentation](http://docs.transifex.com/) for more information about
adding a new language or upadting an existing translation.

## Documentation

Documentation is automatically generated and placed on https://agendav.readthedocs.io/.
Updating the documentation requires some [Sphinx](http://sphinx-doc.org/) knowledge.

Have a look at the `docs/` directory.

## Contributing code

There are some facts that will help you when contributing code to AgenDAV:

- A docker development instance is provided with AgenDAV
  and Baikal as CalDAV server. Run `docker compose up`
  from the repository root to start it up.
  - A `web-builder` service runs `npm install`, `composer install`,
    and the asset build on first start, then exits.
  - AgenDAV is then served at <http://localhost:8080> and Baikal at
    <http://localhost:8081>.
  - The example calendar is named `default`, and the calendar URL is
    `http://localhost:8081/cal.php/calendars/admin/default/`
  - The user for AgendDav is `test` and the password is `test`.
  - The credentials for the Baikal server are `admin` / `admin`.
- Run `bash docker/seed-events.sh` to populate the calendar with example
  events (1-hour, 4-hour, concurrent, all-day, overnight, multi-day,…).
  This removes any existing events in the container!
- `bash docker/smoke-test.sh` brings the stack up and runs an
  end-to-end regression check covering login, calendar/event CRUD against
  Baikal, preferences persistence, CSRF, 404 handling, and HTTP Basic auth.
  Run it before submitting a PR.
- Use the projects base PHP version, stored in the [.php-version](https://packagist.org/packages/webit-de/php-version-pickup)
  file (or run `php-version-pickup use`)
- AgenDAV includes unit tests as well, run via `./vendor/bin/phpunit tests`
- AgenDAV repository uses [nvie's git flow](http://nvie.com/posts/a-successful-git-branching-model/)

### Pull requests

* Please open an issue on GitHub first and describe your desired change before
  starting to work on a PR
* The target branch for Pull Requests is the `development` branch
* Make your pull requests as small as possible, one topic per branch
* Make sure to add tests for your feature, and update the documentation if
  needed
* Please explain your changes in a short, readable commit message

## Coding Guidelines

This project has adopted the
[PSR-2 Coding Style Guide](https://www.php-fig.org/psr/psr-2/) for PHP Code.

## Release cycle

This project has adopted [SemVer 2 Versioning](https://semver.org/).

New commits are composed in branch `development` until a new version is
released.

The `main` branch always refers to the latest version available.

All notable changes made between each release are documented in the
[Changelog](./CHANGELOG.md).

### New Releases

Manual release steps done by project maintainers.

- Checkout latest »development« branch and rebase against »main«
- Use the projects base PHP version, stored in the
  [.php-version](https://packagist.org/packages/webit-de/php-version-pickup)
  file (or run `php-version-pickup use`)
- Create test build runing `npm install && npm run-script build`
- Run code quality tools
- Compare the »development« branch to »main«
  - Add a list of noteworthy features and bugfixes to CHANGELOG.md
  - Describe breaking changes in CHANGELOG.md
  - Describe changes in `docs/source/releasenotes.rst` as well
- Change the version, using semantic versioning, in these files:
  - `docs/source/conf.py`
  - `package.json`
  - `src/Version.php`
- Run `bash docker/smoke-test.sh` to verify the stack
- Create a release commit
  ([example commit](https://github.com/agendav/agendav/commit/7d2f1bba00deb090943f14bf9c47c4a6ac4d1387))
- Merge »development« branch to »main«
- Tag the »main« branch with the new version
- Push branch and tag
- Update the documentation & website
- Add release download file to release page ([example file](https://github.com/agendav/agendav/releases/tag/2.2.0))
  - Clone the git repository using
    `git clone -b <version> https://github.com/agendav/agendav.git agendav-<version>`
  - Run `npm install && npm run-script dist`
    - Creates build files in `public/dist/css/`, `public/dist/js/`
      and `vendor/`
    - Removes `.git`, `node_modules`
  - Zip directory `tar -czf ../agendav-<version>.tar.gz ../agendav-<version>`
- Sip a tea
