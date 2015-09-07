.. _upgrading:

Upgrading
=========

Before starting this process, **make sure you have a backup** of your current
AgenDAV directory, specially the ``web/config/`` directory, and a dump of your
database schema and contents.

Please, do not continue unless you have both backups.

Read all the :ref:`releasenotes` starting at the version you are currently using, because some
important changes may have happened. Apply those changes after updating the files from AgenDAV.

Make sure your system meets the requirements before upgrading. Read the :ref:`requirements` section.

Upgrading from 1.x.x
--------------------

If you are upgrading AgenDAV from 1.x.x, make sure you have the latest 1.x release
installed.

.. warning::
   Current AgenDAV development version will remove all your current shares from the database. This
   will be fixed on next stable release

After that, just follow the steps below.

.. _filesupgrade:

Upgrade AgenDAV code
--------------------

a) Updating from a tar.gz file
******************************

After downloading the new tar.gz file and uncompressing it, copy your
configuration files from the old directory::

  $ cp -a /path/to/old_agendav/web/config/settings.php \
    /path/to/new/agendav/web/config/

This will only work if you are upgrading from AgenDAV 2.x, as older releases
used different configuration files.

b) Updating from git
********************

If you downloaded AgenDAV from the git repository at GitHub then you can
checkout latest stable release from the ``master`` branch, or an specific
version using its tag.

Just pull latest changes and checkout the release you want. For example,
checking out AgenDAV 2.0.0 can be achieved with::

  $ git pull
  [...]
  $ git checkout 2.0.0

Next step is downloading latest AgenDAV dependencies using Composer. If you
already have Composer installed, just run::

 $ cd web/
 $ composer install

If you are upgrading from AgenDAV 1.2.x, you will need to install Composer.
Follow the instructions you'll find in the installation section.

.. _dbupgrade:

Database upgrade
----------------

The database upgrade process included in AgenDAV lets you
apply the latest schema changes without having to deal with ``.sql`` files
and with no need to check which files you should apply to your current
version.

Follow the guide at :ref:`configuration` to create a new ``settings.php`` file inside
``web/config`` which contains at least the database connection details.

Once you have your database configuration prepared, run the provided ``agendavcli`` script this
way::

  $ php agendavcli migrations:migrate

.. warning::
   This development version will remove all your current shares from the database. This will
   be fixed on next stable release

Clear sessions and caches
-------------------------

It is recommended to remove all active sessions. Do it by running the
following command::

  $ php agendavcli sessions:clear

If you are running AgenDAV on a production environment, you should clear several
caches:

- Remove the contents of the _twig_ cache directory. The cache path is configured
  using the option ``twig.options`` on your ``settings.php`` file. If you did not override the
  default value, it should be found at `web/var/cache/twig/` subdirectory::

    $ rm -rf web/var/cache/twig/*
