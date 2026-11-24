HTTP API
========

The AgenDAV frontend talks to the backend through a small JSON API. This page documents the
current contract. The routes are defined in ``app/routes.php``, the controllers live in
``src/Controller/``.

All endpoints except ``/login``, ``/logout`` and ``/translations/{language}`` require an
authenticated session.

General rules
-------------

Authentication
**************

Requests need a valid session cookie. Alternatively, HTTP Basic authentication can be used
if it is enabled (see ``auth.methods``).

Without authentication, requests that send ``X-Requested-With: XMLHttpRequest`` get a
``401`` response with the body ``{}``. All other requests get a ``302`` redirect to
``/login``.

CSRF protection
***************

All ``POST`` requests must carry a CSRF token, either in the ``X-CSRF-Token`` header
(preferred) or in the ``_token`` field of a form-encoded body. A missing or invalid token
results in ``401``.

The current token is returned by ``GET /config``.

Request format
**************

``GET`` endpoints read query parameters. ``POST`` endpoints read either a form-encoded
body (``application/x-www-form-urlencoded``) or a JSON body (``application/json``). Both
use the same parameter names. Form fields like ``shares[with][]`` become nested JSON:

.. code-block:: json

   {"calendar": "/cal/", "shares": {"with": ["/principals/demo/"], "rw": [true]}}

Boolean values can be sent as JSON booleans or as the strings ``true``/``false`` or
``1``/``0``.

Colors are hex values: ``#rgb``, ``#rgba``, ``#rrggbb`` or ``#rrggbbaa``. The leading ``#``
is optional.

Every value is checked before the request is processed. Missing required values, values of
the wrong type (for example a list instead of a text) and values that are not allowed are
rejected with ``400``.

Date formats used in requests:

- *Range dates* (``start``/``end`` of ``GET /events``): ``YYYY-MM-DD`` or
  ``YYYY-MM-DDTHH:MM:SS``, interpreted in the timezone given by ``timezone``
- *Event dates* (``start``/``end`` of ``POST /events/save``): ``YYYY-MM-DDTHH:MM:SS.uuuZ``
  (UTC, as produced by JavaScript ``Date.toISOString()``)
- ``timezone``: an IANA timezone name, for example ``Europe/Berlin``

Response envelope
*****************

Write endpoints answer with a JSON envelope:

.. code-block:: json

   {"result": "SUCCESS", "message": "", "data": {"etag": "\"5f1c…\""}}

``result`` is one of:

- ``SUCCESS`` - the action was done, ``data`` is a JSON object with named values for the
  client (see each endpoint), ``{}`` if there are none
- ``EXCEPTION`` - an expected problem (invalid input, permission denied, …), ``message``
  is a translated text for the user
- ``ERROR`` - an unexpected problem (CalDAV server unreachable, internal error), ``message``
  is a translated text for the user

``message`` is always text for the user, never data. Only ``SUCCESS`` responses have
``data``.

Read endpoints (``GET /calendars``, ``GET /events``, ``GET /eventbase``,
``GET /principals``) return their data directly, without the envelope. Errors on these
endpoints use the envelope.

Status codes
************

.. list-table::
   :header-rows: 1

   * - Situation
     - Status
     - ``result``
   * - Success
     - 200
     - ``SUCCESS``
   * - Invalid or missing input
     - 400
     - ``EXCEPTION``
   * - Permission denied by the CalDAV server
     - 403
     - ``EXCEPTION``
   * - Write request while ``calendar.read_only`` is enabled
     - 403
     - ``EXCEPTION``
   * - Write request on a read-only calendar
     - 403
     - ``EXCEPTION``
   * - Element not found
     - 404
     - ``EXCEPTION``
   * - Element was modified meanwhile (etag mismatch)
     - 412
     - ``EXCEPTION``
   * - CalDAV server unreachable
     - 503
     - ``ERROR``
   * - Unexpected CalDAV status or internal error
     - 500
     - ``ERROR``

Calendars
---------

``GET /calendars``
******************

Lists all calendars of the current user, including shared calendars and subscriptions.

Response:

.. code-block:: json

   [
     {
       "url": "/dav.php/calendars/test/default/",
       "calendar": "/dav.php/calendars/test/default/",
       "displayname": "Default",
       "color": "#3a89c9",
       "order": 0,
       "ctag": "http://sabre.io/ns/sync/12",
       "is_owned": true,
       "is_shared": false,
       "is_subscribed": false,
       "owner": "/dav.php/principals/test/",
       "writable": true,
       "shares": [
         {"sid": 1, "with": "/dav.php/principals/demo/", "displayname": "/dav.php/principals/demo/", "rw": 1}
       ]
     }
   ]

``POST /calendars``
*******************

Creates a calendar, or a subscription to an external ``.ics`` feed.

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``displayname``
     - yes
     - Calendar name
   * - ``calendar_color``
     - yes
     - Color, for example ``#3a89c9``
   * - ``is_subscribed``
     - no
     - ``true`` to create a subscription
   * - ``url``
     - for subscriptions
     - Feed URL, ``http`` or ``https`` only

Response: envelope, ``data`` is ``{}``.

``POST /calendars/save``
************************

Updates name, color and shares of a calendar.

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``
     - yes
     - Calendar URL
   * - ``displayname``
     - yes
     - Calendar name
   * - ``calendar_color``
     - yes
     - Color
   * - ``is_subscribed``
     - no
     - ``true`` for a subscription
   * - ``is_owned``
     - no
     - ``false`` for a calendar shared with the user, only name and color are stored locally
   * - ``shares[with][]``
     - no
     - Principal URLs to share the calendar with (only with ``calendar.sharing`` enabled)
   * - ``shares[rw][]``
     - no
     - Boolean, ``true`` for read-write, same order and length as ``shares[with][]``

Response: envelope, ``data`` is ``{}``.

``POST /calendars/delete``
**************************

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``
     - yes
     - Calendar URL
   * - ``is_subscribed``
     - no
     - ``true`` to remove a subscription

Response: envelope, ``data`` is ``{"calendar": "…"}`` (the removed calendar URL).

Events
------

Event object
************

``GET /events`` and ``GET /eventbase`` return event objects:

.. code-block:: json

   {
     "id": "/dav.php/calendars/test/default/1234-abcd",
     "calendar": "/dav.php/calendars/test/default/",
     "href": "/dav.php/calendars/test/default/1234-abcd.ics",
     "etag": "\"5f1c…\"",
     "uid": "1234-abcd",
     "title": "Meeting",
     "color": "#3a89c9",
     "start": "2026-11-23T10:00:00+01:00",
     "end": "2026-11-23T11:00:00+01:00",
     "allDay": false,
     "orig_allday": false,
     "location": "Office",
     "icalendar_class": "PUBLIC",
     "transp": "OPAQUE",
     "status": "CONFIRMED",
     "description": "",
     "reminders": [{"count": 15, "unit": "minutes"}]
   }

Timed events use ISO 8601 with offset, converted to the requested ``timezone``. All-day
events are not converted.

Recurrent events have these extra fields:

- ``rrule`` - the RRULE string
- ``has_exceptions``, ``is_exception`` - booleans
- ``recurrence_id`` - the RECURRENCE-ID of the instance, also appended to ``id`` as
  ``@<recurrence_id>``

``GET /events``
***************

Lists event instances of one calendar in a time range. Recurrent events are expanded.

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``
     - yes
     - Calendar URL
   * - ``start``, ``end``
     - yes
     - Range dates
   * - ``timezone``
     - yes
     - Timezone for range dates and the response
   * - ``is_subscribed``
     - no
     - ``true`` for a subscription
   * - ``uid``
     - no
     - Only return instances of this event

Response: a JSON array of event objects. Response headers ``X-Fetch-Time`` and
``X-Parse-Time`` carry timing information in seconds.

If the calendar is readable but its events are not, the response is ``403`` with an
``EXCEPTION`` envelope.

``GET /eventbase``
******************

Returns the master instance of an event (used to edit recurrent events).

Parameters: ``calendar``, ``uid``, ``timezone`` (all required).

Response: a single event object.

``POST /events/save``
*********************

Creates an event (no ``uid``) or modifies one (with ``uid``).

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``
     - yes
     - Target calendar URL
   * - ``summary``
     - yes
     - Title
   * - ``start``, ``end``
     - yes
     - Event dates, ``end`` must not be before ``start``
   * - ``timezone``
     - yes
     - Timezone of the event
   * - ``allday``
     - no
     - ``true`` for an all-day event
   * - ``uid``
     - for modifications
     - Event UID
   * - ``etag``
     - for modifications
     - Current etag
   * - ``original_calendar``
     - for modifications
     - Calendar the event is in now, differs from ``calendar`` when moving the event
   * - ``recurrence_id``
     - no
     - Modify only this instance of a recurrent event
   * - ``location``, ``description``, ``class``, ``transp``, ``color``, ``rrule``
     - no
     - Event properties
   * - ``reminders[count][]``, ``reminders[unit][]``
     - no
     - Reminders, same order and length

Response: envelope, ``data`` is ``{"calendars": […]}``, the calendar URLs that changed
(one, or two when the event was moved).

``POST /events/drop``
*********************

Moves an event instance by a number of minutes (drag and drop).

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``, ``uid``, ``timezone``
     - yes
     - Event to move
   * - ``delta``
     - yes
     - Minutes to move, may be negative
   * - ``was_allday``, ``allday``
     - yes
     - All-day state before and after the move
   * - ``recurrence_id``
     - no
     - Move only this instance

Response: envelope, ``data`` is ``{"etag": "…"}``.

``POST /events/resize``
***********************

Changes the end of an event instance by a number of minutes.

Parameters: ``calendar``, ``uid``, ``timezone``, ``delta`` (minutes, required),
``recurrence_id`` (optional).

Response: envelope, ``data`` is ``{"etag": "…"}``.

``POST /events/delete``
***********************

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``calendar``, ``uid``, ``href``, ``etag``
     - yes
     - Event to delete
   * - ``recurrence_id``
     - no
     - Delete only this instance

Response: envelope, ``data`` is ``{}``, or ``{"etag": "…"}`` when a single instance was
removed.

Preferences
-----------

``POST /preferences/save``
**************************

Saves the preferences of the current user. The current preferences are part of
``GET /config``.

Every value must be one of the allowed values below, otherwise the request is rejected
with ``400``. Optional values that are left out use the configured defaults.

.. list-table::
   :header-rows: 1

   * - Parameter
     - Required
     - Description
   * - ``language``
     - yes
     - One of the configured languages, for example ``de_DE``
   * - ``timezone``
     - yes
     - IANA timezone name, for example ``Europe/Berlin``
   * - ``default_calendar``
     - yes
     - URL of the calendar for new events
   * - ``date_format``
     - yes
     - ``ymd``, ``dmy``, ``dmy_dot`` or ``mdy``
   * - ``time_format``
     - yes
     - ``24`` or ``12``
   * - ``weekstart``
     - yes
     - ``0`` (Sunday) or ``1`` (Monday)
   * - ``show_week_nb``
     - yes
     - Boolean
   * - ``show_now_indicator``, ``hide_weekends``
     - no
     - Boolean, default ``false``
   * - ``list_days``
     - no
     - Days in the list view: ``7``, ``14`` or ``31``
   * - ``default_view``
     - no
     - ``month``, ``week``, ``day`` or ``list``
   * - ``color_scheme``
     - no
     - ``light``, ``dark`` or ``auto``
   * - ``working_hours_start``, ``working_hours_end``
     - no
     - Full hours, start ``00:00`` to ``23:00``, end ``01:00`` to ``24:00``. Empty for
       the whole day
   * - ``default_reminder_count``, ``default_reminder_unit``
     - no
     - Reminder for new events, unit ``minutes``, ``hours``, ``days``, ``weeks`` or
       ``months``. Empty count for no reminder

Response: envelope, ``data`` is ``{"preferences": {…}}`` with the saved preferences.

Other endpoints
---------------

``GET /config``
***************

Returns the settings the frontend needs to start. The calendar page only carries the URL of
this endpoint (``data-config-url`` on ``#calendar_view``), it has no inline configuration.

Response:

.. code-block:: json

   {
     "config": {
       "base_url": "/agendav",
       "base_app_url": "/agendav/",
       "agendav_version": "3.4.0",
       "enable_calendar_sharing": true,
       "enable_calendar_subscriptions": true,
       "calendar_colors": ["#3a89c9", "#ff0000"],
       "default_calendar_color": "#3a89c9",
       "show_public_caldav_url": false,
       "calendar_read_only": false,
       "calendar_kiosk_mode": false,
       "calendar_start_hidden": false
     },
     "preferences": {"language": "de", "timezone": "Europe/Berlin", "…": "…"},
     "csrf_token": "…"
   }

- ``config`` is a fixed list of site settings. ``caldav_public_base_url`` is added when
  ``caldav.publicurls`` is enabled. Secrets like autologin credentials are never included
- ``preferences`` are the preferences of the current user
- ``csrf_token`` is the token for ``POST`` requests (see CSRF protection)

``GET /translations/{language}``
********************************

Returns the UI messages of one language, for example ``/translations/de_DE``. Messages
that are not translated into that language are returned in English. Unknown languages
return ``404`` with an empty body.

The messages are the same for every user, so this endpoint needs no login. The response
is cached by the browser (``Cache-Control: public, max-age=31536000, immutable``). During
development, use a hard reload or disable the browser cache to see changed messages.

The frontend loads ``translations/<preferences.language>?v=<agendav_version>`` after
``GET /config``. The version parameter loads new messages after an update. On ``404`` the
frontend loads ``translations/en`` instead.

Response:

.. code-block:: json

   {"labels.calendar": "Kalender", "labels.save": "Speichern", "…": "…"}

``GET /principals``
*******************

Searches principals for calendar sharing.

Parameters: ``term`` (search text, cut to 64 characters).

Response: a JSON array, empty if ``term`` is missing:

.. code-block:: json

   [{"url": "/dav.php/principals/demo/", "displayname": "Demo", "email": "demo@example.com"}]

``GET /keepalive``
******************

Keeps the session alive. Empty ``200`` response.
