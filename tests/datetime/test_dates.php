<?php
$a = '4/5/2011 00:00';

$dta = DateTime::createFromFormat('d/m/Y H:i', $a,
		new DateTimeZone('Europe/Madrid'));
$dtb = DateTime::createFromFormat('d/m/Y H:i', $a,
		new DateTimeZone('UTC'));

echo $dta->format('Ymd\THis') . "\n";
echo $dtb->format('Ymd\THis') . "\n";

$dta->setTimeZone(new DateTimeZone('UTC'));

echo $dta->format('Ymd\THis') . "\n";
