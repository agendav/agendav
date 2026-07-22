Quickstart
==========

This guide walks you through the fastest path to a working, self-hosted
calendar stack: `Baikal <https://sabre.io/baikal/>`_ as the CalDAV server,
a MariaDB database, and AgenDAV as the web frontend.

.. note::

   This is just one example stack, meant to get you started quickly. You
   may run different components (other CalDAV servers, databases, web
   servers) and split them across different infrastructure - see
   :doc:`installation` for other options. Adjust TLS, backups and
   hardening to your own requirements before exposing it on the internet.

Step 1: Set up Baikal
----------------------

Baikal is a lightweight CalDAV/CardDAV server, written in PHP. Download the
latest release from the `Baikal releases page
<https://github.com/sabre-io/Baikal/releases>`_ and unpack it::

  $ tar xf baikal-....tar.gz
  $ cd baikal/

Point your web server to the ``html/`` subdirectory, then make the
``Specific/`` and ``config/`` directories writable by your web server
user::

  # chown -R www-data:www-data Specific/ config/

Open ``http://your.host/admin/`` and finish the setup wizard: choose
SQLite (bundled) or a database of your own, then create a user account.
This account is what you will log into AgenDAV with, so note the username
and password.

Step 2: Create the AgenDAV database
------------------------------------

AgenDAV needs its own database to store preferences and calendar sharing
metadata - it does not store calendar data itself. Using MariaDB/MySQL::

  $ mysql --default-character-set=utf8 -uroot -p
  mysql> CREATE DATABASE agendav CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
  mysql> CREATE USER 'agendav'@'localhost' IDENTIFIED BY 'yourpassword';
  mysql> GRANT ALL PRIVILEGES ON agendav.* TO 'agendav'@'localhost';
  mysql> FLUSH PRIVILEGES;
  mysql> ^D

PostgreSQL and SQLite are also supported - see :ref:`Database requirements
<requirements>` in the installation guide for details.

Step 3: Install AgenDAV
-------------------------

Make sure your server meets the :ref:`requirements`, then download and
unpack the latest release::

  $ tar xf agendav-...tar.gz
  $ cd agendav-.../

Point your web server to the ``public/`` subdirectory. See
:ref:`Web server configuration <webserver>` for sample Apache and Nginx
configurations.

Step 4: Configure AgenDAV
----------------------------

Copy the settings template::

  $ cp config/settings.template.php config/settings.php

Edit ``config/settings.php`` and set at least:

* ``csrf.secret`` - any random string
* ``db.options`` - the database credentials created in Step 2
* ``caldav.baseurl`` - your Baikal CalDAV endpoint, for example
  ``http://your.host:8081/dav.php/``

See :doc:`configuration` for a full description of every option.

Step 5: Create AgenDAV tables
--------------------------------

Run the bundled CLI script to create the required tables::

  $ php bin/agendavcli migrations:migrate

Step 6: Log in
----------------

Fix directory permissions as described in :ref:`webserver`, then open
AgenDAV in your browser and log in with the Baikal user account you
created in Step 1.

Next steps
----------

* :doc:`configuration` - customize site title, logo, and other settings
* :doc:`troubleshooting` - if something does not work as expected
