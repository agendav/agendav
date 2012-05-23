.. _upgrading:

Upgrading
=========

AgenDAV upgrades can be split into two simple steps.

Before starting this process, make sure you have a backup of your current
AgenDAV directory, specially the ``web/config/`` directory, and dump your
database schema and contents.

Please, do not continue unless you have both backups.

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

Read all the :ref:`releasenotes` from the version you were using
to current release, because some configuration files may have changed.

.. _dbupgrade:

Database upgrade
----------------

TODO
