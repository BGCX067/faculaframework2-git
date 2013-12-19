<?php

/**
 * Base View
 *
 * Facula Framework 2013 (C) Rain Lee
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
 * @copyright  2013 Rain Lee
 * @package    Facula
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 */

namespace Facula\App;

/**
 * Base view
 */
abstract class View
{
    private static $assigned = array();

    public static function assign($key, $val)
    {
        return self::$assigned[$key] = $val;
    }

    public static function display($file)
    {
        $file = \Facula\Framework::core('object')->getFileByNamespace($file);
        $content = '';

        if ($content = self::render($file)) {
            \Facula\Framework::core('response')->setContent($content);
            \Facula\Framework::core('response')->send();
        }

        return false;
    }

    private static function render($targetTpl)
    {
        if (is_readable($targetTpl)) {
            if ($oldContent = ob_get_clean()) {
                \Facula\Framework::core('debug')->exception('ERROR_VIEW_BUFFER_POLLUTED|' . htmlspecialchars($oldContent), 'template', true);

                return false;
            }

            ob_start();

            extract(self::$assigned);

            \Facula\Framework::core('debug')->criticalSection(true);

            require($targetTpl);

            \Facula\Framework::core('debug')->criticalSection(false);

            return ob_get_clean();
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_VIEW_TEMPLATE_FILENOTFOUND|' . $file, 'data', true);
        }

        return false;
    }
}
