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
 * @copyright  2014 Rain Lee
 * @package    Facula
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\App;

/**
 * Base view
 */
abstract class View
{
    /** Assigned variable for templating */
    private static $assigned = array();

    /**
     * Assign a variable into template
     *
     * @param string $key Key name for the variable
     * @param string $val Value of the variable
     *
     * @return mixed The assigned value
     */
    public static function assign($key, $val)
    {
        return self::$assigned[$key] = $val;
    }

    /**
     * Render and display the page
     *
     * @param string $path The path to template file
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function display($path)
    {
        $content = '';

        if ($content = self::render($path)) {
            \Facula\Framework::core('response')->setContent($content);
            \Facula\Framework::core('response')->send();

            return true;
        }

        return false;
    }

    /**
     * Render the page
     *
     * @param string $targetTpl The path to template file
     *
     * @return mixed Return the rendered content when succeed, false otherwise
     */
    private static function render($targetTpl)
    {
        if (is_readable($targetTpl)) {
            if ($oldContent = ob_get_clean()) {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_VIEW_BUFFER_POLLUTED|' . htmlspecialchars($oldContent),
                    'template',
                    true
                );

                return false;
            }

            ob_start();

            extract(self::$assigned);

            \Facula\Framework::core('debug')->criticalSection(true);

            require($targetTpl);

            \Facula\Framework::core('debug')->criticalSection(false);

            return ob_get_clean();
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_VIEW_TEMPLATE_FILENOTFOUND|' . $file,
                'data',
                true
            );
        }

        return false;
    }
}
