<?php
include("./always.php");
include("DAViCalSession.php");
$session->LoginRequired();

include("interactive-page.php");

$c->page_title = "DAViCal CalDAV Server - Configuration Help";
include("page-header.php");

$wiki_help = '';
if ( isset($_SERVER['HTTP_REFERER']) ) {
  $wiki_help = preg_replace('#^.*/#', '', $_SERVER['HTTP_REFERER']);
  $wiki_help = preg_replace('#\.php.*$#', '', $wiki_help);
  $wiki_help = 'w/Help/'.$wiki_help;
}

$content = translate('<h1>Help</h1>
<p>For initial help you should visit the <a href="http://www.davical.org/" target="_blank">DAViCal Home Page</a> or take
a look at the <a href="http://wiki.davical.org/'.$wiki_help.'" target="_blank">DAViCal Wiki</a>.</p>
<p>If you can\'t find the answers there, visit us on <a href="http://wikipedia.org/wiki/Internet_Relay_Chat" target="_blank">IRC</a> in
the <b>#davical</b> channel on <a href="http://www.oftc.net/" target="_blank">irc.oftc.net</a>,
or send a question to the <a href="http://lists.sourceforge.net/mailman/listinfo/rscds-general" target="_blank">DAViCal Users mailing list</a>.</p>
<p>The <a href="http://sourceforge.net/mailarchive/forum.php?forum_id=8348" title="DAViCal Users Mailing List" target="_blank">mailing list
archives can be helpful too.</p>');

echo $content;

include("page-footer.php");

