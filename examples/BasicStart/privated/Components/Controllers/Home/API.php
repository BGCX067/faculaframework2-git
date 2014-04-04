<?php

/**
 * Framework Demo: Project api controller
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
 * @version    0.1 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace MyProject\Controller\Home;

/**
 * API controller
 */
class API extends \MyProject\Controller\Root
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
            'Time' => FACULA_TIME,
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
        $this->response->setContent(
            $this->request->getCookie('GuestName')
        );

        return $this->response->send();
    }
}
