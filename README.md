# AgenDAV - CalDAV web client

[![Build Status](https://travis-ci.org/agendav/agendav.svg?branch=develop)](https://travis-ci.org/agendav/agendav)
[![Develop docs](https://readthedocs.org/projects/agendav/badge/?version=develop)](http://agendav.readthedocs.org/en/develop/)
[![Stable docs](https://readthedocs.org/projects/agendav/badge/?version=latest)](http://agendav.readthedocs.org/en/latest/)
[![Made With](https://img.shields.io/badge/made_with-php-blue)](https://gitlab.com/pixelbrackets/acme-app#requirements)
[![License](https://img.shields.io/badge/license-gpl--3.0-blue.svg)](https://spdx.org/licenses/GPL-3.0.html)
[![Contribution](https://img.shields.io/badge/contributions_welcome-%F0%9F%94%B0-brightgreen.svg?labelColor=brightgreen&style=flat-square)](https://github.com/agendav/agendav/blob/develop/CONTRIBUTING.md)

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

## Installation

See [installation guide](https://agendav.readthedocs.io/en/develop/admin/installation/)

## Source

https://github.com/agendav/agendav

## License

GNU GENERAL PUBLIC LICENSE Version 3

## Changelog

See [CHANGELOG.md](./CHANGELOG.md)

## Contribution

[Contributions](./CONTRIBUTING.md) are welcome!
