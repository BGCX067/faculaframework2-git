<?php

/**
 * Page Render
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

namespace Facula\Unit\Paging\Render;

use Facula\Base\Implement\Core\Template\Render as Implement;

/**
 * Provide a space to render Facula pages
 */
class Render implements Implement
{
    /** Rendered content */
    private $content = '';

    /**
     * Constructor of Render
     *
     * @param string $targetTpl File path to the PHP script file (Compiled template)
     * @param array $assigned Assigned data
     *
     * @return void
     */
    public function __construct(&$targetTpl, array &$assigned = array())
    {
        $this->content = static::isolatedRender($targetTpl, $assigned);

        return true;
    }

    /**
     * Get render result
     *
     * @return string Rendered content
     */
    public function getResult()
    {
        return $this->content;
    }

    protected static function isolatedRender($targetTpl, array $assigned)
    {
        ob_start();

        extract($assigned);
        unlink($assigned);

        \Facula\Framework::core('debug')->criticalSection(true);

        require($targetTpl);

        \Facula\Framework::core('debug')->criticalSection(false);

        return ob_get_clean();
    }
}
