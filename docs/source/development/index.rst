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
  ``web/public/`` with the source tree bind-mounted at ``/app`` so edits are picked up live
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

Vagrant (alternative)
*********************

If you also want to test the Ansible deployment recipe, the Vagrant flow provisions a full VM
with the same playbook used in production. Otherwise prefer the Docker Compose flow above.

Software you will need:

* `VirtualBox <https://www.virtualbox.org/wiki/Downloads>`_: virtualization software
* `Vagrant <https://docs.vagrantup.com/v2/installation/>`_: VM manager
* `Ansible <http://docs.ansible.com/intro_installation.html>`_: automation platform

Once you have them installed, let Vagrant initialize the virtual machine::

   $ vagrant up

A base image has to be downloaded, and lot of packages have to be installed
inside the machine, so this will take a while. Go grab a drink until the machine
is ready!

The virtual machine can be stopped and started again using Vagrant. The
initialization process will only run the first time you do ``vagrant up``, and
next starts will just require a few seconds.

You can stop the machine with ``vagrant halt``, and running ``vagrant up`` again
will bring it back to life.

Your current working directory is shared with the virtual machine, so you can
develop on your local machine and your changes will be visible inside the
virtual machine.

The environment created inside the virtual machine will be accessible using the
following URLs and commands:

* AgenDAV: http://localhost:8080/
* Baïkal server: http://localhost:8081/
* SSH: ``vagrant ssh``. Your local machine working directory will be mounted at
  ``/vagrant`` inside the virtual machine

The credentials for this environment are:

* Username: ``demo``
* Password: ``demo``

Note that :ref:`development_environment` will be enabled by default.

Validation
----------

A scripted smoke test lives at ``docker/smoke-test.sh``. It runs against the Compose stack and
exercises login (form + HTTP Basic), calendar/event CRUD through Baikal, preferences
persistence, CSRF rejection, and the 404 path::

   $ bash docker/smoke-test.sh           # idempotent; reuses an existing stack
   $ bash docker/smoke-test.sh --reset   # rebuild and re-seed from scratch
   $ bash docker/smoke-test.sh --down    # tear the stack down

Exit code 0 means every assertion passed; failed assertions print to stdout and are also
written to ``web/var/log/<today>.log``.

Building assets
---------------

If you are going to work on stylesheets, scripts or templates, you will need `npm
<https://www.npmjs.com/>`_. Run the following command to download all required dependencies::

   $ npm install

Stylesheets
***********

Stylesheets are built using the Less pre-processor. They are written as ``.less`` files and
can be found inside the ``resources/private/assets/less`` directory.

Run the following command to rebuild them::

   $ npm run build:css

Templates
*********

Stylesheets are built using the dustjs templating engine.  They can be found inside the
``resources/private/assets/less`` directory.

Run the following command to rebuild them::

   $ npm run build:templates

JavaScript
**********

Run the following command to rebuild them::

   $ npm run build:js
