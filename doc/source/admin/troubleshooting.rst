Troubleshooting AgenDAV
=======================

If you are having problems with AgenDAV, do the following changes to enable
more verbose debugging:

Edit ``web/config/config.php`` and add the value ``INTERNALS`` inside
``show_in_log`` variable. For example::

  $config['show_in_log']= array('ERROR','INFO','AUTHERR', 'AUTHOK','INTERNALS');

Check AgenDAV logs (make sure you have Check AgenDAV logs (make sure you
have a valid path configured in :confval:`log_path` and the user which runs
the webserver has writing access to it) and your webserver logs.
