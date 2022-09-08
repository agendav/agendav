Installation
============

In this section you will found instructions on how to install AgenDAV.

.. _requirements:

Requirements
------------

AgenDAV |release| requires the following software to be installed:

* A CalDAV server
* A web server
* PHP >= 7.2.0
* PHP extensions:

  * ctype
  * curl
  * curl
  * mbstring
  * mcrypt
  * tokenizer
  * xml
  * xmlreader
  * xmlwriter

* A database backend

.. warning::
   Some PHP releases have issues with digest authentication under Windows. If your CalDAV server
   uses digest authentication and you are hosting AgenDAV on a Windows server, make sure your PHP
   version is not affected.

   See `PHP bug #70101 <https://bugs.php.net/bug.php?id=70101>`_ for more details.

Most popular database backends are supported, such as MySQL, PostgreSQL or SQLite.

Look for supported databases on this `Doctrine DBAL driver list <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#driver>`_.

Download AgenDAV
----------------

AgenDAV |release| can be obtained at `AgenDAV GitHub Project <https://github.com/agendav/agendav/releases>`_.

Uncompress it using ``tar``::

 $ tar agendav-...tar.gz
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
 mysql> GRANT ALL PRIVILEGES ON agendav.* TO agendav@localhost IDENTIFIED BY 'yourpassword'
 mysql> CREATE DATABASE agendav CHARACTER SET utf8 COLLATE utf8_general_ci;
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

It is recommended to read the `Silex Webserver configuration guide
<http://silex.sensiolabs.org/doc/web_servers.html>`_ to learn how to configure your preferred web
server software to serve AgenDAV. Just make sure to point your web server to the ``web/public``
subdirectory.

Being Apache one of the most used web servers, a sample configuration is shown below for reference::

 <VirtualHost 1.2.3.4:443>
  ServerAdmin admin@email.host
  DocumentRoot /path/to/agendav/web/public
  ServerName agendav.host
  ErrorLog logs/agendav_error_log
  CustomLog logs/agendav_access_log common

  <Location />
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [QSA,L]
  </Location>
 </VirtualHost>


You can enable development mode by following the instructions at
:ref:`development_environment`.

Fix directory permissions
-------------------------

You should change the owner and group for all AgenDAV files to the ones your webserver uses.
Make sure you allow your webserver user to write on the ``var/`` directory. The following example
assumes your web server runs as `www-data` user and `www-data` group::

  # chown -R www-data:www-data web/
  # chmod -R 750 web/var/

Configuration
-------------

Now you should configure AgenDAV following the :doc:`configuration` section.

Create AgenDAV tables
---------------------

AgenDAV tables are created by running the provided ``agendavcli`` script.

After configuring your AgenDAV instance, including your database settings, just run the script like
this::

  $ php agendavcli migrations:migrate

Confirm the operation, and your database should be ready.
