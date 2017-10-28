<?php
namespace AgenDAV\Data;

/*
 * Copyright (C) Jorge López Pérez <jorge@adobo.org>
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

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="principals")
 */
class Principal
{
    /**
     * @Id
     * @Column(type="string")
     */
    private $url;

    /** @Column(type="string") */
    private $displayname;

    /** @Column(type="string") */
    private $email;

    // Property names
    const DISPLAYNAME = '{DAV:}displayname';

    /**
     * Builds a new Principal
     *
     * @param string $url
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /*
     * Getter for URL
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Getter for displayname
     *
     * @return string
     */
    public function getDisplayName()
    {
        if ($this->displayname === null) {
            return $this->url;
        }

        return $this->displayname;
    }

    /**
     * Setter for displayname
     *
     * @param string $displayname
     */
    public function setDisplayName($displayname)
    {
        $this->displayname = $displayname;
    }

    /**
     * Getter for email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Setter for email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }
}
