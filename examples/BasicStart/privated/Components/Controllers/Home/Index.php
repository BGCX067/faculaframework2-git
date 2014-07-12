<?php

/**
 * Framework Demo: Project index controller
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

use MyProject\Controller\Root;
use Facula\Framework;

/**
 * Index controller
 */
class Index extends Root
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

    /**
     * Processor of HTTP GET method
     */
    public function get()
    {
        $this->assign(
            'Guest',
            array(
                'Name' => $this->getCookie('GuestName'),
            )
        );

        return $this->display('home');
    }

    /**
     * Processor of HTTP POST method
     */
    public function post()
    {
        $post = $this->getPosts(array(
            'Action',
            'Name'
        ));

        switch($post['Action']) {
            case 'Renew':
                Framework::clearState();
                break;

            case 'ClearName':
                $this->response->unsetCookie('GuestName');
                break;

            case 'SetName':
                $this->response->setCookie('GuestName', $post['Name']);
                break;
        }

        return $this->redirect('');
    }
}
