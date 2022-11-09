# AgenDAV - CalDAV web client

[![Maintenance mode](https://img.shields.io/badge/maintenance_mode-%F0%9F%9A%A7-grey.svg?labelColor=orange)](https://github.com/agendav/agendav/blob/develop/CONTRIBUTING.md)
[![Build Status](https://travis-ci.org/agendav/agendav.svg?branch=develop)](https://travis-ci.org/agendav/agendav)
[![Docs](https://readthedocs.org/projects/agendav/badge/?version=develop)](https://agendav.readthedocs.io/en/develop/)
[![Made With](https://img.shields.io/badge/made_with-php-blue)](https://gitlab.com/pixelbrackets/acme-app#requirements)
[![License](https://img.shields.io/badge/license-gpl--3.0-blue.svg)](https://spdx.org/licenses/GPL-3.0.html)
[![Contribution](https://img.shields.io/badge/contributions_welcome-%F0%9F%94%B0-brightgreen.svg?labelColor=brightgreen)](https://github.com/agendav/agendav/blob/develop/CONTRIBUTING.md)

AgenDAV is a CalDAV web client which features an AJAX interface to allow
users to manage their own calendars and shared ones.

![Screenshot](./docs/screenshot.png)

## Requirements

AgenDAV requires:

- A CalDAV server like [Baïkal](http://baikal-server.com/), [DAViCal](http://www.davical.org/), [Radicale](https://radicale.org/tutorial/), etc
- A web server
- PHP >= 5.5.9
- PHP ctype extension
- PHP mbstring extension
- PHP mcrypt extension
- PHP cURL extension
- A database supported by [Doctrine DBAL](https://www.doctrine-project.org/projects/doctrine-dbal/en/2.12/reference/configuration.html#configuration) like MySQL, PostgreSQL, SQLite
- Optional: nodejs & npm to build assets (releases include a build)

## Documentation

https://agendav.readthedocs.io/

## Installation

See [installation guide](https://agendav.readthedocs.io/en/latest/admin/installation/)

### Docker Image

Agendav offers no official Docker image. However, you may use unofficial docker images provided by community instead, for example https://ghcr.io/nagimov/agendav-docker.

## Source

https://github.com/agendav/agendav

## License

GNU GENERAL PUBLIC LICENSE Version 3

## Changelog

See [CHANGELOG.md](./CHANGELOG.md)

## Maintenance Mode

AgenDAV is in maintenance mode currently. This means that the maintainers
choose to prioritize stability and compatibility over new features for now.

- There is no active development & new major features are not planned
- New features may be added by PRs however
  - New features may be proposed in issues tickets, send as Pull Requests,
    and the maintainers will review and presumably merge them
- *PRs for bugfixes are welcome* and will be reviewed & merged
- PRs to keep the software compatible with new PHP versions or the like
  are welcome and will be reviewed & merged
- Critical security concerns will be addressed

## Contribution

[Contributions](./CONTRIBUTING.md) are welcome!
