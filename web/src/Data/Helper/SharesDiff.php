<?php

namespace AgenDAV\Data\Helper;

/*
 * Copyright 2016 Jorge López Pérez <jorge@adobo.org>
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

use AgenDAV\Data\Share;

/**
 * Support class used to decide which existing Shares should be removed,
 * which of them should be updated, and what new Shares should be created
 */

class SharesDiff
{
    /** @var AgenDAV\Data\Share[] */
    protected $keep;

    /** @var AgenDAV\Data\Share[] */
    protected $remove;

    /** @var AgenDAV\Data\Share[] */
    protected $current_shares;

    /**
     * Creates empty lists on $keep and $remove
     *
     * @param AgenDAV\Data\Share[] $current_shares Current list of shares
     */
    public function __construct(Array $current_shares)
    {
        $this->current_shares = $current_shares;
        $this->keep = [];
        $this->remove = [];
    }

    /**
     * Loops over the passed input, and stores a list of Shares that should be
     * kept, and those that are not present on input anymore, so they should
     * be removed.
     *
     * @param AgenDAV\Data\Share[] $input
     * @return void
     */
    public function decide(Array $input)
    {
        $pending_inputs = array_keys($input);

        foreach ($this->current_shares as $share) {
            $found = false;
            $i = 0;
            $count_pending_inputs = count($pending_inputs);

            while (!$found && $i < $count_pending_inputs) {
                $index = $pending_inputs[$i];

                if ($input[$index]->getWith() === $share->getWith()) {
                    $found = true;
                    $share->setWritePermission($input[$index]->isWritable());
                    $this->keep[] = $share;
                    unset($pending_inputs[$i]);
                }

                $i++;
            }

            if (!$found) {
                $this->remove[] = $share;
            }

            // Re-index array, as we removed an index
            $pending_inputs = array_values($pending_inputs);
        }

        // Keep new shares too
        foreach ($pending_inputs as $index) {
            $this->keep[] = $input[$index];
        }
    }

    /**
     * Get existing shares that need to be kept and also new ones
     *
     * @return AgenDAV\Data\Share[]
     */
    public function getKeptShares()
    {
        return $this->keep;
    }

    /**
     * Get shares that have to be deleted
     *
     * @return AgenDAV\Data\Share[]
     */
    public function getMarkedForRemoval()
    {
        return $this->remove;
    }
}
