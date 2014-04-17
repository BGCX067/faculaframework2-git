<?php

/**
 * Errors of PDO function core
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
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Error\Core;

use Facula\Base\Prototype\Error as Base;

/**
 * Errors for PDO function core
 */
class PDO extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'TABLE_DECLARATION_NEEDED' => '
            Specified database select method require Table declaration,
            which is missing for database No. %d.
        ',

        'OPERATION_DECLARATION_NEEDED' => '
            Specified database select method require Operation declaration,
            which is missing for database No. %d.
        ',

        'DRIVER_UNSUPPORTED' => '
            Sorry, specified driver %s for database No. %d is not supported on this server.
            You can only use following: %s.
        ',

        'DRIVER_DECLARATION_NEEDED' => '
            You must specify the PDO driver for database No. %d,
            from one of following drivers: %s
        ',

        'PDO_UNSUPPORTED' => '
            PHP Data Object (PDO) interface not found. This server may not support it.
        ',

        'TABLE_SPECIFICATION_NEEDED' => '
            You need to specify the Table you want to operate with before perform this action.
        ',

        'OPERATION_SPECIFICATION_NEEDED' => '
            You need to specify the Operation you want to before perform this action.
        ',

        'TABLEOPERATION_SPECIFICATION_NEEDED' => '
            You need to specify the target Table and Operation before performing this action.
        ',

        'UNKNOWN_SELECTMETHOD_SPECIFIED' => '
            %s is an unknown select method. Please correct it in your configuration.
        ',

        'DATABASE_ALL_UNAVAILABLE' => '
            All specified databases is unavailable.
            Last error: %s;
        ',

        'DATABASE_UNAVAILABLE' => '
            Database No. %d is unavailable. Returning error: %s
        ',
    );
}
