Release notes
=============

.. _v1.2:: 
1.2 (2011-x-x)
--------------

* DB schema needs to be altered. UTF8 wasn't being used by default, and
  sessions table wasn't using InnoDB. Apply the changes on
  ``sql/changes/1.1.1_to_1.2.mysql``, which are the following::

        ALTER DATABASE agendav CHARACTER SET utf8 COLLATE utf8_general_ci;
        ALTER TABLE sessions CONVERT TO CHARACTER SET utf8;
        ALTER TABLE sessions ENGINE InnoDB;
        ALTER TABLE shared CONVERT TO CHARACTER SET utf8;

* Main configuration file (``config.php``) has been completely **rewritten**
  to make it easier to write. Please, use the provided ``config.php.template``
  as the base for a new ``config.php``

* Interface translation and timezone configuration is now possible in
  AgenDAV. Please, make sure you set correct values on ``config.php``

* AgenDAV has lots of corrections and fixes. See the ``CHANGELOG``

.. _v1.1.1:: 
1.1.1 (2011-09-24)
------------------

* Fix DB schema. Wasn't properlty updated on sql/schema.sql, which
  caused a problem with sessions

  To correct this issue without completely recreating your current database,
  run the following two queries::

        CREATE INDEX last_activity_idx ON sessions(last_activity);
        ALTER TABLE sessions MODIFY user_agent VARCHAR(120); 
   
* Remove LDAP dependency. AgenDAV now authenticates against CalDAV
  server.

  Before this change, AgenDAV authenticated users at first using LDAP, and
  then your CalDAV server had to authenticate them again. With this change,
  AgenDAV completely relies on your CalDAV server.
