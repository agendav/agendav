<?php
if ( $_SERVER['REQUEST_METHOD'] != "GET" && $_SERVER['REQUEST_METHOD'] != "POST" && $_SERVER['REQUEST_METHOD'] != "HEAD" ) {
  /**
  * If the request is not a GET or POST then they must really want caldav.php!
  */
  include("./caldav.php");
  exit;  // Not that it should return from that!
}

include("./always.php");
include("DAViCalSession.php");
$session->LoginRequired();

include("interactive-page.php");
include("page-header.php");

  echo <<<EOBODY
<h1>Administration</h1>
<p>You are logged on as $session->username ($session->fullname)</p>
EOBODY;
?>
<h2>Administration Functions</h2>
<p>The administration of this application should be fairly simple.  You can administer:</p>
<ul>
<li>Users (or Resources or Groups) and the relationships between them</li>
<li>The types of relationships that are available</li>
</ul>
<p><i>There is no ability to view and / or maintain calendars or events from within this administrative interface.</i></p>
<p>To do that you will need to use a CalDAV capable calendaring application such as Evolution, Sunbird, Thunderbird
(with the Lightning extension) or Mulberry.</p>

<h3>Principals: Users, Resources and Groups</h3>
<p>These are the things which may have collections of calendar resources (i.e. calendars).</p>
<p><a href="<?php echo $c->base_url; ?>/admin.php?action=browse&t=principal&type=1">Here is a list of users (maybe :-)</a>.  You can click on any user to see the full detail
for that person (or group or resource - but from now we'll just call them users).</p>
<p>The primary differences between them are as follows:</p>
<ul>
<li>Users will probably have calendars, and are likely to also log on to the system.</li>
<li>Resources do have calendars, but they will not usually log on.</li>
<li>Groups provide an intermediate linking to minimise administration overhead.  They might not have calendars, and they will not usually log on.</li>
</ul>

<h3>Groups &amp; Grants</h3>
<ul>
<li>Grants specify the access rights to a collection or a principal</li>
<li>Groups allow those granted rights to be assigned to a set of many principals in one action</li>
<li>Groups may be members of other groups, but complex nesting will hurt system performance</li>
</ul>

<h2>Configuring Calendar Clients for DAViCal</h2>
<p>The <a href="http://rscds.sourceforge.net/clients.php">DAViCal client setup page on sourceforge</a> have information on how
to configure Evolution, Sunbird, Lightning and Mulberry to use remotely hosted calendars.</p>
<p>The administrative interface has no facility for viewing or modifying calendar data.</p>

<h2>Configuring DAViCal</h2>
<p>If you can read this then things must be mostly working already.</p>
<p>The <a href="http://rscds.sourceforge.net/installation.php">DAViCal installation page</a> on sourceforge has
some further information on how to install and configure this application.</p>

<?php
include("page-footer.php");
