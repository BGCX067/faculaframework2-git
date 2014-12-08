<?php

/**
 * Response Core Prototype
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

namespace Facula\Base\Prototype\Core;

use Facula\Base\Error\Core\Response as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Response as Implement;
use Facula\Framework;

/**
 * Prototype class for Response core for make core remaking more easy
 */
abstract class Response extends Factory implements Implement
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Globally container for headers */
    protected static $headers = array();

    /** Globally container for cookies */
    protected static $cookies = array();

    /** Content will be send */
    protected static $content = '';

    /** Frequently-used content types for short cut */
    protected static $httpContentTypes = array(
        'evy' => 'application/envoy',
        'fif' => 'application/fractals',
        'spl' => 'application/futuresplash',
        'hta' => 'application/hta',
        'acx' => 'application/internet-property-stream',
        'hqx' => 'application/mac-binhex40',
        'doc' => 'application/msword',
        'dot' => 'application/msword',
        '*' => 'application/octet-stream',
        'bin' => 'application/octet-stream',
        'class' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'exe' => 'application/octet-stream',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'oda' => 'application/oda',
        'axs' => 'application/olescript',
        'pdf' => 'application/pdf',
        'prf' => 'application/pics-rules',
        'p10' => 'application/pkcs10',
        'crl' => 'application/pkix-crl',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript',
        'ps' => 'application/postscript',
        'rtf' => 'application/rtf',
        'setpay' => 'application/set-payment-initiation',
        'setreg' => 'application/set-registration-initiation',
        'xla' => 'application/vnd.ms-excel',
        'xlc' => 'application/vnd.ms-excel',
        'xlm' => 'application/vnd.ms-excel',
        'xls' => 'application/vnd.ms-excel',
        'xlt' => 'application/vnd.ms-excel',
        'xlw' => 'application/vnd.ms-excel',
        'msg' => 'application/vnd.ms-outlook',
        'sst' => 'application/vnd.ms-pkicertstore',
        'cat' => 'application/vnd.ms-pkiseccat',
        'stl' => 'application/vnd.ms-pkistl',
        'pot' => 'application/vnd.ms-powerpoint',
        'pps' => 'application/vnd.ms-powerpoint',
        'ppt' => 'application/vnd.ms-powerpoint',
        'mpp' => 'application/vnd.ms-project',
        'wcm' => 'application/vnd.ms-works',
        'wdb' => 'application/vnd.ms-works',
        'wks' => 'application/vnd.ms-works',
        'wps' => 'application/vnd.ms-works',
        'hlp' => 'application/winhlp',
        'bcpio' => 'application/x-bcpio',
        'cdf' => 'application/x-cdf',
        'z' => 'application/x-compress',
        'tgz' => 'application/x-compressed',
        'cpio' => 'application/x-cpio',
        'csh' => 'application/x-csh',
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dxr' => 'application/x-director',
        'dvi' => 'application/x-dvi',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-gzip',
        'hdf' => 'application/x-hdf',
        'ins' => 'application/x-internet-signup',
        'isp' => 'application/x-internet-signup',
        'iii' => 'application/x-iphone',
        'js' => 'application/x-javascript',
        'latex' => 'application/x-latex',
        'mdb' => 'application/x-msaccess',
        'crd' => 'application/x-mscardfile',
        'clp' => 'application/x-msclip',
        'dll' => 'application/x-msdownload',
        'm13' => 'application/x-msmediaview',
        'm14' => 'application/x-msmediaview',
        'mvb' => 'application/x-msmediaview',
        'wmf' => 'application/x-msmetafile',
        'mny' => 'application/x-msmoney',
        'pub' => 'application/x-mspublisher',
        'scd' => 'application/x-msschedule',
        'trm' => 'application/x-msterminal',
        'wri' => 'application/x-mswrite',
        'cdf' => 'application/x-netcdf',
        'nc' => 'application/x-netcdf',
        'pma' => 'application/x-perfmon',
        'pmc' => 'application/x-perfmon',
        'pml' => 'application/x-perfmon',
        'pmr' => 'application/x-perfmon',
        'pmw' => 'application/x-perfmon',
        'p12' => 'application/x-pkcs12',
        'pfx' => 'application/x-pkcs12',
        'p7b' => 'application/x-pkcs7-certificates',
        'spc' => 'application/x-pkcs7-certificates',
        'p7r' => 'application/x-pkcs7-certreqresp',
        'p7c' => 'application/x-pkcs7-mime',
        'p7m' => 'application/x-pkcs7-mime',
        'p7s' => 'application/x-pkcs7-signature',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'swf' => 'application/x-shockwave-flash',
        'sit' => 'application/x-stuffit',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'roff' => 'application/x-troff',
        't' => 'application/x-troff',
        'tr' => 'application/x-troff',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'ms' => 'application/x-troff-ms',
        'ustar' => 'application/x-ustar',
        'src' => 'application/x-wais-source',
        'cer' => 'application/x-x509-ca-cert',
        'crt' => 'application/x-x509-ca-cert',
        'der' => 'application/x-x509-ca-cert',
        'pko' => 'application/ynd.ms-pkipko',
        'zip' => 'application/zip',
        'au' => 'audio/basic',
        'snd' => 'audio/basic',
        'mid' => 'audio/mid',
        'rmi' => 'audio/mid',
        'mp3' => 'audio/mpeg',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'm3u' => 'audio/x-mpegurl',
        'ra' => 'audio/x-pn-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'wav' => 'audio/x-wav',
        'bmp' => 'image/bmp',
        'cod' => 'image/cis-cod',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jfif' => 'image/pipeg',
        'svg' => 'image/svg+xml',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ras' => 'image/x-cmu-raster',
        'cmx' => 'image/x-cmx',
        'ico' => 'image/x-icon',
        'pnm' => 'image/x-portable-anymap',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'ppm' => 'image/x-portable-pixmap',
        'rgb' => 'image/x-rgb',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'mht' => 'message/rfc822',
        'mhtml' => 'message/rfc822',
        'nws' => 'message/rfc822',
        'css' => 'text/css',
        '323' => 'text/h323',
        'htm' => 'text/html',
        'html' => 'text/html',
        'stm' => 'text/html',
        'uls' => 'text/iuls',
        'bas' => 'text/plain',
        'c' => 'text/plain',
        'h' => 'text/plain',
        'txt' => 'text/plain',
        'rtx' => 'text/richtext',
        'sct' => 'text/scriptlet',
        'tsv' => 'text/tab-separated-values',
        'htt' => 'text/webviewhtml',
        'htc' => 'text/x-component',
        'etx' => 'text/x-setext',
        'vcf' => 'text/x-vcard',
        'mp2' => 'video/mpeg',
        'mpa' => 'video/mpeg',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpv2' => 'video/mpeg',
        'mov' => 'video/quicktime',
        'qt' => 'video/quicktime',
        'lsf' => 'video/x-la-asf',
        'lsx' => 'video/x-la-asf',
        'asf' => 'video/x-ms-asf',
        'asr' => 'video/x-ms-asf',
        'asx' => 'video/x-ms-asf',
        'avi' => 'video/x-msvideo',
        'movie' => 'video/x-sgi-movie',
        'flr' => 'x-world/x-vrml',
        'vrml' => 'x-world/x-vrml',
        'wrl' => 'x-world/x-vrml',
        'wrz' => 'x-world/x-vrml',
        'xaf' => 'x-world/x-vrml',
        'xof' => 'x-world/x-vrml',
    );

    /** Instance configuration for caching */
    public $configs = array();

    /** Instance running data */
    public $data = array(
        'UseGZIP' => false,
        'CharsetFitClient' => false,
    );

    /** A tag to not allow re-warming */
    protected $rewarmingMutex = false;

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common)
    {
        $setting = array();

        $this->configs = array(
            'CookiePrefix' => isset($common['CookiePrefix'][0])
                                ? $common['CookiePrefix'] : 'facula_',

            'GZIPEnabled' => isset($cfg['UseGZIP']) && $cfg['UseGZIP'] && function_exists('gzcompress')
                                ? true : false,

            'PSignal' => isset($cfg['PostProfileSignal']) && $cfg['PostProfileSignal']
                                ? true : false,

            'NoExposure' => isset($cfg['HideServerInfo']) && !$cfg['HideServerInfo']
                                ? false : true,

            'Charset' => strtoupper(
                isset($cfg['Charset'])
                ?
                $cfg['Charset']
                :
                isset($common['Charset']) ? $common['Charset'] : 'UTF-8'
            ),

            'Strict' => // Only response when appropriate (according to Accept-*)
                isset($cfg['Strict']) && $cfg['Strict']
                ?
                true
                :
                false,

            'UseFFR' => function_exists('fastcgi_finish_request')
                                ? true : false,

            'AppVersion' => $common['AppName'] . ' (' . $common['AppVersion'] . ')',
        );
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        if ($this->rewarmingMutex) {
            new Error('REWARMING_NOTALLOWED');
        }

        $this->rewarmingMutex = true;

        // The request function is already warmed as warming order
        $request = Framework::core('request');

        // Check if client happy with current acceptedEncodings
        $this->data['AcceptedEncodings'] =
            $request->getClientInfo('acceptedEncodings');

        if ((isset($this->data['AcceptedCharsets']['*'])
            ||
            isset($this->data['AcceptedCharsets']['identity'])
            ||
            isset($this->data['AcceptedEncodings']['gzip'])
        ) && $this->configs['GZIPEnabled']) {
            $this->data['UseGZIP'] = true;
        }

        // Check if client happy with current charset
        $this->data['AcceptedCharsets'] =
            $request->getClientInfo('acceptedCharsets');

        if (isset($this->data['AcceptedCharsets']['*'])
        || isset($this->data['AcceptedCharsets'][$this->configs['Charset']])) {
            $this->data['CharsetFitClient'] = true;
        }

        // Get Accepted Types
        $this->data['AcceptedTypes'] =
            $request->getClientInfo('acceptedTypes');

        return true;
    }

    /**
     * Send content to client
     *
     * @param string $type Content type
     * @param bool $persistConn Set if let keep connection alive after sent
     * @param string $error Reference used to returning error detail code
     *
     * @return bool Return true when content sent, false otherwise
     */
    public function send($type = 'htm', $persistConn = false, &$error = '')
    {
        $file = $line = $finalContent = $contentType = '';
        $hookResult = null;
        $finalContentLen = $wildMatchTypePos = 0;
        $thereIndiscernible = false;
        $errors = $lastBufferContents = array();

        // If $type is empty, set it to htm as default
        if (!$type) {
            $type = 'htm';
        }

        // Assume we will finish this application after output, calc belowing profile data
        Framework::$profile['MemoryUsage'] = memory_get_usage(true);
        Framework::$profile['MemoryPeak'] = memory_get_peak_usage(true);

        Framework::$profile['OutputTime'] = microtime(true);
        Framework::$profile['ProductionTime'] =
            Framework::$profile['OutputTime'] - Framework::$profile['StartTime'];

        // Check size of response_finished hook queue
        if (Framework::getHookSize('response_finished') > 0) {
            ignore_user_abort(true);

            $thereIndiscernible = true;
        }

        if (!headers_sent($file, $line)) {
            // Get Content-Type first
            if (isset(static::$httpContentTypes[$type])) {
                $contentType = static::$httpContentTypes[$type];
            } else {
                $contentType = $type;
            }

            // If strict mode is on
            if ($this->configs['Strict']) {
                // Don't send anything when client not read this charset
                if (!$this->data['CharsetFitClient']) {
                    $error = 'ERROR_RESPONSE_STRICT_REPLYING_CHARSET_CLIENT_NOT_SUPPORTED';

                    return false;
                }

                // Check if current type we sending matched require
                if (!isset($this->data['AcceptedTypes']['*/*'])) {
                    // Well, seems we have to check it

                    if (!isset($this->data['AcceptedTypes'][$contentType])) {
                        // Try search with wild card
                        $wildMatchType = substr($contentType, 0, strpos($contentType, '/'));

                        if (!isset($this->data['AcceptedTypes'][$wildMatchType . '/*'])) {
                            $error = 'ERROR_RESPONSE_STRICT_REPLYING_TYPE_CLIENT_NOTSUPPORTED';

                            return false;
                        }
                    }
                }
            }

            // Safely shutdown early output, save the old content into a stack
            while (ob_get_level()) {
                $lastBufferContents[] = ob_get_clean();
            }

            // Start buffer to output
            ob_start();

            $finalContent = static::$content;

            $hookResult = Framework::summonHook(
                'response_preparing',
                array($finalContent),
                $errors
            );

            header(
                'Content-Type: '
                . $contentType
                . '; charset='
                . $this->configs['Charset']
            );

            // Hide server software information by replace it.
            if ($this->configs['NoExposure']) {
                header('Server: Facula');
                header('X-Powered-By: facula ' . __FACULAVERSION__ . ' with YOU');
                header('X-Powered-For: ' . $this->configs['AppVersion']);
            }

            if ($this->configs['PSignal']) {
                header(
                    'X-Runtime: '
                    . round(Framework::$profile['ProductionTime']  * 1000, 2)
                    . 'ms (' . Framework::$profile['ProductionTime'] . 's)'
                );

                header(
                    'X-Memory: '
                    . (Framework::$profile['MemoryUsage'] / 1024)
                    . 'kb / ' . (Framework::$profile['MemoryPeak'] / 1024) . 'kb'
                );
            }

            foreach (static::$cookies as $cookie) {
                setcookie(
                    $cookie['Key'],
                    $cookie['Val'],
                    $cookie['Expire'],
                    $cookie['Path'],
                    $cookie['Domain'],
                    $cookie['Secure'],
                    $cookie['HttpOnly']
                );
            }

            foreach (static::$headers as $header) {
                header($header);
            }

            if ($persistConn) {
                header('Connection: Keep-Alive');
            } else {
                header('Connection: Close');
            }

            $hookResult = Framework::summonHook(
                'response_sending',
                array($finalContent),
                $errors
            );

            header('Content-Length: ' . strlen($finalContent));

            echo $finalContent;

            ob_end_flush();

            if ($this->configs['UseFFR']) {
                fastcgi_finish_request();
            }

            flush();

            if (!empty($lastBufferContents)) {
                foreach ($lastBufferContents as $oldObContent) {
                    ob_start();

                    echo $oldObContent;
                }
            }

            if ($thereIndiscernible) {
                Framework::summonHook(
                    'response_finished',
                    $hookResult,
                    $errors
                );
            }

            return true;
        } else {
            new Error(
                'RESPONSE_OVERSEND',
                array(
                    $file,
                    $line
                ),
                'ERROR'
            );

            return false;
        }

        return false;
    }

    /**
     * Set HTTP header without poison the output buffer
     *
     * @param string $header The header content
     *
     * @return bool Always true
     */
    public function setHeader($header)
    {
        // Notice that:
        // We not do sensitivity test here as RFC 2616 says we don't have to
        // But All header names should be set sensitively according to the
        // way it be mentioned in RFC 2616 for consistency. You may also reference:
        // http://en.wikipedia.org/wiki/List_of_HTTP_header_fields#Request_fields
        static::$headers[] = $header;

        return true;
    }

    /**
     * Set content that will be send
     *
     * @param string $content The content that wait to be send
     * @param string $forceRaw Forcibly set the content without been zipped
     *
     * @return bool Always true
     */
    public function setContent($content, $forceRaw = false)
    {
        $orgSize = $gzSize = 0;
        $gzContent = '';

        $orgSize = strlen($content);

        if (!$forceRaw && $this->data['UseGZIP'] && $orgSize >= 2048) {
            $gzContent = gzcompress($content, 2);
            $gzSize = strlen($gzContent);

            static::$content = "\x1f\x8b\x08\x00\x00\x00\x00\x00"
                . substr($gzContent, 0, $gzSize - 4);

            static::$headers['Vary'] = 'Vary: Accept-Encoding';
            static::$headers['Content-Encoding'] = 'Content-Encoding: gzip';
            static::$headers['X-Length'] = 'X-Length: '
                                        . $gzSize
                                        . ' bytes / '
                                        . $orgSize
                                        . ' bytes';
        } else {
            static::$content = $content;

            static::$headers['X-Length'] = 'X-Length: '
                                        . $orgSize
                                        . ' bytes';

            if (isset(static::$headers['Vary'])) {
                unset(static::$headers['Vary']);
            }

            if (isset(static::$headers['Content-Encoding'])) {
                unset(static::$headers['Content-Encoding']);
            }
        }

        return true;
    }

    /**
     * Set cookie without poison the output buffer
     *
     * @param string $key Cookie key
     * @param string $value Value that will be set
     * @param string $expire Expire time
     * @param string $path Path
     * @param string $domain Domain
     * @param bool $secure For HTTPS
     * @param bool $httpOnly For HTTP Only
     *
     * @return bool Always true
     */
    public function setCookie(
        $key,
        $value = '',
        $expire = 0,
        $path = '/',
        $domain = '',
        $secure = false,
        $httpOnly = false
    ) {
        global $_COOKIE;

        $cKey = $this->configs['CookiePrefix'] . $key;

        $cVal = $value !== null ? $value : null;

        $cExpire = $expire ? FACULA_TIME + (int)($expire) : 0;
        $cPath = $path ? $path : '/';

        $cDomain = $domain ?
                    $domain : (strpos($_SERVER['HTTP_HOST'], '.') !== false ? $_SERVER['HTTP_HOST'] : '');

        $cSecure = $secure ? true : false;

        $cHttpOnly = $httpOnly ? true : false;

        if ($cDomain == '127.0.0.1' || $cDomain == 'localhost') {
            $cDomain = '';
        }

        static::$cookies[] = array(
            'Key' => $cKey,
            'Val' => $cVal,
            'Expire' => $cExpire,
            'Path' => $cPath,
            'Domain' => $cDomain,
            'Secure' => $cSecure,
            'HttpOnly' => $cHttpOnly,
        );

        if (is_null($cVal)) {
            // Assume we already successed. The value can be read immediately, no need to reload page.
            $_COOKIE[$this->configs['CookiePrefix'] . $key] = $value;
        } elseif (isset($_COOKIE[$this->configs['CookiePrefix'] . $key])) {
            unset($_COOKIE[$this->configs['CookiePrefix'] . $key]);
        }

        return true;
    }

    /**
     * Unset the cookie
     *
     * @param string $key Cookie key
     *
     * @return bool Always true
     */
    public function unsetCookie($key)
    {
        if ($this->setCookie($key, null, -FACULA_TIME - 1)) {
            return true;
        }

        return false;
    }
}
