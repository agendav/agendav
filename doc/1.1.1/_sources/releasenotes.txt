Release notes
=============

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
