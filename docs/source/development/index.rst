Development
===========

Please, read this section if you want to contribute to AgenDAV with code. You can even use this
information to confirm a bug.

Local environment
-----------------

Docker Compose (recommended)
****************************

The repository ships a ``docker-compose.yml`` that brings up the full stack:

* ``web`` — Apache + PHP 8.5 (built from ``docker/agendav/Dockerfile``), serving AgenDAV from
  ``public/`` with the source tree bind-mounted at ``/app`` so edits are picked up live
* ``db`` — MariaDB 10.11, pre-provisioned with database ``agendav`` and user
  ``agendav``/``agendav``
* ``baikal`` — a CalDAV server (``ckulka/baikal:nginx``) with persistent ``baikal-config`` and
  ``baikal-data`` volumes

Requirements: Docker Engine and the Compose v2 plugin.

Bring the stack up::

   $ docker compose up -d

Once the containers report healthy, AgenDAV is reachable at http://localhost:8080/ and the
Baikal admin UI at http://localhost:8081/.

On the first boot, Baikal is empty. Either:

* finish the Baikal install wizard at http://localhost:8081/ (admin password ``admin``), add a
  user with a calendar, then log in to AgenDAV with those credentials; or
* run the smoke-test script described below, which seeds a ``test/test`` user with a default
  calendar.

The ``AGENDAV_ENVIRONMENT=dev`` environment variable is already set on the ``web`` service, so
AgenDAV runs against ``web/config/dev.php`` (Twig cache disabled, debug on).

Stop and tear down::

   $ docker compose down            # stop containers, keep volumes
   $ docker compose down -v         # also drop the Baikal/MariaDB volumes

Example data
************

To seed the calendar with representative events (1-hour, 4-hour, concurrent, all-day,
overnight, multi-day) run::

   $ bash docker/reset-events.sh

This removes any existing events in the container.

Validation
----------

A scripted smoke test lives at ``docker/smoke-test.sh``. It runs against the Compose stack and
exercises login (form + HTTP Basic), calendar/event CRUD through Baikal, preferences
persistence, CSRF rejection, and the 404 path::

   $ bash docker/smoke-test.sh           # idempotent; reuses an existing stack
   $ bash docker/smoke-test.sh --reset   # rebuild and re-seed from scratch
   $ bash docker/smoke-test.sh --down    # tear the stack down

Exit code 0 means every assertion passed; failed assertions print to stdout and are also
written to ``var/log/<today>.log``.

Unit tests
----------

Run the full test suite from the ``tests/`` directory::

   $ cd tests && /usr/bin/php8.5 ../vendor/bin/phpunit

To run a single test file::

   $ cd tests && /usr/bin/php8.5 ../vendor/bin/phpunit AgenDAV/SomeTest.php

Documentation
-------------

Documentation is written in `reStructuredText <https://www.sphinx-doc.org/en/master/usage/restructuredtext/basics.html>`_
and built with `Sphinx <https://www.sphinx-doc.org/>`_. Source files live in ``docs/source/``.
The built documentation is published automatically at https://agendav.readthedocs.io/.

To build the docs locally, install Sphinx and run::

   $ cd docs && make html

The output is written to ``docs/build/html/``.

Translations
------------

Translation files are PHP files in ``resources/private/lang/``. The English file
``en.php`` is the source of truth - compare against it when adding or updating a
translation.

AgenDAV translation system is based on labels which get translated into
full sentences/paragraphs. They can contain placeholders which get replaced
by the system depending on the context.

To add or update a translation:

1. Open ``resources/private/lang/en.php`` to see all available translation keys.
2. Copy ``en.php`` to ``<locale>.php`` (e.g. ``de_DE.php``) for a new language,
   or open the existing file for your locale to update it.
3. Translate the string values, keeping the PHP array key names unchanged.
4. Placeholders like ``{name}`` must be preserved as-is.

Building assets
---------------

If you are going to work on stylesheets, scripts or templates, you will need `npm
<https://www.npmjs.com/>`_. Run the following command to download all required dependencies::

   $ npm ci

Stylesheets
***********

Stylesheets are built using the Less pre-processor. They are written as ``.less`` files and
can be found inside the ``resources/private/assets/less`` directory.

Run the following command to rebuild them::

   $ npm run build:css

Templates
*********

Stylesheets are built using the dustjs templating engine.  They can be found inside the
``resources/private/assets/templates`` directory.

Run the following command to rebuild them::

   $ npm run build:templates

JavaScript
**********

Run the following command to rebuild them::

   $ npm run build:js

HTTP API
--------

The frontend uses a JSON API, see :doc:`api`.

.. toctree::
   :hidden:

   api
