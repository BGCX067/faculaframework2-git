<?php

/**
 * Framework Demo: Project api controller
 *
 * Facula Framework 2015 (C) Rain Lee
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
 * @copyright  2015 Rain Lee
 * @package    Facula
 * @version    0.1 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace MyProject\Demo\Controller;

use MyProject\Controller\Root;

/**
 * API controller
 */
class API extends Root
{
    /**
     * Controller configuration array.
     */
    protected $setting = array();

    /**
     * Controller constructor
     */
    public function __construct()
    {
        $this->setting = array(
            'Site' => $this->getSetting('Site'),
            'Defaults' => $this->getSetting('APISetting'),
        );
    }

    /**
     * Method that will be call after controller initialized
     */
    public function inited()
    {
        $this->assign('Setting', $this->setting);

        return true;
    }

    public function displayGetName()
    {
        $name = $this->request->getCookie('GuestName');

        $this->response->setContent(
            $name ? $name : $this->setting['Defaults']['DefaultName']
        );

        return $this->response->send();
    }
}
