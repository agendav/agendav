<?php
namespace AgenDAV\Console\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SessionsClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('sessions:clear')
            ->setDescription('Removes all user sessions');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $db = $this->getHelper('db');
        $connection = $db->getConnection();

        $sql = 'DELETE FROM sessions';
        $stmt = $connection->prepare($sql);
        $stmt->execute();
    }
}
