<?php

/**
 * Errors of Template function core
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
 * Errors for Template function core
 */
class Template extends Base
{
    /** Error code to error message */
    protected static $errorStrings = array(
        'REWARMING_NOTALLOWED' => '
            You are attempt to re-warming Template function core.
            Which is not allowed due to data integrity reason.
        ',

        'PATH_TEMPLATEPOOL_NOTFOUND' => '
            The path declared in "TemplatePool" setting must be defined and existed.
        ',

        'PATH_COMPILEDTEMPLATE_NOTFOUND' => '
            The path declared in "CompiledTemplate" must be defined and existed.
        ',

        'PATH_CACHEDTEMPLATE_NOTFOUND' => '
            The path declared in "CachePath" must be defined and existed.
        ',

        'MESSAGE_NOCONTENT' => '
            Inserted message is empty.
        ',

        'CACHE_DISABLE' => '
            Template cache has been disabled. To use this function, please declare the "CacheTemplate" to be true.
        ',

        'TEMPLATE_NOTFOUND' => '
            The template "%s" can\'t be found in template declaration.
        ',

        'TEMPLATE_CONFLICT' => '
            The template file %s is conflicted with %s.
        ',

        'TEMPLATE_CONFLICT_SET' => '
            The template file %s in set "%s" is conflicted with %s.
        ',

        'TEMPLATE_IMPORTING_EXISTED' => '
            The importing template "%s" for set "%s" is conflicting with existing one.
            Please use other name or set to import.
        ',

        'LANGUAGE_IMPORTING_UNSUPPORTED' => '
            The importing language "%s" is not supported.
            This application currently only support following language codes: %s.
        ',

        'RENDER_FAILED' => '
            Render has failed on rendering file %s.
        ',

        'COMPILER_FAILED' => '
            Compiler has failed on compiling file %s.
        ',

        'COMPILE_FILE_EMPTY' => '
            Trying to open file %s for compile, but the file seems contains nothing.
        ',

        'COMPILE_FILE_NOTFOUND' => '
            Trying to open file %s for compile, but the file seems not readable.
        ',

        'LANGUAGE_FILE_NOTFOUND' => '
            Trying to load language file %s, but the file seems not existed.
        ',

        'LANGUAGE_DEFAULT_FILE_NOTFOUND' => '
            Trying to load default language file %s, but the file seems not existed.
        ',

        'LANGUAGE_KEY_ALREADY_DECLARED' => '
            Trying declare language key "%s", but it already assigned with value "%s" elsewhere.
        ',

        'RENDER_CLASS_NOTFOUND' => '
            Template render class "%s" was not found.
        ',

        'RENDER_INTERFACE_INVALID' => '
            Template render class "%s" must implement interface "%s".
        ',

        'COMPILER_CLASS_NOTFOUND' => '
            Template compiler class "%s" was not found.
        ',

        'COMPILER_INTERFACE_INVALID' => '
            Template compiler class "%s" must implement interface "%s".
        ',

        'CACHE_EXCLUDE_AREA_UNEXECPTED_SEQUENCE' => '
            Invalid sequence of cache exclude tag. Expecting "%s" but picked "%s" in code "%s".
        ',
    );
}
