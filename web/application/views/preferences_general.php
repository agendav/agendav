<?php

$languages = array();
foreach ($this->config->item('lang_rels') as $langKey => $val)
{
    $languages[$langKey] = $val['name'];
}

echo formelement(
		$this->i18n->_('labels', 'language'),
		form_dropdown('language', $languages,
			$this->i18n->getCurrent(),
			'class="medium"'));

echo formelement(
		$this->i18n->_('labels', 'first_day'),
		form_dropdown('prefs_firstday', array(
                0 => $this->i18n->_('labels', 'sunday'),
                1 => $this->i18n->_('labels', 'monday'),
                2 => $this->i18n->_('labels', 'tuesday'),
                3 => $this->i18n->_('labels', 'wednesday'),
                4 => $this->i18n->_('labels', 'thursday'),
                5 => $this->i18n->_('labels', 'friday'),
                6 => $this->i18n->_('labels', 'saturday')
            ),
			$prefs_firstday,
			'class="medium"'));

$zones = array();
$allZones = timezone_abbreviations_list();
foreach($allZones as $zoneLabel => $zoneData) {
    foreach($zoneData as $zone) {
        $zones[$zone['timezone_id']] = $zone['timezone_id'];
    }
}
$zones = array_unique($zones);
sort($zones);

echo formelement(
		$this->i18n->_('labels', 'timezone'),
		form_dropdown('timezone', array_combine($zones, $zones),
			$timezone,
			'class="medium"'));

?>
