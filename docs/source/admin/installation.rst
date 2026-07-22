Installation
============

AgenDAV is a web frontend for an existing CalDAV server. It does not store
any calendar data itself - it connects to a CalDAV server and displays your
calendars and events in a browser interface.

.. _prerequisites:

Prerequisites
-------------

Before installing AgenDAV, you need a running CalDAV server with at least one
user account. Popular options:

* `Baïkal <https://github.com/sabre-io/Baikal>`_ - lightweight, easy to set up, PHP-based
* `Nextcloud <https://nextcloud.com/>`_ - full groupware suite with a built-in CalDAV server
* `DAViCal <https://www.davical.org/>`_ - full-featured, PHP-based
* `Radicale <https://radicale.org/>`_ - minimal, Python-based

Once your CalDAV server is running and you have a user account on it, continue
with the AgenDAV installation below and point it at your CalDAV server URL.

Don't have a CalDAV server yet?

* Just want to try out AgenDAV? Run the Docker demo locally - see
  :doc:`/development/index`.
* Want to quickly set up a complete, self-hosted calendar stack? See the
  :doc:`quickstart` guide.

.. _requirements:

Requirements
------------

AgenDAV |release| requires the following software to be installed on the server
where AgenDAV itself runs:

* A web server
* PHP >= 8.5.0
* PHP extensions:

  * ctype
  * curl
  * mbstring
  * openssl
  * tokenizer
  * xml
  * xmlreader
  * xmlwriter

* A database backend

Most popular database backends are supported, such as MySQL, PostgreSQL or SQLite.

Look for supported databases on this `Doctrine DBAL driver list <https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html#driver>`_.

Download AgenDAV
----------------

AgenDAV |release| can be obtained at `AgenDAV GitHub Project <https://github.com/agendav/agendav/releases>`_.

Uncompress it using ``tar``::

 $ tar xf agendav-...tar.gz
 $ cd agendav-.../

PHP configuration
-----------------

Make sure that you have the following PHP settings set:

* ``date.timezone``: choose a valid time zone from `this list <http://php.net/manual/en/timezones.php>`_, for example ``Europe/Berlin``.

This is usually done on your ``php.ini`` file.

Database requirements
---------------------

AgenDAV requires a database to store some extra information.

First of all you have to set up your database. If you plan using MySQL or PostgreSQL, here you will
find some basic instructions about how to set up them.

**Setting up a MySQL/MariaDB database**

.. warning::
   If you have binary logging enabled in MySQL/MariaDB, make sure it is configured to use
   `binlog_format = MIXED`. Or just disable binary logging in case you don't actually need it.

   AgenDAV will complain and exit in case you have a different binary logging configuration.

Create a user in MySQL and let it use a new `agendav` database::

 $ mysql --default-character-set=utf8 -uroot -p
 Enter password:
 [...]
 mysql> CREATE DATABASE agendav CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
 mysql> CREATE USER 'agendav'@'localhost' IDENTIFIED BY 'yourpassword';
 mysql> GRANT ALL PRIVILEGES ON agendav.* TO 'agendav'@'localhost';
 mysql> FLUSH PRIVILEGES;
 mysql> ^D

**Setting up a PostgreSQL database**

Use the special ``postgres`` system user to manage your installation. You
can add a new user and a new database the following way::

 # su postgres
 $ psql
 postgres=# CREATE USER agendav WITH PASSWORD 'somepassword';
 postgres=# CREATE DATABASE agendav ENCODING 'UTF8';
 postgres=# GRANT ALL PRIVILEGES ON DATABASE agendav TO agendav;
 postgres=# \q
 $ exit

Then you have to edit the file ``pg_hba.conf``, which is usually located at
``/var/lib/pgsql/``. Add the following line before other definitions::

 # TYPE  DATABASE    USER        CIDR-ADDRESS          METHOD
 local   agendav     agendav                           md5

**Setting up a SQLite database**

SQLite is not recommended for production environments, but will be more than enough for testing and
single user environments.

You will need a dedicated directory for the database::

  # mkdir database
  # touch database/agendav.sqlite
  # chown -R www-data:www-data database/


.. _webserver:

Web server configuration
------------------------

It is recommended to read the `Slim 4 Web Servers guide
<https://www.slimframework.com/docs/v4/start/web-servers.html>`_ to learn how to configure your preferred web
server software to serve AgenDAV. Just make sure to point your web server to the ``public/``
subdirectory.

Being Apache one of the most used web servers, a sample configuration is shown below for reference::

 <VirtualHost 1.2.3.4:443>
  ServerAdmin admin@email.host
  DocumentRoot /path/to/agendav/public
  ServerName agendav.host
  ErrorLog logs/agendav_error_log
  CustomLog logs/agendav_access_log common

  <Directory /path/to/agendav/public>
    Options +FollowSymLinks
    AllowOverride All
  </Directory>

  <Location />
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]
  </Location>
 </VirtualHost>

A sample Nginx configuration is shown below::

 server {
     listen 443 ssl;
     server_name agendav.host;
     root /path/to/agendav/public;

     location ~ \.php$ {
         try_files      $uri =404;
         fastcgi_pass   unix:/run/php/php8.5-fpm.sock;
         fastcgi_index  index.php;
         include        fastcgi.conf;
     }

     location / {
         index index.php;
         try_files $uri $uri/ /index.php?$args;
     }
 }


You can enable development mode by following the instructions at
:ref:`development_environment`.

Fix directory permissions
-------------------------

You should change the owner and group for all AgenDAV files to the ones your webserver uses.
Make sure you allow your webserver user to write on the ``var/`` directory. The following example
assumes your web server runs as `www-data` user and `www-data` group::

  # chown -R www-data:www-data .
  # chmod -R 750 var/

Configuration
-------------

A ready-to-edit template is provided at ``config/settings.template.php``. Copy it to
``config/settings.php`` and adjust the values for your setup::

  $ cp config/settings.template.php config/settings.php

Then follow the :doc:`configuration` section for a description of every option.

Create AgenDAV tables
---------------------

AgenDAV tables are created by running the provided ``bin/agendavcli`` script.

After configuring your AgenDAV instance, including your database settings, just run the script like
this::

  $ php bin/agendavcli migrations:migrate

Confirm the operation, and your database should be ready.
