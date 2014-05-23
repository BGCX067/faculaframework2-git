<?php

/**
 * Base Controller
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

use Facula\Framework;
use Facula\Base\Exception\App\Controller as Exception;

/**
 * Base Controller
 */
abstract class Controller extends Setting
{
    public $cachedObjectFilePath = '';
    public $cachedObjectSaveTime = 0;

    private $cores = array();

    /**
     * Magic method set to replace core instance in controller
     *
     * @param string $key The key name function core
     * @param string $val The function core instance
     *
     * @return mixed Return the function core if found, null otherwise.
     */
    final public function __set($key, $val)
    {
        if (isset($this->cores[$key])) {
            $this->cores[$key] = $val;

            return true;
        }

        throw new Exception\UndefinedProperty($key);

        return false;
    }

    /**
     * Magic method get to get cores
     *
     * @param string $key The key name function core
     *
     * @return mixed Return the function core if found, null otherwise.
     */
    final public function __get($key)
    {
        if (isset($this->cores[$key])) {
            return $this->cores[$key];
        }

        switch ($key) {
            case 'debug':
                throw new Exception\DebugCoreInactive();
                break;

            case 'object':
                throw new Exception\ObjectCoreInactive();
                break;

            case 'request':
                throw new Exception\RequestCoreInactive();
                break;

            case 'response':
                throw new Exception\ResponseCoreInactive();
                break;

            case 'template':
                throw new Exception\TemplateCoreInactive();
                break;

            case 'pdo':
                throw new Exception\PDOCoreInactive();
                break;

            case 'cache':
                throw new Exception\CacheCoreInactive();
                break;

            default:
                throw new Exception\CoreInactive($key);
                break;
        }

        return null;
    }

    /**
     * A initializer method that will be auto automatically call by Object core
     *
     * In this very case, it will load all Facula cores into current instance space
     *
     * @return bool Return true when initialize complete, false otherwise.
     */
    final public function init()
    {
        $this->cores = Framework::getAllCores();

        return true;
    }

    /**
     * Default runner that will be automatically call by
     * Object core if no runner specified.
     *
     * In this very case, it will get HTTP METHOD, and use that to call
     * specific class method for request processing.
     *
     * @return mixed Return the result of the method when it's found, false otherwise.
     */
    final public function run()
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

    /**
     * Get a parameter from GET request
     *
     * @param string $key The key name of GET parameter
     *
     * @return mixed Return the result of Request core::getGet
     */
    final protected function getGet($key)
    {
        return $this->request->getGet($key);
    }

    /**
     * Get a parameter from POST request
     *
     * @param string $key The key name of POST parameter
     *
     * @return mixed Return the result of Request core::getPost
     */
    final protected function getPost($key)
    {
        return $this->request->getPost($key);
    }

    /**
     * Get a parameter from COOKIE
     *
     * @param string $key The key name of COOKIE parameter
     *
     * @return mixed Return the result of Request core::getCookie
     */
    final protected function getCookie($key)
    {
        return $this->request->getCookie($key);
    }

    /**
     * Get parameters from GET request
     *
     * @param array $keys The key names of GET parameter
     * @param array $errors A reference to catch failed parameters
     *
     * @return mixed Return the result of Request core::getGets
     */
    final protected function getGets(array $keys, array &$errors = array())
    {
        return $this->request->getGets($keys, $errors);
    }

    /**
     * Get parameters from POST request
     *
     * @param array $keys The key names of POST parameter
     * @param array $errors A reference to catch failed parameters
     *
     * @return mixed Return the result of Request core::getPosts
     */
    final protected function getPosts(array $keys, array &$errors = array())
    {
        return $this->request->getPosts($keys, $errors);
    }

    /**
     * Redirect to other URL
     *
     * @param string $addr Address to redirect to
     * @param integer $httpcode HTTP status code for this redirection
     * @param bool $interior This is root based URL(or a full qualified address when set to false)
     *
     * @return mixed Return the result of Response core::setHeader
     */
    final protected function redirect($addr, $httpcode = 302, $interior = true)
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

        return $this->response->setHeader('Location: ' . $rootUrl . $addr) && $this->response->send() ?
            true : false;
    }

    /**
     * Set a new HTTP header
     *
     * @param string $header The header
     *
     * @return mixed Return the result of Response core::setHeader
     */
    final protected function header($header)
    {
        return $this->response->setHeader($header);
    }

    /**
     * Send content to client
     *
     * @param string $content The content to send
     * @param string $type Type of the content
     *
     * @return mixed Return the result of Response core::send when success, false otherwise
     */
    final protected function send($content, $type)
    {
        if ($this->response->setContent($content)) {
            return $this->response->send($type);
        }

        return false;
    }

    /**
     * Assign variable into Template core
     *
     * @param string $key Key name of the variable
     * @param string $val Value of the variable
     *
     * @return mixed Return the result of Template core::assign when success, false otherwise
     */
    final protected function assign($key, $val)
    {
        if ($this->template->assign($key, $val)) {
            return true;
        }

        return false;
    }

    /**
     * Set a error message in Template core for render
     *
     * @param mixed $msg The message in string or array
     *
     * @return mixed Result from Template core::insertMessage when success, false otherwise
     */
    final protected function error($msg)
    {
        if (!$msg) {
            return false;
        }

        if (is_array($msg)) {
            return $this->template->insertMessage($msg);
        } else {
            return $this->template->insertMessage(array(
                'Message' => $msg,
            ));
        }

        return false;
    }

    /**
     * Display the template
     *
     * @param string $tplName Template name
     * @param string $cacheExpired Cache time in second relative to current
     * @param mixed $cacheExpiredCallback Callback function will be call when template need to render
     * @param string $tplSet Name of a specified template from template series
     * @param string $factor Factor name for template caching
     *
     * @return mixed Result from Response core::send when success, false otherwise
     */
    final protected function display(
        $tplName,
        $cacheExpired = 0,
        $cacheExpiredCallback = null,
        $tplSet = '',
        $factor = ''
    ) {
        $content = '';

        if ($content = $this->template->render(
            $tplName,
            $tplSet,
            $cacheExpired,
            $cacheExpiredCallback,
            $factor
        )) {
            if ($this->response->setContent($content)) {
                return $this->response->send();
            }
        }

        return false;
    }
}
