<?php

/**
 * Request Core Prototype
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
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Prototype\Core;

use Facula\Base\Error\Core\Request as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Request as Implement;
use Facula\Base\Tool\PHP\Ini as Ini;

/**
 * Prototype class for Request core for make core remaking more easy
 */
abstract class Request extends Factory implements Implement
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Valid methods used by framework */
    protected static $requestMethods = array(
        'GET' => 'GET',
        'POST' => 'POST',
        'PUT' => 'PUT',
        'HEAD' => 'HEAD',
        'DELETE' => 'DELETE',
        'TRACE' => 'TRACE',
        'OPTIONS' => 'OPTIONS',
        'CONNECT' => 'CONNECT',
        'PATCH' => 'PATCH',
    );

    /** Priority of forward header */
    protected static $xForwardPriority = array(
        'HTTP_X_FORWARDED_FOR' => 3,
        'HTTP_X_FORWARDED' => 2,
        'HTTP_FORWARDED_FOR' => 1,
        'HTTP_FORWARDED' => 0,
    );

    /** A tag to not allow re-warming */
    protected $rewarmingMutex = false;

    /** Instance configuration for caching */
    protected $configs = array();

    /** Container to storage request data ($_GET, $_POST, $_COOKIE and so) */
    protected $pool = array();

    /** Request info */
    protected $requestInfo = array(
        'method' => 'GET',
        'rootURL' => '',
        'absRootURL' => '',
        'gzip' => false,
        'languages' => array(
            'en'
        ),
        'language' => 'en',
        'https' => false,
        'acceptedCharsets' => array(
            '*' => 1.0,
        ),
        'acceptedEncodings' => array(
            'identity' => 1.0
        ),
        'acceptedLanguages' => array(
            '*' => 1.0
        ),
        'acceptedTypes' => array(
            '*/*' => 1.0
        ),
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
            // give memory_limit * 0.6 because we needs memory to run other stuffs, so memory
            // cannot be 100%ly use for handle request data;
            $this->configs['MaxDataSize'] = min(
                (int)($cfg['MaxDataSize']),
                Ini::getBytes('post_max_size'),
                Ini::getBytes('memory_limit') * 0.6
            );
        } else {
            $this->configs['MaxDataSize'] = min(
                Ini::getBytes('post_max_size'),
                Ini::getBytes('memory_limit') * 0.6
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
                        new Error(
                            'PROXYADDR_INVALID',
                            array(
                                $proxyIP
                            ),
                            'ERROR'
                        );

                        return false;

                        break 2;
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
                                                ? (int)($cfg['MaxHeaderSize']) : 5120;

        $this->configs['CookiePrefix'] = isset($common['CookiePrefix'][0])
                                                ? $common['CookiePrefix'] : '';

        // Get environment variables

        // Get current root
        if (isset($_SERVER['SCRIPT_NAME'])) {
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
                $this->requestInfo['hostURIFormated'] =
                    '%s//'
                    . $_SERVER['SERVER_NAME']
                    . '%s';

                $this->requestInfo['absRootFormated'] =
                    $this->requestInfo['hostURIFormated']
                    . $this->requestInfo['rootURL'];

                $this->requestInfo['absRootURL'] = sprintf(
                    $this->requestInfo['absRootFormated'],
                    (
                        $this->isHTTPS() ?
                            'https:'
                        :
                            'http:'
                    ),
                    (
                        $this->isHTTPS() ?
                            ($_SERVER['SERVER_PORT'] == '443' ? '' : ':' . $_SERVER['SERVER_PORT'])
                        :
                            ($_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT'])
                    )
                );
            }
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
        $curXForwdPri = $requestBlocks = 0;

        if ($this->rewarmingMutex) {
            new Error('REWARMING_NOTALLOWED');
        }

        $this->rewarmingMutex = true;

        // Init all needed array if not set.
        if (!isset($_GET, $_POST, $_COOKIE, $_SERVER)) {
            $_SERVER = $_COOKIE = $_POST = $_GET = array();
        }

        // Sec check: Request array element cannot exceed this
        $requestBlocks = count($_GET)
                        + count($_POST)
                        + count($_COOKIE)
                        + count($_SERVER);

        if ($requestBlocks > $this->configs['MaxRequestBlocks']) {
            new Error(
                'BLOCKS_OVERLIMIT',
                array(
                    $this->configs['MaxRequestBlocks'],
                    $requestBlocks
                ),
                'ERROR'
            );

            return false;
        } elseif (isset($_SERVER['CONTENT_LENGTH'])
        && (int)($_SERVER['CONTENT_LENGTH']) > $this->configs['MaxDataSize']) {
            // Sec check: Request size cannot large than this
            new Error(
                'LENGTH_OVERLIMIT',
                array(
                    $this->configs['MaxDataSize'],
                    $_SERVER['CONTENT_LENGTH']
                ),
                'ERROR'
            );

            return false;
        }

        if ($this->configs['AutoMagicQuotes']) {
            // Impossible by now, remove all slash code back
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
                        if (isset(static::$requestMethods[$val])) {
                            // Normal match, standard client
                            $this->requestInfo['method'] = static::$requestMethods[$val];
                        } else {
                            // The client not following rules

                            // Try convert $val to upper case to match it again
                            $val = strtoupper($val);

                            if (isset(static::$requestMethods[$val])) {
                                $this->requestInfo['method'] = static::$requestMethods[$val];
                            }

                            // We don't support this method. It's may a extension-method
                            // Application creator must implement their own by use old PHP way
                            // (get data from $_SERVER['REQUEST_METHOD'], and deal with it themself)
                        }
                        break;

                    case 'HTTP_ACCEPT':
                        if ($acceptedTypes = $this->parseAcceptValue(strtolower($val), 10, 10)) {
                            $this->requestInfo['acceptedTypes'] = $acceptedTypes;
                        }
                        break;

                    case 'HTTP_ACCEPT_ENCODING':
                        // Try to found out if our dear client support gzip
                        // gzip go first as seems no body use * those days
                        if ($acceptedEncodings = $this->parseAcceptValue(strtolower($val), 10, 10)) {
                            $this->requestInfo['acceptedEncodings'] = $acceptedEncodings;
                        }
                        break;

                    case 'HTTP_ACCEPT_CHARSET':
                        // utf-8, gbk and more
                        if ($acceptedCharsets = $this->parseAcceptValue(strtoupper($val), 10, 10)) {
                            $this->requestInfo['acceptedCharsets'] = $acceptedCharsets;
                        }
                        break;

                    case 'HTTP_ACCEPT_LANGUAGE':
                        // zh-cn, en and more
                        if ($acceptedLanguages = $this->parseAcceptValue(strtolower($val), 10, 10)) {
                            $this->requestInfo['acceptedLanguages'] = $acceptedLanguages;
                        }

                        // The Order of language should be ordered with Q value as this is PHP
                        $this->requestInfo['languages'] = array_keys(
                            $this->requestInfo['acceptedLanguages']
                        );

                        if (isset($this->requestInfo['languages'][0])) {
                            $this->requestInfo['language'] = $this->requestInfo['languages'][0];
                        }
                        break;

                    case 'SERVER_PORT':
                        if ($val == 443) {
                            $this->requestInfo['https'] = true;
                        }
                        break;

                    case 'HTTPS':
                        if ($val != 'off') {
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
                new Error(
                    'HEADERITEM_OVERLIMIT',
                    array(
                        $key,
                        $this->configs['MaxHeaderSize']
                    ),
                    'ERROR'
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
     * Parse value for Accept-* into property => weight paired array
     *
     * Notice that this method will not take out data as standard way
     * for speeding purpose.
     *
     * @param string $value The value of a Accept-* header
     * @param integer $maxSections Max result sections
     * @param integer $maxFramesPerSection Max frame per each sections
     *
     * @return array Return a array in pair
     */
    protected function parseAcceptValue(
        $value,
        $maxSections = 16,
        $maxFramesPerSection = 16
    ) {
        $sequences = $frames = $tempFrame = array();
        $frameValueAssignPos = $lastSectionIdx = 0;
        $qValue = '';

        $sections = explode(';', $value, $maxSections + 1);

        if (isset($sections[$maxSections])) {
            array_pop($sections);
        }

        foreach ($sections as $sectionIdx => $section) {
            $tempFrame = explode(
                ',',
                $section,
                $maxFramesPerSection
            );

            if (0 != $sectionIdx
            && ($frameValueAssignPos = strpos($tempFrame[0], '=')) !== false) {
                $qValue = trim(substr(
                    $tempFrame[0],
                    $frameValueAssignPos + 1,
                    strlen($tempFrame[0])
                ));

                if ('' === $qValue) {
                    // Default q should be 1.0
                    $frames[$lastSectionIdx][1] = 1.0;
                } elseif ('0' === $qValue) {
                    // 0 means not do not use, so unset it
                    unset($frames[$lastSectionIdx]);
                } else {
                    $frames[$lastSectionIdx][1] = (float)$qValue;
                }

                unset($tempFrame[0]);

                if (empty($tempFrame)) {
                    continue;
                }
            }

            $frames[$sectionIdx] = array(
                $tempFrame,
                1.0,
            );

            $lastSectionIdx = $sectionIdx;
        }

        foreach ($frames as $frame) {
            foreach ($frame[0] as $key) {
                $sequences[trim($key)] = $frame[1];
            }
        }

        if (isset($sequences[''])) {
            unset($sequences['']);
        }

        arsort($sequences);

        return $sequences;
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
                || (($ip = $this->getRealIPAddrFromXForward(
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

    /**
     * Check if current access from HTTPS
     *
     * @return bool Return true when accessed from HTTPS, false otherwise
     */
    protected function isHTTPS()
    {
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
        ||
        ($_SERVER['SERVER_PORT'] == '443')) {
            return true;
        }

        return false;
    }
}
