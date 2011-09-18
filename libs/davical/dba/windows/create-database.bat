@echo off
rem Build the DAViCal database

rem BAT file changes are internal only
setlocal

if db%1 EQU db (
    echo Usage: create-database dbnameprefix [adminpassword [pguser]]
    exit /B 1
)
set DBNAME=%1-davical
set ADMINPW=%2

set DBADIR=%CD%\..

rem Attempt to locate the AWL directory
set AWLDIR=%DBADIR%\..\..\awl\dba
echo %AWLDIR%
if EXIST %AWLDIR%\awl-tables.sql (
  rem awldir=%AWLDIR%
) ELSE (
  echo awl directory not found
  exit /B 2
)

rem Set DB user, web user, DB config directory, Windows DB config directory
set AWL_DBAUSER=davical_dba
set AWL_APPUSER=davical_app
set DBA=%AWL_DBAUSER%

rem Need PostgreSQL location
if DEFINED %PGDIR% (
    rem Use existing variable
) ELSE (
    if EXIST "c:\Program Files\PostgreSQL\8.3\bin\createuser" (
        set PGDIR="c:\Program Files\PostgreSQL\8.3\bin"
    )
)
echo PGDIR=%PGDIR%
rem set PGDIR=%PGLOCALEDIR%\..\..\bin

rem Get the major version for PostgreSQL
rem set DBVERSION="`%PGDIR\psql -qAt -c "SELECT version();" template1 | cut -f2 -d' ' | cut -f1-2 -d'.'`"

rem Show general info
IF usr%3 NEQ usr ( set USERNAME=%3 )
echo username=%USERNAME%

rem Create DB user, web user
%PGDIR%\createuser -U %USERNAME% --no-createdb --no-createrole --no-superuser %AWL_DBAUSER%
%PGDIR%\createuser -U %USERNAME% --no-createdb --no-createrole --no-superuser %AWL_APPUSER%

echo Creating DB=%DBNAME%
%PGDIR%\createdb -E UTF8 -T template0  -U %USERNAME% %DBNAME% 
if %ERRORLEVEL% NEQ 0 ( 
   echo Unable to create database
   exit /B 2
)

rem This will fail if the language already exists, but it should not
rem because we created from template0.
%PGDIR%\createlang -U %USERNAME% plpgsql %DBNAME%

rem Test if egrep is available
rem You can download egrep.exe for Windows e.g. from UnxUtils: http://unxutils.sourceforge.net/):
egrep 2>NULL
echo egrep results: %ERRORLEVEL%
if %ERRORLEVEL% EQU 3 (
    rem No egrep

    rem Load the AWL base tables and schema management tables
    echo load windows\awl-tables.sql [no egrep]
    %PGDIR%\psql -q -f %AWLDIR/awl-tables.sql %DBNAME% %USERNAME% 2>&1 

    echo load windows\schema-management.sql [no egrep]
    %PGDIR%\psql -q -f %AWLDIR%/schema-management.sql %DBNAME% %USERNAME% 2>&1

    rem Load the DAViCal tables
    echo load davical [no egrep]
    %PGDIR%\psql -q -f %DBADIR%\davical.sql %DBNAME% %USERNAME% 2>&1

) ELSE (
    rem egrep is available

    rem Load the AWL base tables and schema management tables
    echo load windows\awl-tables [egrep]
    %PGDIR%\psql -q -f %AWLDIR%/awl-tables.sql %DBNAME% %USERNAME% 2>&1 | egrep -v "(^CREATE |^GRANT|^BEGIN|^COMMIT| NOTICE: )"
    echo load WINDOWS schema-management [egrep]
    %PGDIR%\psql -q -f %AWLDIR%/schema-management.sql %DBNAME% %USERNAME% 2>&1 | egrep -v "(^CREATE |^GRANT|^BEGIN|^COMMIT| NOTICE: |^t$)"

    rem Load the DAViCal tables
    echo load davical [egrep]
    %PGDIR%\psql -q -f %DBADIR%/davical.sql %DBNAME% %USERNAME% 2>&1 | egrep -v "(^CREATE |^GRANT|^BEGIN|^COMMIT| NOTICE: |^t$)"
)
del NULL

echo load caldav_functions
%PGDIR%\psql -q -f %DBADIR%/caldav_functions.sql %DBNAME%  %USERNAME%

echo TBD: Set permissions for the application DB user on the database
rem if EXIST %DBADIR%\update-davical-database (
rem   %DBADIR%\update-davical-database --dbname %DBNAME% --appuser %AWL_APPUSER% --nopatch --owner %AWL_DBAUSER%
rem ) ELSE (
rem   if EXIST %DBADIR%\..\update-davical-database (
rem     %DBADIR%\..\update-davical-database --dbname %DBNAME% --appuser %AWL_APPUSER% --nopatch --owner %AWL_DBAUSER%
rem   ) ELSE (
rem     echo Could not find update-davical-database...ignoring
rem   )
rem )
rem if %ERRORLEVEL% NEQ 0 (
rem   echo * * * * ERROR * * * *
rem   echo The database administration utility failed.  This is usually due to the Perl YAML
rem   echo or the Perl DBD::Pg libraries not being available.

rem   echo See:  http://wiki.davical.org/w/Install_Errors/No_Perl_YAML

rem   exit /B 2
rem )

rem Load the required base data
echo load base-data
%PGDIR%\psql -q -f %DBADIR%/base-data.sql %DBNAME%  %USERNAME%

rem We can override the admin password generation for regression testing predictability
rem if [ %ADMINPW}" = "" ] ; then
rem   #
rem   # Generate a random administrative password.  If pwgen is available we'll use that,
rem   # otherwise try and hack something up using a few standard utilities
rem   ADMINPW="`pwgen -Bcny 2>/dev/null | tr \"\\\'\" '^='`"
rem fi
rem 
rem if [ "$ADMINPW" = "" ] ; then
rem   # OK.  They didn't supply one, and pwgen didn't work, so we hack something
rem   # together from /dev/random ...
rem   ADMINPW="`dd if=/dev/urandom bs=512 count=1 2>/dev/null | tr -c -d "[:alnum:]" | cut -c2-9`"
rem fi
rem 
rem   # Right.  We're getting desperate now.  We'll have to use a default password
rem   # and hope that they change it to something more sensible.
IF pw%ADMINPW% EQU pw ( set ADMINPW=please change this password )
rem fi

%PGDIR%\psql -q -c "UPDATE usr SET password = '**%ADMINPW%' WHERE user_no = 1;" %DBNAME%  %USERNAME%

echo The password for the 'admin' user has been set to "%ADMINPW%"

rem The supported locales are in a separate file to make them easier to upgrade
%PGDIR%\psql -q -f %DBADIR%/supported_locales.sql %DBNAME%  %USERNAME%

echo DONE

:END

endlocal

