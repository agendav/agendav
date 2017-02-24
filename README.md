AgenDAV - CalDAV web client
===========================

[![Build Status](https://travis-ci.org/agendav/agendav.svg?branch=develop)](https://travis-ci.org/agendav/agendav)
[![Develop docs](https://readthedocs.org/projects/agendav/badge/?version=develop)](http://agendav.readthedocs.org/en/develop/)
[![Stable docs](https://readthedocs.org/projects/agendav/badge/?version=latest)](http://agendav.readthedocs.org/en/latest/)

AgenDAV is a CalDAV web client which features an AJAX interface to allow
users to manage their own calendars and shared ones. It's released under
the GPLv3 license.

Requisites
----------

AgenDAV requires:

* A CalDAV server (such as [Baïkal](http://baikal-server.com/), [DAViCal](http://www.davical.org/), etc)
* A web server
* PHP >= 5.5.9
* PHP ctype extension
* PHP mbstring extension
* PHP mcrypt extension
* PHP cURL extension
* Most DB backends: MySQL, PostgreSQL, SQLite. Look for supported DB drivers at http://doctrine-dbal.readthedocs.org/en/latest/reference/configuration.html#driver

