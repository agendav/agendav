<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

/*
 * Copyright 2011-2014 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */


/**
 * This class will create and cache DateTimeZone objects
 */

class Timezonemanager {
    private $timezones;

    function __construct() {
        $this->timezones = array();
    }

    /**
     * Returns a timezone object, caching it
     *
     * @param string $name Timezone name
     * @return \DateTimeZone
     */
    public function getTz($name) {
        return (isset($this->timezones[$name])) ?
            $this->timezones[$name] :
            $this->createTz($name);
    }

    /**
     * Returns an array of timezone names
     *
     * @return Array (key = value)
     */
    public function getAvailableTimezoneIdentifiers()
    {
        $timezones = \DateTimeZone::listIdentifiers();

        // Builds a new array using timezone identifiers as both key and value
        return array_combine($timezones, $timezones);
    }

    /**
     * Creates a DateTimeZone object and caches it
     *
     * @param string $name Timezone name
     * @return \DateTimeZone
     */
    private function createTz($name) {
        try {
            $tz = new DateTimeZone($name);
        } catch (Exception $e) {
            log_message("ERROR","Invalid timezone: '".$name."'");

            //try looking up the timezone using the Olson names
            if($this->lookupTz($name)!= false){
                $newZoneName = $this->lookupTz($name);
                log_message("DEBUG","Translated timezone to: ".$newZoneName);
                try {
                    $tz = new DateTimeZone($newZoneName);
                }
                catch (Exception $e) {
                    return FALSE;
                }
            }
            else{
                return FALSE;
            }
        }

        $this->timezones[$name] = $tz;
        return $tz;
    }

    private function lookupTz($name)
    {
        //Time zone lookup obtained from: http://zimbra.imladris.sk/download/src/HELIX-720.fbsd/ZimbraServer/conf/tz/windows-names
        $tzLookup = array(
            "(UTC-12.00) International Date Line West" => "Etc/GMT+12",
            "(GMT-12.00) International Date Line West" => "Etc/GMT+12",
            "(UTC-11.00) Coordinated Universal Time-11" => "Etc/GMT+11",
            "(GMT-11.00) Coordinated Universal Time-11" => "Etc/GMT+11",
            "(UTC-11.00) Midway Island / Samoa" => "Pacific/Midway",
            "(GMT-11.00) Midway Island / Samoa" => "Pacific/Midway",
            "(UTC-11.00) Samoa" => "Pacific/Midway",
            "(GMT-11.00) Samoa" => "Pacific/Midway",
            "(UTC-10.00) Hawaii" => "Pacific/Honolulu",
            "(GMT-10.00) Hawaii" => "Pacific/Honolulu",
            "(UTC-09.00) Alaska" => "America/Anchorage",
            "(GMT-09.00) Alaska" => "America/Anchorage",
            "(UTC-08.00) Baja California" => "America/Tijuana",
            "(GMT-08.00) Baja California" => "America/Tijuana",
            "(UTC-08.00) Pacific Time (US & Canada)" => "America/Los_Angeles",
            "(GMT-08.00) Pacific Time (US & Canada)" => "America/Los_Angeles",
            "(UTC-08.00) Pacific Time (US & Canada) / Tijuana" => "America/Los_Angeles",
            "(GMT-08.00) Pacific Time (US & Canada) / Tijuana" => "America/Los_Angeles",
            "(UTC-08.00) Tijuana / Baja California" => "America/Tijuana",
            "(GMT-08.00) Tijuana / Baja California" => "America/Tijuana",
            "(UTC-07.00) Arizona" => "America/Phoenix",
            "(GMT-07.00) Arizona" => "America/Phoenix",
            "(UTC-07.00) Chihuahua / La Paz / Mazatlan" => "America/Chihuahua",
            "(GMT-07.00) Chihuahua / La Paz / Mazatlan" => "America/Chihuahua",
            "(UTC-07.00) Chihuahua / La Paz / Mazatlan - New" => "America/Chihuahua",
            "(GMT-07.00) Chihuahua / La Paz / Mazatlan - New" => "America/Chihuahua",
            "(UTC-07.00) Chihuahua / La Paz / Mazatlan - Old" => "America/Chihuahua",
            "(GMT-07.00) Chihuahua / La Paz / Mazatlan - Old" => "America/Chihuahua",
            "(UTC-07.00) Mountain Time (US & Canada)" => "America/Denver",
            "(GMT-07.00) Mountain Time (US & Canada)" => "America/Denver",
            "(UTC-06.00) Central America" => "America/Guatemala",
            "(GMT-06.00) Central America" => "America/Guatemala",
            "(UTC-06.00) Central Time (US & Canada)" => "America/Chicago",
            "(GMT-06.00) Central Time (US & Canada)" => "America/Chicago",
            "(UTC-06.00) Guadalajara / Mexico City / Monterrey" => "America/Mexico_City",
            "(GMT-06.00) Guadalajara / Mexico City / Monterrey" => "America/Mexico_City",
            "(UTC-06.00) Guadalajara / Mexico City / Monterrey - New" => "America/Mexico_City",
            "(GMT-06.00) Guadalajara / Mexico City / Monterrey - New" => "America/Mexico_City",
            "(UTC-06.00) Guadalajara / Mexico City / Monterrey - Old" => "America/Mexico_City",
            "(GMT-06.00) Guadalajara / Mexico City / Monterrey - Old" => "America/Mexico_City",
            "(UTC-06.00) Saskatchewan" => "America/Regina",
            "(GMT-06.00) Saskatchewan" => "America/Regina",
            "(UTC-05.00) Bogota / Lima / Quito / Rio Branco" => "America/Bogota",
            "(GMT-05.00) Bogota / Lima / Quito / Rio Branco" => "America/Bogota",
            "(UTC-05.00) Bogota / Lima / Quito" => "America/Bogota",
            "(GMT-05.00) Bogota / Lima / Quito" => "America/Bogota",
            "(UTC-05.00) Eastern Time (US & Canada)" => "America/New_York",
            "(GMT-05.00) Eastern Time (US & Canada)" => "America/New_York",
            "(UTC-05.00) Indiana (East)" => "America/Indiana/Indianapolis",
            "(GMT-05.00) Indiana (East)" => "America/Indiana/Indianapolis",
            "(UTC-04.30) Caracas" => "America/Caracas",
            "(GMT-04.30) Caracas" => "America/Caracas",
            "(UTC-04.00) Asuncion" => "America/Asuncion",
            "(GMT-04.00) Asuncion" => "America/Asuncion",
            "(UTC-04.00) Atlantic Time (Canada)" => "America/Halifax",
            "(GMT-04.00) Atlantic Time (Canada)" => "America/Halifax",
            "(UTC-04.00) Cuiaba" => "America/Cuiaba",
            "(GMT-04.00) Cuiaba" => "America/Cuiaba",
            "(UTC-04.00) Georgetown / La Paz / Manaus / San Juan" => "America/Guyana",
            "(GMT-04.00) Georgetown / La Paz / Manaus / San Juan" => "America/Guyana",
            "(UTC-04.00) Georgetown" => "America/Guyana",
            "(GMT-04.00) Georgetown" => "America/Guyana",
            "(UTC-04.00) La Paz" => "America/La_Paz",
            "(GMT-04.00) La Paz" => "America/La_Paz",
            "(UTC-04.00) Caracas / La Paz" => "America/La_Paz",
            "(GMT-04.00) Caracas / La Paz" => "America/La_Paz",
            "(UTC-04.00) Manaus" => "America/Manaus",
            "(GMT-04.00) Manaus" => "America/Manaus",
            "(UTC-04.00) Santiago" => "America/Santiago",
            "(GMT-04.00) Santiago" => "America/Santiago",
            "(UTC-03.30) Newfoundland" => "America/St_Johns",
            "(GMT-03.30) Newfoundland" => "America/St_Johns",
            "(UTC-03.00) Brasilia" => "America/Sao_Paulo",
            "(GMT-03.00) Brasilia" => "America/Sao_Paulo",
            "(UTC-03.00) Buenos Aires" => "America/Argentina/Buenos_Aires",
            "(GMT-03.00) Buenos Aires" => "America/Argentina/Buenos_Aires",
            "(UTC-03.00) Buenos Aires / Georgetown" => "America/Argentina/Buenos_Aires",
            "(GMT-03.00) Buenos Aires / Georgetown" => "America/Argentina/Buenos_Aires",
            "(UTC-03.00) Cayenne / Fortaleza" => "America/Cayenne",
            "(GMT-03.00) Cayenne / Fortaleza" => "America/Cayenne",
            "(UTC-03.00) Greenland" => "America/Godthab",
            "(GMT-03.00) Greenland" => "America/Godthab",
            "(UTC-03.00) Montevideo" => "America/Montevideo",
            "(GMT-03.00) Montevideo" => "America/Montevideo",
            "(UTC-02.00) Coordinated Universal Time-02" => "Etc/GMT+2",
            "(GMT-02.00) Coordinated Universal Time-02" => "Etc/GMT+2",
            "(UTC-02.00) Mid-Atlantic" => "Atlantic/South_Georgia",
            "(GMT-02.00) Mid-Atlantic" => "Atlantic/South_Georgia",
            "(UTC-01.00) Azores" => "Atlantic/Azores",
            "(GMT-01.00) Azores" => "Atlantic/Azores",
            "(UTC-01.00) Cape Verde Is." => "Atlantic/Cape_Verde",
            "(GMT-01.00) Cape Verde Is." => "Atlantic/Cape_Verde",
            "(UTC) Casablanca" => "Africa/Casablanca",
            "(GMT) Casablanca" => "Africa/Casablanca",
            "(UTC) Coordinated Universal Time" => "UTC",
            "(GMT) Coordinated Universal Time" => "UTC",
            "(UTC) Dublin / Edinburgh / Lisbon / London" => "Europe/London",
            "(GMT) Dublin / Edinburgh / Lisbon / London" => "Europe/London",
            "(UTC) Greenwich Mean Time - Dublin / Edinburgh / Lisbon / London" => "Europe/London",
            "(GMT) Greenwich Mean Time - Dublin / Edinburgh / Lisbon / London" => "Europe/London",
            "(UTC) Monrovia / Reykjavik" => "Africa/Monrovia",
            "(GMT) Monrovia / Reykjavik" => "Africa/Monrovia",
            "(UTC) Casablanca / Monrovia / Reykjavik" => "Africa/Monrovia",
            "(GMT) Casablanca / Monrovia / Reykjavik" => "Africa/Monrovia",
            "(UTC) Casablanca / Monrovia" => "Africa/Monrovia",
            "(GMT) Casablanca / Monrovia" => "Africa/Monrovia",
            "(UTC+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna" => "Europe/Berlin",
            "(GMT+01.00) Amsterdam / Berlin / Bern / Rome / Stockholm / Vienna" => "Europe/Berlin",
            "(UTC+01.00) Belgrade / Bratislava / Budapest / Ljubljana / Prague" => "Europe/Belgrade",
            "(GMT+01.00) Belgrade / Bratislava / Budapest / Ljubljana / Prague" => "Europe/Belgrade",
            "(UTC+01.00) Brussels / Copenhagen / Madrid / Paris" => "Europe/Brussels",
            "(GMT+01.00) Brussels / Copenhagen / Madrid / Paris" => "Europe/Brussels",
            "(UTC+01.00) Sarajevo / Skopje / Warsaw / Zagreb" => "Europe/Warsaw",
            "(GMT+01.00) Sarajevo / Skopje / Warsaw / Zagreb" => "Europe/Warsaw",
            "(UTC+01.00) West Central Africa" => "Africa/Algiers",
            "(GMT+01.00) West Central Africa" => "Africa/Algiers",
            "(UTC+01.00) Windhoek" => "Africa/Windhoek",
            "(GMT+01.00) Windhoek" => "Africa/Windhoek",
            "(UTC+02.00) Windhoek" => "Africa/Windhoek",
            "(GMT+02.00) Windhoek" => "Africa/Windhoek",
            "(UTC+02.00) Amman" => "Asia/Amman",
            "(GMT+02.00) Amman" => "Asia/Amman",
            "(UTC+02.00) Athens / Bucharest" => "Europe/Athens",
            "(GMT+02.00) Athens / Bucharest" => "Europe/Athens",
            "(UTC+02.00) Athens / Bucharest / Istanbul" => "Europe/Athens",
            "(GMT+02.00) Athens / Bucharest / Istanbul" => "Europe/Athens",
            "(UTC+02.00) Athens / Beirut / Istanbul / Minsk" => "Europe/Athens",
            "(GMT+02.00) Athens / Beirut / Istanbul / Minsk" => "Europe/Athens",
            "(UTC+02.00) Bucharest" => "Europe/Athens",
            "(GMT+02.00) Bucharest" => "Europe/Athens",
            "(UTC+02.00) Beirut" => "Asia/Beirut",
            "(GMT+02.00) Beirut" => "Asia/Beirut",
            "(UTC+02.00) Cairo" => "Africa/Cairo",
            "(GMT+02.00) Cairo" => "Africa/Cairo",
            "(UTC+02.00) Damascus" => "Asia/Damascus",
            "(GMT+02.00) Damascus" => "Asia/Damascus",
            "(UTC+02.00) Harare / Pretoria" => "Africa/Harare",
            "(GMT+02.00) Harare / Pretoria" => "Africa/Harare",
            "(UTC+02.00) Helsinki / Kyiv / Riga / Sofia / Tallinn / Vilnius" => "Europe/Helsinki",
            "(GMT+02.00) Helsinki / Kyiv / Riga / Sofia / Tallinn / Vilnius" => "Europe/Helsinki",
            "(UTC+02.00) Istanbul" => "Europe/Istanbul",
            "(GMT+02.00) Istanbul" => "Europe/Istanbul",
            "(UTC+02.00) Jerusalem" => "Asia/Jerusalem",
            "(GMT+02.00) Jerusalem" => "Asia/Jerusalem",
            "(UTC+02.00) Minsk" => "Europe/Minsk",
            "(GMT+02.00) Minsk" => "Europe/Minsk",
            "(UTC+03.00) Baghdad" => "Asia/Baghdad",
            "(GMT+03.00) Baghdad" => "Asia/Baghdad",
            "(UTC+03.00) Kaliningrad" => "Europe/Kaliningrad",
            "(GMT+03.00) Kaliningrad" => "Europe/Kaliningrad",
            "(UTC+03.00) Kuwait / Riyadh" => "Asia/Kuwait",
            "(GMT+03.00) Kuwait / Riyadh" => "Asia/Kuwait",
            "(UTC+03.00) Moscow / St. Petersburg / Volgograd" => "Europe/Moscow",
            "(GMT+03.00) Moscow / St. Petersburg / Volgograd" => "Europe/Moscow",
            "(UTC+03.00) Nairobi" => "Africa/Nairobi",
            "(GMT+03.00) Nairobi" => "Africa/Nairobi",
            "(UTC+03.00) Tbilisi" => "Asia/Tbilisi",
            "(GMT+03.00) Tbilisi" => "Asia/Tbilisi",
            "(UTC+03.30) Tehran" => "Asia/Tehran",
            "(GMT+03.30) Tehran" => "Asia/Tehran",
            "(UTC+04.00) Abu Dhabi / Muscat" => "Asia/Muscat",
            "(GMT+04.00) Abu Dhabi / Muscat" => "Asia/Muscat",
            "(UTC+04.00) Baku" => "Asia/Baku",
            "(GMT+04.00) Baku" => "Asia/Baku",
            "(UTC+04.00) Baku / Tbilisi / Yerevan" => "Asia/Baku",
            "(GMT+04.00) Baku / Tbilisi / Yerevan" => "Asia/Baku",
            "(UTC+04.00) Moscow / St. Petersburg / Volgograd" => "Europe/Moscow",
            "(GMT+04.00) Moscow / St. Petersburg / Volgograd" => "Europe/Moscow",
            "(UTC+04.00) Caucasus Standard Time" => "Asia/Tbilisi",
            "(GMT+04.00) Caucasus Standard Time" => "Asia/Tbilisi",
            "(UTC+04.00) Port Louis" => "Indian/Mauritius",
            "(GMT+04.00) Port Louis" => "Indian/Mauritius",
            "(UTC+04.00) Tbilisi" => "Asia/Tbilisi",
            "(GMT+04.00) Tbilisi" => "Asia/Tbilisi",
            "(UTC+04.00) Yerevan" => "Asia/Yerevan",
            "(GMT+04.00) Yerevan" => "Asia/Yerevan",
            "(UTC+04.30) Kabul" => "Asia/Kabul",
            "(GMT+04.30) Kabul" => "Asia/Kabul",
            "(UTC+05.00) Ekaterinburg" => "Asia/Yekaterinburg",
            "(GMT+05.00) Ekaterinburg" => "Asia/Yekaterinburg",
            "(UTC+05.00) Islamabad / Karachi" => "Asia/Karachi",
            "(GMT+05.00) Islamabad / Karachi" => "Asia/Karachi",
            "(UTC+05.00) Islamabad / Karachi / Tashkent" => "Asia/Karachi",
            "(GMT+05.00) Islamabad / Karachi / Tashkent" => "Asia/Karachi",
            "(UTC+05.00) Tashkent" => "Asia/Tashkent",
            "(GMT+05.00) Tashkent" => "Asia/Tashkent",
            "(UTC+05.30) Chennai / Kolkata / Mumbai / New Delhi" => "Asia/Kolkata",
            "(GMT+05.30) Chennai / Kolkata / Mumbai / New Delhi" => "Asia/Kolkata",
            "(UTC+05.30) Sri Jayawardenepura" => "Asia/Colombo",
            "(GMT+05.30) Sri Jayawardenepura" => "Asia/Colombo",
            "(UTC+06.00) Sri Jayawardenepura" => "Asia/Colombo",
            "(GMT+06.00) Sri Jayawardenepura" => "Asia/Colombo",
            "(UTC+05.45) Kathmandu" => "Asia/Katmandu",
            "(GMT+05.45) Kathmandu" => "Asia/Katmandu",
            "(UTC+06.00) Astana" => "Asia/Almaty",
            "(GMT+06.00) Astana" => "Asia/Almaty",
            "(UTC+06.00) Dhaka" => "Asia/Dhaka",
            "(GMT+06.00) Dhaka" => "Asia/Dhaka",
            "(UTC+06.00) Astana / Dhaka" => "Asia/Dhaka",
            "(GMT+06.00) Astana / Dhaka" => "Asia/Dhaka",
            "(UTC+06.00) Novosibirsk" => "Asia/Novosibirsk",
            "(GMT+06.00) Novosibirsk" => "Asia/Novosibirsk",
            "(UTC+06.00) Almaty / Novosibirsk" => "Asia/Novosibirsk",
            "(GMT+06.00) Almaty / Novosibirsk" => "Asia/Novosibirsk",
            "(UTC+06.00) Ekaterinburg" => "Asia/Yekaterinburg",
            "(GMT+06.00) Ekaterinburg" => "Asia/Yekaterinburg",
            "(UTC+06.30) Yangon (Rangoon)" => "Asia/Rangoon",
            "(GMT+06.30) Yangon (Rangoon)" => "Asia/Rangoon",
            "(UTC+06.30) Rangoon" => "Asia/Rangoon",
            "(GMT+06.30) Rangoon" => "Asia/Rangoon",
            "(UTC+07.00) Bangkok / Hanoi / Jakarta" => "Asia/Bangkok",
            "(GMT+07.00) Bangkok / Hanoi / Jakarta" => "Asia/Bangkok",
            "(UTC+07.00) Krasnoyarsk" => "Asia/Krasnoyarsk",
            "(GMT+07.00) Krasnoyarsk" => "Asia/Krasnoyarsk",
            "(UTC+07.00) Novosibirsk" => "Asia/Novosibirsk",
            "(GMT+07.00) Novosibirsk" => "Asia/Novosibirsk",
            "(UTC+08.00) Beijing / Chongqing / Hong Kong / Urumqi" => "Asia/Hong_Kong",
            "(GMT+08.00) Beijing / Chongqing / Hong Kong / Urumqi" => "Asia/Hong_Kong",
            "(UTC+08.00) Irkutsk" => "Asia/Irkutsk",
            "(GMT+08.00) Irkutsk" => "Asia/Irkutsk",
            "(UTC+08.00) Irkutsk / Ulaan Bataar" => "Asia/Irkutsk",
            "(GMT+08.00) Irkutsk / Ulaan Bataar" => "Asia/Irkutsk",
            "(UTC+08.00) Krasnoyarsk" => "Asia/Krasnoyarsk",
            "(GMT+08.00) Krasnoyarsk" => "Asia/Krasnoyarsk",
            "(UTC+08.00) Kuala Lumpur / Singapore" => "Asia/Kuala_Lumpur",
            "(GMT+08.00) Kuala Lumpur / Singapore" => "Asia/Kuala_Lumpur",
            "(UTC+08.00) Perth" => "Australia/Perth",
            "(GMT+08.00) Perth" => "Australia/Perth",
            "(UTC+08.00) Taipei" => "Asia/Taipei",
            "(GMT+08.00) Taipei" => "Asia/Taipei",
            "(UTC+08.00) Ulaanbaatar" => "Asia/Ulaanbaatar",
            "(GMT+08.00) Ulaanbaatar" => "Asia/Ulaanbaatar",
            "(UTC+09.00) Irkutsk" => "Asia/Irkutsk",
            "(GMT+09.00) Irkutsk" => "Asia/Irkutsk",
            "(UTC+09.00) Osaka / Sapporo / Tokyo" => "Asia/Tokyo",
            "(GMT+09.00) Osaka / Sapporo / Tokyo" => "Asia/Tokyo",
            "(UTC+09.00) Seoul" => "Asia/Seoul",
            "(GMT+09.00) Seoul" => "Asia/Seoul",
            "(UTC+09.00) Yakutsk" => "Asia/Yakutsk",
            "(GMT+09.00) Yakutsk" => "Asia/Yakutsk",
            "(UTC+09.30) Adelaide" => "Australia/Adelaide",
            "(GMT+09.30) Adelaide" => "Australia/Adelaide",
            "(UTC+09.30) Darwin" => "Australia/Darwin",
            "(GMT+09.30) Darwin" => "Australia/Darwin",
            "(UTC+10.00) Brisbane" => "Australia/Brisbane",
            "(GMT+10.00) Brisbane" => "Australia/Brisbane",
            "(UTC+10.00) Canberra / Melbourne / Sydney" => "Australia/Sydney",
            "(GMT+10.00) Canberra / Melbourne / Sydney" => "Australia/Sydney",
            "(UTC+10.00) Guam / Port Moresby" => "Pacific/Guam",
            "(GMT+10.00) Guam / Port Moresby" => "Pacific/Guam",
            "(UTC+10.00) Hobart" => "Australia/Hobart",
            "(GMT+10.00) Hobart" => "Australia/Hobart",
            "(UTC+10.00) Yakutsk" => "Asia/Yakutsk",
            "(GMT+10.00) Yakutsk" => "Asia/Yakutsk",
            "(UTC+10.00) Vladivostok" => "Asia/Vladivostok",
            "(GMT+10.00) Vladivostok" => "Asia/Vladivostok",
            "(UTC+11.00) Magadan" => "Asia/Magadan",
            "(GMT+11.00) Magadan" => "Asia/Magadan",
            "(UTC+11.00) Solomon Is. / New Caledonia" => "Pacific/Guadalcanal",
            "(GMT+11.00) Solomon Is. / New Caledonia" => "Pacific/Guadalcanal",
            "(UTC+11.00) Magadan / Solomon Is. / New Caledonia" => "Asia/Magadan",
            "(GMT+11.00) Magadan / Solomon Is. / New Caledonia" => "Asia/Magadan",
            "(UTC+11.00) Vladivostok" => "Asia/Vladivostok",
            "(GMT+11.00) Vladivostok" => "Asia/Vladivostok",
            "(UTC+12.00) Auckland / Wellington" => "Pacific/Auckland",
            "(GMT+12.00) Auckland / Wellington" => "Pacific/Auckland",
            "(UTC+12.00) Coordinated Universal Time+12" => "Etc/GMT-12",
            "(GMT+12.00) Coordinated Universal Time+12" => "Etc/GMT-12",
            "(UTC+12.00) Fiji" => "Pacific/Fiji",
            "(GMT+12.00) Fiji" => "Pacific/Fiji",
            "(UTC+12.00) Fiji / Kamchatka / Marshall Is." => "Pacific/Fiji",
            "(GMT+12.00) Fiji / Kamchatka / Marshall Is." => "Pacific/Fiji",
            "(UTC+12.00) Magadan" => "Asia/Magadan",
            "(GMT+12.00) Magadan" => "Asia/Magadan",
            "(UTC+12.00) Petropavlovsk-Kamchatsky" => "Asia/Kamchatka",
            "(GMT+12.00) Petropavlovsk-Kamchatsky" => "Asia/Kamchatka",
            "(GMT+12.00) Petropavlovsk-Kamchatsky - Old" => "Asia/Kamchatka",
            "(UTC+13.00) Nuku'alofa" => "Pacific/Tongatapu",
            "(GMT+13.00) Nuku'alofa" => "Pacific/Tongatapu"
        );

        if(isset($tzLookup[$name])){
            return $tzLookup[$name];
        }
        else{
            return false;
        }
    }
}
