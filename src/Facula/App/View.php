<?php

/**
 * Base View
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

namespace Facula\App;

use Facula\Base\Exception\App\View as Exception;
use Facula\Framework;

/**
 * Base view
 */
class View
{
    protected $file = '';

    /** Assigned variable for templating */
    private $assigned = array();

    /**
     * Select a tempalte file and render it
     *
     * @param string $templateFile Template file
     *
     * @return object Return new instance view instance
     */
    public static function template($templateFile)
    {
        return new static($templateFile);
    }

    /**
     * Init the view
     *
     * @param string $templateFile Template file
     *
     * @return void
     */
    protected function __construct($templateFile)
    {
        if (!is_readable($templateFile)) {
            throw new Exception\TemplateFileNotFound($templateFile);

            return false;
        }

        $this->file = $templateFile;
    }

    /**
     * Assign a variable into template
     *
     * @param string $key Key name for the variable
     * @param string $val Value of the variable
     *
     * @return object Return current instance.
     */
    public function assign($key, $val)
    {
        $this->assigned[$key] = $val;

        return $this;
    }

    /**
     * Render and display the page
     *
     * @return bool Return true when succeed, false otherwise
     */
    public function display()
    {
        $content = '';

        if ($content = self::render($this->file, $this->assigned)) {
            Framework::core('response')->setContent($content);
            Framework::core('response')->send();

            return true;
        }

        return false;
    }

    /**
     * Render the page
     *
     * @param string $targetTpl The path to template file
     * @param array $assigned Assigned data
     *
     * @return mixed Return the content in ob buffer.
     */
    protected static function render($targetTpl, array $assigned)
    {
        ob_start();

        extract($assigned);

        Framework::core('debug')->criticalSection(true);

        require($targetTpl);

        Framework::core('debug')->criticalSection(false);

        return ob_get_clean();
    }
}
