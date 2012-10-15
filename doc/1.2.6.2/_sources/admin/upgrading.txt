.. _upgrading:

Upgrading
=========

AgenDAV upgrades can be split into two simple steps.

Before starting this process, make sure you have a backup of your current
AgenDAV directory, specially the ``web/config/`` directory, and dump your
database schema and contents.

Please, do not continue unless you have both backups.

Read all the :ref:`releasenotes` from the version you were using
to current release, because some configuration files may have changed. Apply
those changes after updating the files from AgenDAV.

.. _filesupgrade:

Files upgrade
-------------

a) Updating from tar.gz file
****************************

You can replace the whole AgenDAV directory with the new files, but it's
recommended to keep your old folder with a different name (e.g.
``agendav_old/``). You'll need it to copy back your configuration files.

After downloading the new tar.gz file and uncompressing it, copy your
configuration files from the old directory::

  $ cd agendav_old/web/config/ 
  $ cp -a advanced.php caldav.php config.php database.php \
    /path/to/new/agendav/web/config/


b) Updating from git
********************

If you downloaded AgenDAV from the git repository at GitHub then you can
checkout latest stable release from the ``master`` branch, or an specific
version using its tag.

Just pull latest changes and checkout the release you want. For example,
checking out AgenDAV 1.2.5 can be achieved with::

  $ git pull
  [...]
  $ git checkout 1.2.5

.. _dbupgrade:

Database upgrade
----------------

.. note::

   AgenDAV <= 1.2.5.1 have a bug that makes database upgrades to fail,
   complaining about a missing ``migration_lang.php`` file. If you have
   AgenDAV configured to use a language other than English, set
   :confval:`default_language` to ``en`` before running ``agendav dbupdate``

The database upgrade process included in AgenDAV since 1.2.5 lets you
apply the latest schema changes without having to deal with ``.sql`` files
and with no need to check which files you should apply to your current
version.

Just use the provided ``bin/agendavcli`` script this way::

  $ ./bin/agendavcli dbupdate



