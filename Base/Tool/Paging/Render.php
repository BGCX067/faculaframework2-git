<?php

/**
 * Facula Framework Struct Manage Unit
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
 * @package    FaculaFramework
 * @version    2.2 prototype
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Tool\Paging;

class Render
{
    private $content = '';

    public function __construct(&$targetTpl, &$assigned = array())
    {
        if ($oldContent = ob_get_clean()) {
            \Facula\Framework::core('debug')->exception('ERROR_TEMPLATE_RENDER_BUFFER_POLLUTED|' . htmlspecialchars($oldContent), 'template', true);

            return false;
        }

        ob_start();

        extract($assigned);

        \Facula\Framework::core('debug')->criticalSection(true);

        require($targetTpl);

        \Facula\Framework::core('debug')->criticalSection(false);

        $this->content = ob_get_clean();

        return true;
    }

    public function getResult()
    {
        return $this->content;
    }
}
