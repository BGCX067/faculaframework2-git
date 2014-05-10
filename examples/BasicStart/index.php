<?php

/**
 * Framework Demo: Project entry file
 *
 * Facula Framework 2014 (C) Rain Lee
 *
 * Facula Framework is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, version 3.
 *
 * Facula Framework is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author     Rain Lee <raincious@gmail.com>
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

/**
 * Check if example has disabled
 */
if (file_exists('..' . DIRECTORY_SEPARATOR . 'Lock')) {
    exit('Example disabled. Remove Lock file to enable.');
}

/**
 * Require the framework
 */
require(
    '..'
    . DIRECTORY_SEPARATOR
    . '..'
    . DIRECTORY_SEPARATOR
    . 'Bootstrap.php'
);

/**
 * Require the framework configuration file
 */
require(
    'privated'
    . DIRECTORY_SEPARATOR
    . 'Configurations'
    . DIRECTORY_SEPARATOR
    . 'Primary.php'
);

/**
 * Wake up the framework using the configuration
 */
Facula\Framework::run($cfg);
