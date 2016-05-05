# Change Log

This project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased][unreleased]

### Changed

- Upgraded to Symfony 2.8.5 components

### Fixed

- Etags were not being updated after dropping/resizing an event

## [2.0.0-beta2] - 2016-04-20

### Changed

- AgenDAV now requires PHP 5.5.0 or greater
- HTML code is now allowed on the footer message
- Replaced abandoned Keboola/php-encryption with phpseclib/phpseclib
- Moved agendavcli out of the bin/ subdirectory to the root directory
- Upgraded symfony/security and doctrine/* to non-vulnerable versions
- Upgraded to Bootstrap 3.3.6
- Upgraded to latest moment-timezone (0.4.1)
- Upgraded to latest Symfony 2.8.x components
- Upgraded to sabre/dav 3.1.3
- Upgraded to latest UUID generation library
- Upgraded to monolog 1.18.1
- Upgraded to Guzzle 6
- Upgraded to jQuery 1.12.3
- Upgraded to jQuery UI 1.11.4
- Upgraded to Fullcalendar 2.6.1, moment 2.13.0 and moment-timezone 0.5.3
- Improved internal XML generation component
- Error messages from the server are now handled by default, even if an error handler was not
  provided
- Switched to the new PdoSessionHandler from Symfony

### Added

- New translations: Slovak and Portuguese (Portugal)

### Removed

- Sessions are not encrypted anymore by AgenDAV

### Fixed

- Build issue with new Bootstrap releases (#152)
- Authorization headers are now hidden on HTTP debug logs
- Some properties were being overwritten by mistake (#159)
- PostgreSQL migrations were not working (#150)
- Recurrent events: first instance not considered "special" anymore (#170)

## [2.0.0-beta1] - 2015-08-26

This is a beta release. Calendar sharing is not available.

### Changed
- New PHP stack based on Silex framework, Doctrine and Sabre/VObject
- Dialogs are now client-side generated. The UI feels faster
- AgenDAV now requires PHP 5.4.0 or greater
- Cleaner user interface
- New color palette based on Material

### Added
- Each day height is now under control. Crowded days will show a _+n events_ link
- More database backends supported (including SQLite)
- Support for internal debugging
- New repetition rules editor with support for more complex rules
- New reminders editor
- Support for exceptions on repetitive events
- Users can now set their own language, date and time formats, which day the week starts on and timezone
- New translation: Japanese

### Removed
- Users cannot hide calendars anymore
- The agenda view has been removed from AgenDAV

### Fixed
- Exotic timezones are now handled the right way

## [1.2.6.2] - 2012-10-15
- Add missing files

## [1.2.6.1] - 2012-10-15
- Handle timezones with three components (X/Y/Z)

## [1.2.6] - 2012-09-03
- Added Reminders support
- Changed dialog rendering method to use client templates via JavaScript. Much faster!
- Removed load indicator that blocked the whole page. Now it's just an spinning wheel on the top right of the application
- Upgraded:
  - CodeIgniter (to 2.1.2)
  - jQuery UI (to 1.8.23)
  - iCalcreator (to 2.14)
- Added support for IPv6 clients
- Added new option to customize login page image
- Added pt-BR translation (thanks to Fernando Mercês)
- Lots of UI improvements (icons, tooltips)
- Lots of internal fixes and improvements
- Improved print stylesheets

## [1.2.5.1] - 2012-06-11
- Removed bogus DB update scripts that made the dbupdate process fail

## [1.2.5] - 2012-06-07
- Updated jQuery to 1.7.2
- Updated jQuery UI to 1.8.20
- Updated iCalcreator to 2.12
- Added PostgreSQL support
- CalDAV client rewritten to use cURL (HTTP Digest auth now supported)
- Rewritten calendar sharing interface with autocomplete (using principal-property-search)
- Added support for read-only calendar sharing
- Improved memory usage by reusing DateTimeZone objects
- Applied some fullcalendar patches from pull request #48:
- Now a line shows current time in week/day views
- New view: 'agenda'
- Lots of aesthetical changes
- New automated database upgrade process (migrations like)
- Users can now configure their default calendar, hide calendars from list and temporarily hide events from selected calendars
- Added et (Estonian) translation (thanks to Rivo Zängov)

## [1.2.4] - 2012-01-16
- Aesthetical changes: 
- Event box padding
- Changed default blue color
- Current day cell is clearly highlighted
- More vertical and horizontal space
- Weekend days have a different background color
- Changed calendar list style
- Switched to default cursor style instead of pointer
- JavaScript and CSS compression and unification to make AgenDAV load faster
- Fixed translations, were not working in IE7
- Now CalDAV URLs for principals and calendars can have different schemas
- Calendar sharing can be disabled for those servers that don't have ACL support
- Upgraded to iCalcreator 2.10.23
- Upgraded to latest git qTip2
- Upgraded to jQuery 1.7.1
- Session cookies are now smaller
- Added script (configtest.php) to check AgenDAV installation requisites and basic configuration
- Added fr_FR translation (thanks to Guillaume BF)
- Added nl_NL translation (thanks to Henry Verdonschot)
- More minor bugfixes

## [1.2.3] - 2011-11-08
- Better error logging when in production mode
- Fixed editing of recurring events
- Fixed DAViCal/awl include paths when installed in same machine
- Fixed am/pm indicator under some circumstances
- Fixed fuzzy buttons on dialogs (issue #13)
- Event text color changes depending on background (dark/light)
- New default colors and color selection dialog

## [1.2.2] - 2011-10-25
- Reverted upgrade to iCalcreator. Went back to 2.10.5
- Fixed am/pm indicator
- Fixed edit of recurrent events

## [1.2.1] - 2011-10-24
- Fixed timezone and DST issues
- Fixed untranslated string
- Fixed centering of dialogs, which were top aligned
- Upgraded to latest iCalcreator release (2.10.15)
- Upgraded to latest git qTip2 release
- Modified Fullcalendar to send UTC timestamps to server

## [1.2] - 2011-10-17
- Fixed DB schema to allow full UTF8 strings. Database changes needed
- Added localization support (i18n)
- Removed annoying success messages
- Login page now shows correct logo
- Simplified configuration file
- Fixed error on calendars with special names
- Fixed error on DAViCal installations not on host root
- Upgraded Aristo jQuery UI theme to latest git version
- Better form appearance and alignment
- 'Today' cell shows with a yellow background instead the previous pale blue
- Now you can customize time/date formats and some calendar visualization parameters
- Calendar now keeps its height when changing between months with 5-6 weeks
- Configurable timezone support
- Hide time fields when editing/creating an all day event
- Add a select helper when creating events in week/day view
- Added de_DE translation (thanks to Andreas Stöckel)
- Added it_IT translation (thanks to Lorenzo Novaro)
- Added de_AT translation (thanks to Hermann Schwärzler)

## [1.1.1] - 2011-09-24
- Fix DB schema. Wasn't properlty updated on sql/schema.sql, which caused a problem with sessions
- Remove LDAP dependency. AgenDAV now authenticates against CalDAV server

## [1.1] - 2011-09-18
- AgenDAV has now a logo!
- Added multiple calendars support. Auto discovered via PROPFIND
- Added read+write calendar sharing, based on WebDAV ACLs
- Changed from jQuery overlay to qTip tooltips
- Restyled whole application using Aristo jQuery UI theme
- Added a _Create event_ button on top of calendar list
- Event creation/modification is now tabbed
- iCalendar parsing times are now benchmarked. They get logged and sent within HTTP header X-Parse-Time
- Upgraded libraries:
  - CodeIgniter 2.0.3
  - iCalcreator 2.10.5
  - jQuery 1.6.4
  - jQuery UI 1.8.16
  - Fullcalendar 1.5.2

## [1.0.1] - 2011-06-14
- Added 'logout_redirect_to' option
- Added 'additional_js' option
- All day events can now last more than a single day
- Upgraded to latest iCalcreator beta (2.9.6rc)
- VTIMEZONEs are now generated using iCalcreator functionality instead of having lots of .ics files on config/vtimezones
- Upgraded to jQuery 1.6.1 and jQuery UI 1.8.13
- Rearranged calendar navigation items, including a new datepicker
- Multiple calendar support
- Automatic and periodic session refresh
- Dialogs have now icons within buttons to make them clearer
- Several bugfixes and optimizations
- Changed to perifer timePicker (resembles Google Calendar timepicker)

