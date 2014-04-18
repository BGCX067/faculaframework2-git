<?php

/**
 * Errors of Operator Factory
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

namespace Facula\Base\Error\Tool\Paging;

use Facula\Base\Prototype\Error as Base;

/**
 * Errors for Operator factory
 */
class Compiler extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'TAG_PARSER_FAILED' => '
            Parser of tag "%s" has failed on parsing content: %s.
        ',

        'TAG_UNCLOSED' => '
            Tag need to be close: "%s".
        ',

        'TAG_INCLUDE_EMPTY' => '
            Included template is empty: %s.
        ',

        'TAG_INCLUDE_NOTFOUND' => '
            Trying to include template "%s", not it can\'t be found.
        ',

        'TAG_LANGUAGE_NOTFOUND' => '
            Trying to include template "%s", not it can\'t be found.
        ',

        'TAG_PAGER_FORMAT_INVALID' => '
            Tag of pager contains invalid format.
        ',

        'TAG_PAGER_FORMAT_INVALID' => '
            Tag of pager contains invalid format.
        ',

        'VARIABLE_NAME_INVALID' => '
            Variable name "%s" is not valid.
        ',

        'VARIABLE_MUST_DEFINED' => '
            Variable must be defined.
        ',

        'LANG_DATE_MISSED' => '
            Missing formats for "%s" in language.
            Following format need to be declared: $s.
        ',

        'LANG_FRIENDLYTIME_MISSED' => '
            Missing formats for "%s" in language.
            Following format need to be declared: $s.
        ',

        'LANG_BYTE_MISSED' => '
            Missing formats for "%s" in language.
            Following format need to be declared: $s.
        ',

        'LANG_FRIENDLYNUMBER_MISSED' => '
            Missing formats for "%s" in language.
            Following format need to be declared: $s.
        ',
    );
}
