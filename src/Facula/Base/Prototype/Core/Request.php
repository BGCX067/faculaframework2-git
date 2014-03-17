<?php

/**
 * Request Core Prototype
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

namespace Facula\Base\Prototype\Core;

/**
 * Prototype class for Request core for make core remaking more easy
 */
abstract class Request extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\Request
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Default configuration */
    protected static $requestMethods = array(
        'GET' => 'get',
        'POST' => 'post',
        'PUT' => 'put',
        'HEAD' => 'head',
        'DELETE' => 'delete',
        'TRACE' => 'trace',
        'OPTIONS' => 'options',
        'CONNECT' => 'connect',
        'PATCH' => 'patch'
    );

    /** Priority of forward header */
    protected static $xForwardPriority = array(
        'HTTP_X_FORWARDED_FOR' => 3,
        'HTTP_X_FORWARDED' => 2,
        'HTTP_FORWARDED_FOR' => 1,
        'HTTP_FORWARDED' => 0,
    );

    /** Instance configuration for caching */
    protected $configs = array();

    /** Container to storage request data ($_GET, $_POST, $_COOKIE and so) */
    protected $pool = array();

    /** Request info */
    protected $requestInfo = array(
        'method' => 'get',
        'rootURL' => '',
        'absRootURL' => '',
        'gzip' => false,
        'languages' => array(
            'en'
        ),
        'language' => 'en',
        'https' => false,
        'auth' => array(
            'Username' => '',
            'Password' => '',
        ),
        'ip' => '0.0.0.0',
        'ipArray' => array('0','0','0','0'),
        'forwarded' => false,
        'xForwardedName' => '',
        'fromSelf' => false,
    );

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common, $facula)
    {
        global $_SERVER;

        if (function_exists('get_magic_quotes_gpc')) {
            $this->configs['AutoMagicQuotes'] = get_magic_quotes_gpc();
        }

        if (isset($cfg['DenyExternalSubmit']) && $cfg['DenyExternalSubmit']) {
            $this->configs['NoExtSubmit'] = true;
        } else {
            $this->configs['NoExtSubmit'] = false;
        }

        if (isset($cfg['MaxDataSize'])) {
            // give memory_limit * 0.8 because our app needs memory to run, so memory
            // cannot be 100%ly use for save request data;
            $this->configs['MaxDataSize'] = min(
                (int)($cfg['MaxDataSize']),
                \Facula\Base\Tool\Misc\PHPIni::convertIniUnit(ini_get('post_max_size')),
                \Facula\Base\Tool\Misc\PHPIni::convertIniUnit(ini_get('memory_limit')) * 0.8
            );
        } else {
            $this->configs['MaxDataSize'] = min(
                \Facula\Base\Tool\Misc\PHPIni::convertIniUnit(ini_get('post_max_size')),
                \Facula\Base\Tool\Misc\PHPIni::convertIniUnit(ini_get('memory_limit')) * 0.8
            );
        }

        // CDN or approved proxy servers
        if (isset($cfg['TrustedProxies']) && is_array($cfg['TrustedProxies'])) {
            $proxyIPRange = $proxyIPTemp = array();

            if (defined('AF_INET6')) {
                $this->configs['TPVerifyFlags'] = FILTER_FLAG_IPV4 + FILTER_FLAG_IPV6;
            } else {
                $this->configs['TPVerifyFlags'] = FILTER_FLAG_IPV4;
            }

            foreach ($cfg['TrustedProxies'] as $proxy) {
                $proxyIPRange = explode('-', $proxy, 2);

                foreach ($proxyIPRange as $proxyIP) {
                    if (!filter_var(
                        $proxyIP,
                        FILTER_VALIDATE_IP,
                        $this->configs['TPVerifyFlags']
                    )) {
                        throw new \Exception($proxyIP . ' not a valid IP for proxy server.');
                        break;
                        break;
                    }
                }

                if (isset($proxyIPRange[1])) {
                    $proxyIPTemp[0] = (inet_pton($proxyIPRange[0]));
                    $proxyIPTemp[1] = (inet_pton($proxyIPRange[1]));

                    if ($proxyIPTemp[0] < $proxyIPTemp[1]) {
                        $this->configs['TrustedProxies'][$proxyIPTemp[0]] = $proxyIPTemp[1];
                    } elseif ($proxyIPTemp[0] > $proxyIPTemp[1]) {
                        $this->configs['TrustedProxies'][$proxyIPTemp[1]] = $proxyIPTemp[0];
                    } else {
                        $this->configs['TrustedProxies'][$proxyIPTemp[0]] = false;
                    }
                } else {
                    $this->configs['TrustedProxies'][(inet_pton($proxyIPRange[0]))] = false;
                }
            }
        } else {
            $this->configs['TrustedProxies'] = array();
        }

        // We can handler up to 512 elements in _GET + _POST + _COOKIE + SERVER array
        $this->configs['MaxRequestBlocks'] = isset($cfg['MaxRequestBlocks'])
                                                ? (int)($cfg['MaxRequestBlocks']) : 512;

        // How long of the data we can handle.
        $this->configs['MaxHeaderSize'] = isset($cfg['MaxHeaderSize'])
                                                ? (int)($cfg['MaxHeaderSize']) : 1024;

        $this->configs['CookiePrefix'] = isset($common['CookiePrefix'][0])
                                                ? $common['CookiePrefix'] : '';

        // Get environment variables

        // Get current root
        if (isset($common['SiteRootURL'][0])) {
            $this->requestInfo['rootURL'] = $common['SiteRootURL'];
        } else {
            $this->requestInfo['rootURL'] = substr(
                $_SERVER['SCRIPT_NAME'],
                0,
                strrpos($_SERVER['SCRIPT_NAME'], '/')
            );
        }

        // Get current absolute root
        if (isset($_SERVER['SERVER_NAME']) && isset($_SERVER['SERVER_PORT'])) {
            $this->requestInfo['absRootURL'] =
                ($_SERVER['SERVER_PORT'] == 443 ? 'https://' : 'http://')
                . $_SERVER['SERVER_NAME']
                . ($_SERVER['SERVER_PORT'] == 80 ? '' : ':'
                . $_SERVER['SERVER_PORT'])
                . $this->requestInfo['rootURL'];
        }
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        global $_GET, $_POST, $_COOKIE, $_SERVER;
        $curXForwdPri = 0;

        // Init all needed array if not set.
        if (!isset($_GET, $_POST, $_COOKIE, $_SERVER)) {
            $_SERVER = $_COOKIE = $_POST = $_GET = array();
        }

        // Sec check: Request array element cannot exceed this
        if ((count($_GET) + count($_POST) +
            count($_COOKIE) + count($_SERVER)) > $this->configs['MaxRequestBlocks']) {
            \Facula\Framework::core('debug')->exception(
                'ERROR_REQUEST_BLOCKS_OVERLIMIT',
                'limit',
                true
            );
        } elseif (isset($_SERVER['CONTENT_LENGTH'])
            && (int)($_SERVER['CONTENT_LENGTH']) > $this->configs['MaxDataSize']) {
            // Sec check: Request size cannot large than this
            \Facula\Framework::core('debug')->exception(
                'ERROR_REQUEST_SIZE_OVERLIMIT',
                'limit',
                true
            );
        }

        if ($this->configs['AutoMagicQuotes']) { // Impossible by now, remove all slash code back
            foreach ($_GET as $key => $val) {
                $_GET[$key] = is_array($val)
                                ? array_map('stripslashes', $val) : stripslashes($val);
            }

            foreach ($_POST as $key => $val) {
                $_POST[$key] = is_array($val)
                                ? array_map('stripslashes', $val) : stripslashes($val);
            }

            foreach ($_COOKIE as $key => $val) {
                $_COOKIE[$key] = is_array($val)
                                ? array_map('stripslashes', $val) : stripslashes($val);
            }
        }

        // Check the size and by the way, figure out client info
        foreach ($_SERVER as $key => $val) {
            if (!isset($val[$this->configs['MaxHeaderSize']])) {
                switch (strtoupper($key)) {
                    case 'REQUEST_METHOD':
                        // Determine the type of request method.
                        $this->requestInfo['method'] = isset(static::$requestMethods[$val])
                            ? static::$requestMethods[$val] : 'get';
                        break;

                    case 'HTTP_ACCEPT_ENCODING':
                        // Try to found out if our dear client support gzip
                        if (strpos($val, 'gzip') !== false) {
                            $this->requestInfo['gzip'] = true;
                        }
                        break;

                    case 'HTTP_ACCEPT_LANGUAGE':
                        // No need to read all languages that client has
                        $lang = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'], 3);

                        foreach ($lang as $languageOrder => $language) {
                            $this->requestInfo['languages'][$languageOrder] = trim(
                                strtolower(explode(';', $language, 2)[0])
                            );
                        }

                        if (isset($this->requestInfo['languages'][0])) {
                            $this->requestInfo['language'] = $this->requestInfo['languages'][0];
                        }
                        break;

                    case 'SERVER_PORT':
                        if ($val == 443) {
                            $this->requestInfo['https'] = true;
                        }
                        break;

                    case 'PHP_AUTH_USER':
                        $this->requestInfo['auth'] = array(
                            'Username' => $val,
                            'Password' => isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '',
                        );
                        break;

                    case 'HTTP_REFERER':
                        if (strpos(
                            $_SERVER['HTTP_REFERER'],
                            $this->requestInfo['absRootURL']
                        ) == 0) {
                            $this->requestInfo['fromSelf'] = true;
                        }
                        break;

                    case 'HTTP_X_FORWARDED_FOR':
                    case 'HTTP_X_FORWARDED':
                    case 'HTTP_FORWARDED_FOR':
                    case 'HTTP_FORWARDED':
                        if (!$this->requestInfo['xForwardedName']
                            || static::$xForwardPriority[$this->requestInfo['xForwardedName']] > $curXForwdPri) {
                            $this->requestInfo['xForwardedName'] = $key;
                            $curXForwdPri = static::$xForwardPriority[$this->requestInfo['xForwardedName']];
                        }
                        break;

                    default:
                        $this->requestInfo['Raw'][$key] = &$_SERVER[$key];
                        break;
                }
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_REQUEST_HEADER_SIZE_OVERLIMIT|' . $key,
                    'limit',
                    true
                );

                return false;
                break;
            }
        }

        // Get client IP
        if ($this->requestInfo['ip'] = $this->getUserIP(true)) {
            $this->requestInfo['ipArray'] = $this->splitIP($this->requestInfo['ip']);

            if (isset($_SERVER['REMOTE_ADDR'])
                && $this->requestInfo['ip'] != $_SERVER['REMOTE_ADDR']) {
                $this->requestInfo['forwarded'] = true;
            }
        }

        // Deny external submit request when needed by clear POST array
        if ($this->configs['NoExtSubmit']
        && !$this->requestInfo['fromSelf']) {
            $_POST = array();
        }

        $this->pool = array(
            'GET' => &$_GET,
            'POST' => &$_POST,
            'COOKIE' => &$_COOKIE,
        );

        return true;
    }

    /**
     * Get client info by key
     *
     * @param string $key The key name of info
     *
     * @return mixed Return the info when found, or false otherwise
     */
    public function getClientInfo($key)
    {
        if (isset($this->requestInfo[$key])) {
            return $this->requestInfo[$key];
        }

        return null;
    }

    /**
     * Shorter: Get cookie
     *
     * @param string $key The key name of cookie
     *
     * @return mixed Return the result of static::get
     */
    public function getCookie($key)
    {
        return $this->get('COOKIE', $this->configs['CookiePrefix'] . $key);
    }

    /**
     * Shorter: Get post
     *
     * @param string $key The key name of post
     *
     * @return mixed Return the result of static::get
     */
    public function getPost($key)
    {
        return $this->get('POST', $key);
    }

    /**
     * Shorter: Get get
     *
     * @param string $key The key name of get
     *
     * @return mixed Return the result of static::get
     */
    public function getGet($key)
    {
        return $this->get('GET', $key);
    }

    /**
     * Shorter: Get posts
     *
     * @param array $keys The key names of post
     * @param array $errors Error reference for detail
     *
     * @return mixed Return the result of static::gets
     */
    public function getPosts(array $keys, array &$errors = array())
    {
        return $this->gets('POST', $keys, $errors, false);
    }

    /**
     * Shorter: Get gets
     *
     * @param array $keys The key names of get
     * @param array $errors Error reference for detail
     *
     * @return mixed Return the result of static::gets
     */
    public function getGets(array $keys, array &$errors = array())
    {
        return $this->gets('GET', $keys, $errors, false);
    }

    /**
     * Get request data
     *
     * @param string $type Type of the data (GET, POST, COOKIE and so)
     * @param string $key Key name of the data
     * @param bool $errored A reference to storage if it's not found
     *
     * @return mixed Return data when found, or null otherwise
     */
    public function get($type, $key, &$errored = false)
    {
        if (isset($this->pool[$type][$key])) {
            return $this->pool[$type][$key];
        } else {
            $errored = true;
        }

        return null;
    }

    /**
     * Get request datas
     *
     * @param string $type Type of the data (GET, POST, COOKIE and so)
     * @param array $keys Key name of the data
     * @param array $errors A reference to storage if it's not found
     * @param bool $failfalse Return false when any requested data not found
     *
     * @return mixed Return data when found, or null otherwise
     */
    public function gets($type, array $keys, array &$errors = array(), $failfalse = false)
    {
        $result = array();

        if (is_array($keys)) {
            foreach ($keys as $key) {
                if (isset($this->pool[$type][$key])) {
                    $result[$key] = $this->pool[$type][$key];
                } elseif ($failfalse) {
                    return false;
                } else {
                    $result[$key] = null;
                    $errors[] = $key;
                }
            }
        }

        return !empty($result) ? $result : false;
    }

    /**
     * Get user's theoretically real IP
     *
     * @param bool $outAsString Get a string or array of IP
     *
     * @return mixed Return the IP address as $outAsString setting. Use 0.0.0.0 for false.
     */
    protected function getUserIP($outAsString = false)
    {
        global $_SERVER;
        $ip = '';

        if ($this->requestInfo['xForwardedName']) {
            if (isset($_SERVER['REMOTE_ADDR'])) {
                if (!$this->checkProxyTrusted($_SERVER['REMOTE_ADDR'])
                    ||
                    (($ip = $this->getRealIPAddrFromXForward(
                        $_SERVER[$this->requestInfo['xForwardedName']]
                    )) == '0.0.0.0')) {
                    // If REMOTE_ADDR (Must be proxy's addr here) not in our trusted
                    // list OR No any server we can trust in X Forward, set the address to REMOTE_ADDR
                    $ip = $_SERVER['REMOTE_ADDR'];
                }
            } else {
                $ip = '0.0.0.0';
            }
        } else {
            $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
        }

        return $outAsString ? $ip : static::splitIP($ip);
    }

    /**
     * Split IP
     *
     * @param string $ip The IP string
     *
     * @return array Splited IP address in array
     */
    protected function splitIP($ip)
    {
        return explode(':', str_replace('.', ':', $ip), 8); // Max is 8 for a IP addr
    }

    /**
     * Get user's IP address from user's X-Forwarded header
     *
     * @param string $x_forwarded_for The data in x_forwarded_for
     *
     * @return string The theoretically IP address
     */
    protected function getRealIPAddrFromXForward($x_forwarded_for)
    {
        $ips = array_reverse(explode(',', str_replace(' ', '', $x_forwarded_for)));

        foreach ($ips as $forwarded) {
            if (filter_var($forwarded, FILTER_VALIDATE_IP, $this->configs['TPVerifyFlags'])) {
                if (!$this->checkProxyTrusted($forwarded)) {
                    return $forwarded;
                    break;
                }
            } else {
                break;
            }
        }

        return '0.0.0.0';
    }

    /**
     * Check if the IP in the trusted list
     *
     * @param string $ip
     *
     * @return bool Return true for trusted, false for not
     */
    protected function checkProxyTrusted($ip)
    {
        $bIP = inet_pton($ip);

        if (isset($this->configs['TrustedProxies'][$bIP])) {
            return true;
        }

        foreach ($this->configs['TrustedProxies'] as $start => $end) {
            if ($end && $bIP >= $start && $bIP <= $end) {
                return true;
                break;
            }
        }

        return false;
    }
}
