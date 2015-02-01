.. _upgrading:

Upgrading
=========

Before starting this process, make sure you have a backup of your current
AgenDAV directory, specially the ``web/config/`` directory, and dump your
database schema and contents.

Please, do not continue unless you have both backups.

Read all the :ref:`releasenotes` from the version you were using
to current release, because some configuration files may have changed. Apply
those changes after updating the files from AgenDAV.

Upgrading from 1.x.x
--------------------

If you are upgrading AgenDAV from 1.x.x, make sure you have the latest 1.x release
installed.

AgenDAV 2.x is configured now using a single configuration file. Follow the
guide at :ref:`configuration` to create a new ``settings.php`` file inside ``web/config``.

After that, just follow the steps below.

.. _filesupgrade:

Upgrade AgenDAV code
--------------------

a) Updating from tar.gz file
****************************

After downloading the new tar.gz file and uncompressing it, copy your
configuration files from the old directory::

  $ cp -a /path/to/old_agendav/web/config/settings.php \
    /path/to/new/agendav/web/config/


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

If you're upgrading from AgenDAV 1.2.x, you will need to install Composer.
Follow the instructions you'll find in the installation section.

.. _dbupgrade:

Database upgrade
----------------

The database upgrade process included in AgenDAV lets you
apply the latest schema changes without having to deal with ``.sql`` files
and with no need to check which files you should apply to your current
version.

Just use the provided ``bin/agendavcli`` script this way::

  $ ./bin/agendavcli migrations:migrate

Please, note that this requires you have created a ``settings.php`` file with
a valid configuration to connect your database.
