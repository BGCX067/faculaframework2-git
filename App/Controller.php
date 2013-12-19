<?php

/**
 * Base Controller
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
 * Base Controller
 */
abstract class Controller extends Setting
{
    public function init()
    {
        foreach (\Facula\Framework::getAllCores() as $coreName => $coreReference) {
            $this->$coreName = $coreReference;
        }

        return true;
    }

    public function run()
    {
        $method = $this->request->getClientInfo('method');

        if (method_exists($this, $method)) {
            return $this->$method();
        } else {
            $this->response->setHeader('HTTP/1.1 405 Method Not Allowed');
            $this->response->send();
            return false;
        }
    }

    protected function getGet($key)
    {
        return $this->request->getGet($key);
    }

    protected function getPost($key)
    {
        return $this->request->getPost($key);
    }

    protected function getCookie($key)
    {
        return $this->request->getCookie($key);
    }

    protected function getGets(array $keys, array &$errors = array())
    {
        return $this->request->getGets($keys, $errors);
    }

    protected function getPosts(array $keys, array &$errors = array())
    {
        return $this->request->getPosts($keys, $errors);
    }

    protected function redirect($addr, $httpcode = 302, $interior = true)
    {
        $rootUrl = $interior ? $this->request->getClientInfo('rootURL') . '/' : '';

        switch ($httpcode) {
            case 301:
                $this->response->setHeader('HTTP/1.1 301 Moved Permanently');
                break;

            case 302:
                $this->response->setHeader('HTTP/1.1 302 Moved Temporarily');
                break;

            default:
                break;
        }

        return $this->response->setHeader('Location: ' . $rootUrl . $addr) && $this->response->send() ? true : false;
    }

    protected function header($code)
    {
        return $this->response->setHeader($code);
    }

    protected function send($content, $type)
    {
        if ($this->response->setContent($content)) {
            return $this->response->send($type);
        }

        return false;
    }

    protected function assign($key, $val)
    {
        if (isset($this->template)) {
            if ($this->template->assign($key, $val)) {
                return true;
            }
        } else {
            $this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
        }

        return false;
    }

    protected function error($msg)
    {
        if ($this->template) {
            return $this->template->insertMessage($msg);
        } else {
            $this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
        }

        return false;
    }

    protected function display($tplName, $cacheExpired = 0, $cacheExpiredCallback = null, $tplSet = '', $factor = '')
    {
        $content = '';

        if (isset($this->template)) {
            if ($content = $this->template->render($tplName, $tplSet, $cacheExpired, $cacheExpiredCallback, $factor)) {
                if ($this->response->setContent($content)) {
                    return $this->response->send();
                }
            }
        } else {
            $this->debug->exception('ERROR_CONTROLLER_CORE_INACTIVE_TEMPLATE', 'controller', true);
        }

        return false;
    }
}