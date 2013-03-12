<?php
namespace AgenDAV\CalDAV;

use AgenDAV\Data\SinglePermission;
use AgenDAV\Data\Permissions;

/*
 * Copyright 2013 Jorge López Pérez <jorge@adobo.org>
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

class ACLGenerator implements IACLGenerator
{

    /**
     * Configured permissions
     *
     * @var AgenDAV\Permissions
     */
    private $permissions;

    /**
     * Current grants
     *
     * @var Array
     */
    private $grants;

    public function __construct(Permissions $p)
    {
        $this->permissions = $p;
        $this->grants = array();
    }

    /**
     * Adds a new grant to this ACL
     *
     * @param string $principal Principal href
     * @param string $profile Profile (owner, read, read_write)
     * @return boolean Grant failed
     */
    public function addGrant($principal, $profile)
    {
        $this->grants[$principal] = $profile;
    }

    /**
     * Builds ACL for current added users
     *
     * @return string XML DAV ACL
     */
    public function buildACL()
    {
        $xml = new \DomDocument('1.0', 'utf-8');
        $acl = $xml->createElementNS('DAV:', 'acl');
        $acl->setAttributeNS(
            'http://www.w3.org/2000/xmlns/',
            'xmlns:C',
            'urn:ietf:params:xml:ns:caldav'
        );
        $xml->appendChild($acl);

        $default = $this->permissions->getDefault();
        $this->addACE($xml, 'other', null, $default);
        foreach ($this->grants as $username => $profile) {
            $perms = $this->permissions->getProfile($profile);
            $this->addACE($xml, $profile, $username, $perms);
        }

        // Debug purposes only
        //$xml->formatOutput = true;
        return $xml->saveXML();
    }

    protected function addACE($doc, $profile, $username, $perms)
    {
        $acl = $doc->documentElement;

        $ace = $doc->createElement('ace');
        $principal = $doc->createElement('principal');
        if ($profile === 'owner') {
            $property = $doc->createElement('property');
            $property->appendChild($doc->createElement('owner'));
            $principal->appendChild($property);
        } elseif ($username === null) {
            $principal->appendChild($doc->createElement('authenticated'));
        } else {
            $principal->appendChild($doc->createElement('href', $username));
        }
        $ace->appendChild($principal);
        $acl->appendChild($ace);

        $grant = $doc->createElement('grant');
        $ace->appendChild($grant);
        foreach($perms as $p) {
            $privilege = $doc->createElement('privilege');
            $grant->appendChild($privilege);
            $privilege->appendChild(
                $doc->createElementNS(
                    $p->getNameSpace(),
                    $p->getName()
                )
            );
        }
    }
}
