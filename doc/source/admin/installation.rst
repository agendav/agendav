Installation
============

In this section you will found instructions on how to install AgenDAV.

Prerequisites
-------------

AgenDAV |release| requires the following software to be installed:

* A CalDAV server
* A web server
* PHP >= 5.4.0
* PHP mbstring extension
* PHP cURL extension
* A database backend

Most popular database backends are supported, such as MySQL, PostgreSQL or SQLite.

Look for supported databases on this `Doctrine DBAL driver list <http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#driver>`_.

Downloading AgenDAV and uncompressing
-------------------------------------

AgenDAV |release| can be obtained at `AgenDAV webpage <http://agendav.org>`_.

Uncompress it using ``tar``::

 $ tar agendav-...tar.gz
 $ cd agendav-.../

Download dependencies (only for Git)
------------------------------------

If you downloaded AgenDAV from GitHub using ``git``, you will need to download
`Composer <http://getcomposer.org>`_ and use it to fetch AgenDAV dependencies.

Composer is a PHP package manager, and some libraries used by AgenDAV are
downloaded using it.

Composer installation is really simple::

 $ cd web/
 $ curl -s https://getcomposer.org/installer | php
 $ php composer.phar install

Database and tables
-------------------

AgenDAV requires a database to store some extra information.

First of all you have to create a user and a database for that user.

After preparing your DB, you will have to configure your database settings, and after that you will be able to create
the database tables using a provided script.

How to create a user on MySQL
*****************************
Create a user in MySQL like this::

 $ mysql --default-character-set=utf8 -uroot -p
 Enter password: 
 [...]
 mysql> GRANT ALL PRIVILEGES ON agendav.* TO agendav@localhost IDENTIFIED BY 'yourpassword'
 mysql> CREATE DATABASE agendav CHARACTER SET utf8 COLLATE utf8_general_ci;
 mysql> FLUSH PRIVILEGES;
 mysql> ^D

How to create a user on PostgreSQL
**********************************

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

Create AgenDAV tables
*********************

AgenDAV tables are created by running the provided ``agendavcli`` script.

Before being able to run it, you will have to configure at least your database
connection details. Have a look at the :confval:`db.options` parameter.

After configuring your database connection, just run the script like this::

  $ cd bin/
  $ ./agendavcli migrations:migrate

Confirm the operation, and your database should be ready.

Configuring  Apache to serve AgenDAV
------------------------------------

Apache has to be configured to point to ``web/public`` directory.

Example using a dedicated virtualhost::

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

.. note::
   Make sure that you have the following PHP settings *disabled*:

   * ``magic_quotes_gpc``
   * ``magic_quotes_runtime``


You can enable development mode by following the instructions at
:ref:`development_environment`.

Other web servers
*****************

AgenDAV should run on any other web server software as well. Just read the
`Silex Webserver configuration guide <http://silex.sensiolabs.org/doc/web_servers.html>`_.

Change directory permissions
----------------------------

You should change the owner and group for all AgenDAV files to the ones your webserver uses.
Make sure you allow your webserver user to write on the ``var/`` directory. The following example
assumes your webserver runs as `www-data` user and `www-data` group::

  # chown -R www-data:www-data web/
  # chmod -R 750 web/var/

Configure AgenDAV
-----------------

Now you can proceed to fully configure AgenDAV following the :doc:`configuration`
section.
