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
        'PATH_TEMPLATEPOOL_NOTFOUND' => '
            The path declared in "TemplatePool" setting must be defined and existed.
        ',

        'PATH_COMPILEDTEMPLATE_NOTFOUND' => '
            The path declared in "CompiledTemplate" must be defined and existed.
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

        'TEMPLATE_IMPORTING_EXISTED' => '
            The importing template "%s" for set "%s" is conflicting with existing one.
            Please use other name or set to import.
        ',

        'LANGUAGE_IMPORTING_UNSUPPORTED' => '
            The importing language "%s" is not supported.
            This application currently only support following language codes: %s.
        ',

        'RENDER_INTERFACE' => '
            The template render "%s" must implement interface "%s".
        ',

        'COMPILER_INTERFACE' => '
            The template compiler "%s" must implement interface "%s".
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
    );
}