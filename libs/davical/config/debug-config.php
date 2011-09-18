<?php
/**
* The configuration settings detailed here should probably not appear in most
* most people's configuration files, but they can be useful to have available
* to assist with debugging problems.
*/

/**
* if this is set then any e-mail that would normally be sent by DAViCal will be
* sent to this e-mail address for debugging.
*/
//$c->debug_email


/**
* If you want to debug you have to set to 1 one of this variable
* and then you can look at the error log of PHP for example :
* $c->dbg["ALL"] = 1;
* and then tail -f /var/log/apache2/error_log (or wherever PHP errors are logged).
*
* in the code a line like this:
* dbg_error_log( "Login", "blabla %s blalba %s",first_string, second_string );
* will produce in apache error log and if $c->dbg['Login'] ==1 : "blabla first_string blalba second_string"
* using format rules as for printf and related functions.
*/
// $c->dbg["ALL"] = 1;
// $c->dbg["request"] = 1;   // The request headers & content
// $c->dbg['response'] = 1;  // The response headers & content
// $c->dbg["component"] = 1;
// $c->dbg['caldav'] = 1;
// $c->dbg['querystring'] = 1;
// $c->dbg['icalendar'] = 1;
// $c->dbg['ics'] = 1;
// $c->dbg['login'] = 1;
// $c->dbg['options'] = 1;
// $c->dbg['get'] = 1;
// $c->dbg['put'] = 1;
// $c->dbg['propfind'] = 1;
// $c->dbg['proppatch'] = 1;
// $c->dbg['report'] = 1;
// $c->dbg['principal'] = 1;
// $c->dbg['user'] = 1;
// $c->dbg['vevent'] = 1;
// $c->dbg['rrule'] = 1;

/**
* default is 'davical' used to prefix debugging messages but will only need to change
* if you are running multiple DAViCal servers logging into the same place.
*/
// $c->sysabbr = 'davical';

/**
* As yet we only support quite a limited range of options.  When we see clients looking
* for more than this we will work to support them further.  So we can see clients trying
* to use such methods there is a configuration option to override and allow lying about
* what is available.
* ex : $c->override_allowed_methods = "PROPPATCH,OPTIONS, GET, HEAD, PUT, DELETE, PROPFIND, MKCOL, MKCALENDAR, LOCK, UNLOCK, REPORT"
* Don't muck with this unless you are trying to write code to support a new option!
*/
// $c->override_allowed_methods = "PROPPATCH, OPTIONS, GET, HEAD, PUT, DELETE, PROPFIND, MKCOL, MKCALENDAR, LOCK, UNLOCK, REPORT"

