<?php
include("page-header.php");

  echo <<<EOBODY
<h1>DAViCal Not Configured</h1>
<h2>The Bad News</h2>
<p>There is no configuration file present in <b>/etc/davical/$_SERVER[SERVER_NAME]-conf.php</b> so
   your installation is not fully set up.</p>
<h2>The Good News</h2>
<p>Well, you're seeing this! At least you have DAViCal <i>installed</i> :-) You also have Apache and PHP working
   and so really you are well on the road to success!</p>
<h2>The Dubious News</h2>
<p>You could try and <a href="http://$_SERVER[SERVER_NAME]/docs/rscds/configuring.html">click here</a> and
   see if that enlightens you at all.  Odds are it's a fairly broken link, but it might work sooner
   or later so keep downloading new versions and trying again.  Or make some guesses.  Or bug Andrew :-)</p>
<h2>The Really Basic Help</h2>
<p>The configuration file should look something like this:</p>
<pre>
&lt;?php
//  \$c->domain_name  = 'davical.example.com';
//  \$c->sysabbr     = 'davical';
//  \$c->system_name = 'DAViCal CalDAV Server';

  \$c->admin_email  = 'admin@example.com';
  \$c->pg_connect[] = 'dbname=davical user=davical_app';

</pre>
<p>The only really <em>essential</em> thing there is that connect string for the database, although
configuring someone for the admin e-mail is a really good idea.</p>
EOBODY;

include("page-footer.php");
