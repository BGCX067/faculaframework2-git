<?php

/**
 * Debug Core Prototype
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

/**
 * Prototype class for Debug core for make core remaking more easy
 */
abstract class Debug extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\Debug
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Error message template */
    protected static $errorMessageTemplate = array(
        'line' => array(
            'Code' => '
                <div
                    class="facula-error"
                    style="
                        clear: both;
                        margin: 10px 0;
                        font-size: 1em;
                        color: #fff;
                        padding: 20px;
                        background: #9e2e2e;
                ">
                    <div
                        class="icon"
                        style="
                            background: #8b3131;
                            box-shadow: inset 0 0 5px #702d2d;
                            border-radius: 10px;
                            padding: 10px 20px;
                            font-size: 30px;
                            margin-right: 20px;
                            float: left;
                        "
                    >!</div>

                    <div class="message" style="padding: 5px; overflow: auto; zoom: 1;">
                        <span class="title" style="font-size: 0.9em">
                            An error occurred:
                        </span>

                        <div class="code" style="padding-top: 5px; word-wrap: break-word;">%Error:Code%</div>

                        %Error:Detail%
                    </div>
                </div>
            ',

            'Banner' => array(
                'Start' => '<ul class="trace">',
                'End' => '</ul>',
                'Code' => '
                    <li class="item" style="margin-bottom: 10px;">
                        <div class="item caller">
                            %Error:Banner:Caller%
                        </div>

                        <div class="item file" style="font-size: 0.9em; color: #e96d6d;">
                            %Error:Banner:File% (Line: %Error:Banner:Line%)
                        </div>

                        <div class="item plate" style="font-size: 0.9em;">
                            Author: %Error:Banner:Plate:Author%,
                            Reviser: %Error:Banner:Plate:Reviser%,
                            Contact: %Error:Banner:Plate:Contact%,
                            Updated: %Error:Banner:Plate:Updated%,
                            Version: %Error:Banner:Plate:Version%
                        </div>
                    </li>
                '
            ),
        ),

        'page' => array(
            'Code' => '
                <!doctype html>
                <html>
                    <head>
                        <title>Oops!</title>

                        <style>
                            body {
                                width: 100%;
                                margin: 0;
                                padding: 0;
                                background: #072731;
                                color: #fff;
                                font-size: 1em;
                                word-wrap: break-word;
                            }
                            a {
                                color: #fff;
                            }
                            h1 {
                                margin: 15px 0;
                            }
                            #error {
                                padding: 50px;
                                background: #9e2e2e;
                                box-shadow: 0 0 10px #3c0d0d;
                            }

                            #error .msg {
                                overflow: hidden;
                                zoom: 1;
                            }
                            #error .icon {
                                display: block;
                                width: 120px;
                                height: 120px;
                                line-height: 120px;
                                font-style: normal;
                                font-size: 100px;
                                background: #8b3131;
                                box-shadow: inset 0 0 5px #702d2d;
                                border-radius: 10px;
                                margin-right: 50px;
                                text-align: center;
                                float: left;
                            }
                            #error .suggestion {
                                margin-top: 50px;
                                padding: 20px 0 0 0;
                            }
                            #error .suggestions {
                                margin-top: 20px;
                                font-size: 0.9em;
                            }
                            #error .suggestions li {
                                margin: 10px 0;
                            }
                            #info {
                                padding: 50px;
                            }
                            #trace {
                                list-style: none;
                                padding: 0;
                            }
                            #trace li {
                                margin-bottom: 50px;
                                clear: both;
                            }
                            #trace .no {
                                padding: 20px 25px;
                                font-size: 20px;
                                display: block;
                                background: #19363f;
                                margin: 0 20px 0 0;
                                border-radius: 10%;
                                font-style: normal;
                                float: left;
                            }
                            #trace .detail {
                                overflow: hidden;
                                margin-left: 120px;
                                zoom: 1;
                                _display: inline;
                                _margin-left: 30px;
                            }
                            #trace .item {
                                margin-bottom: 3px;
                            }
                            #trace .item.caller {
                                font-size: 1.1em;
                            }
                            #trace .item.file {
                                color: #2c4f5a;
                            }
                            #trace .item.plate {
                                font-size: 0.8em;
                            }
                            #misc-info {
                                padding: 50px;
                                font-size: 0.8em;
                                color: #405961;
                            }
                        </style>
                    </head>

                    <body>
                        <div id="error">
                            <i class="icon">!</i>

                            <div class="msg">
                                <h1>An error occurred</h1>

                                %Error:Code%

                                <div class="suggestion">
                                    Our application made a serious mistake when try to display that page for you.
                                    Please:

                                    <ul class="suggestions">
                                        <li>
                                            <a href="javascript:history.go(0);">Reload</a>
                                            this page see if this error disappears.
                                        </li>

                                        <li>
                                            If this error screen still, may be, it take a while to fix.
                                            So relax, have some fun in other places.
                                        </li>

                                        <li>
                                            If you want to help, please contact our web manager.
                                            report this error with all information on this page.
                                        </li>

                                        <li>
                                            If you are the web manager,
                                            the information below will help you fix this problem.
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </div>

                        <div id="info">
                            %Error:Detail%
                        </div>

                        <div id="misc-info">
                            Happened: %Error:Time%,&nbsp;
                            Debug: %Error:DebugStatus%,&nbsp;
                            Initialized: %Error:BootVersion%,&nbsp;
                            Application: %Error:AppName% (%Error:AppVersion%),&nbsp;
                            Server: %Error:ServerName%
                        </div>
                    </body>
                </html>
            ',

            'Banner' => array(
                'Start' => '<ul id="trace">',
                'End' => '</ul>',
                'Code' => '
                    <li>
                        <i class="no">%Error:Banner:No%</i>
                        <div class="detail">
                            <div class="item caller">
                                %Error:Banner:Caller%
                            </div>

                            <div class="item file">
                                %Error:Banner:File% (Line: %Error:Banner:Line%)
                            </div>

                            <div class="item plate">
                                Author: %Error:Banner:Plate:Author%,
                                Reviser: %Error:Banner:Plate:Reviser%,
                                Contact: %Error:Banner:Plate:Contact%,
                                Updated: %Error:Banner:Plate:Updated%,
                                Version: %Error:Banner:Plate:Version%
                            </div>
                        </div>
                    </li>
                '
            ),
        ),

        'cli' => array(
            'Code' => '
                Error: %Error:Code%
                ===========================================
                %Error:Detail%
            ',
            'Banner' => array(
                'Start' => '',
                'End' => '',
                'Code' => '
                    > %Error:Banner:Caller%
                    # %Error:Banner:File% (Line: %Error:Banner:Line%)
                '
            ),
        ),
    );

    /** All errors recorded in here */
    protected $errorRecords = array();

    /** Trigger for enable or disable error display */
    protected $tempDisabled = false;

    /** Trigger for enable or disable error display and logging */
    protected $tempFullDisabled = false;

    /** Instance setting for caching */
    protected $configs = array();

    /** Custom handler for error catch */
    protected $customHandler = null;

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common)
    {
        $this->configs = array(
            'ExitOnAnyError' => isset($cfg['ExitOnAnyError'])
                                ? $cfg['ExitOnAnyError'] : false,

            'LogRoot' => isset($cfg['LogRoot']) && is_dir($cfg['LogRoot'])
                        ? \Facula\Base\Tool\File\PathParser::get($cfg['LogRoot']) : '',

            'LogServer' => array(
                'Addr' => isset($cfg['LogServerInterface'][0]) ? $cfg['LogServerInterface'] : '',

                'Key' => isset($cfg['LogServerKey'][0]) ? $cfg['LogServerKey'] : '',
            ),

            'Debug' => !isset($cfg['Debug']) || $cfg['Debug'] ? true : false,

            'ServerName' => $common['PHP']['ServerName'],

            'SAPI' => $common['PHP']['SAPI'],

            'BootVersion' => $common['BootVersion'],

            'AppName' => $common['AppName'],

            'AppVersion' => $common['AppVersion'],
        );
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        register_shutdown_function(function () {
            $this->shutdownTask();
        }); // Experimentally use our own fatal reporter

        set_error_handler(function ($errno, $errstr, $errfile, $errline, $errcontext) {
            $this->errorHandler($errno, $errstr, $errfile, $errline, $errcontext);
        }, E_ALL); // Use our own error reporter, just like PHP's E_ALL

        set_exception_handler(function ($exception) {
            $this->exceptionHandler($exception);
        }); // Use our own exception reporter

        if (isset($this->configs['LogServer']['Addr'][0])) {
            $this->configs['LogServer']['Enabled'] = true;
        } else {
            $this->configs['LogServer']['Enabled'] = false;
        }

        $this->configs['DefaultErrorLevel'] = error_reporting(
            E_ALL
            &~ (
                E_CORE_ERROR
                | E_CORE_WARNING
                | E_COMPILE_ERROR
                | E_COMPILE_WARNING
                | E_PARSE
                | E_ERROR
                | E_WARNING
                | E_DEPRECATED
                | E_NOTICE
                | E_USER_ERROR
                | E_USER_WARNING
                | E_USER_DEPRECATED
                | E_USER_NOTICE
            )
        );

        return true;
    }

    /**
     * Shutdown routine of this class
     *
     * @return void
     */
    protected function shutdownTask()
    {
        // 1, I remember when any failure in functions that
        //    registered with register_shutdown_function will
        //    case whole shutdown_function queue be halt.
        // 2, When this function finished, we will don't have
        //    chance to catch any further errors.
        //    And there may have other shutdown functions still
        //    wait to go after this.
        // So, we recover the error reporting setting, for giving
        // a chance to log the error on server's error log. ( And
        // since this should be the first one in register_shutdown_function
        // queue, everything will be fine. Remaining functions will be
        // run under default error_reporting level. )
        error_reporting($this->configs['DefaultErrorLevel']);
        // However, i'm not recommend you to use
        // register_shutdown_function(and also shutingdown hook), If
        // you want to do something very expensive, do it with response_finished hook.

        $this->fatalHandler();
        $this->reportError();
    }

    /**
     * Add log into log file or record pool
     *
     * @param string $type Type of this error
     * @param string $errorCode Error code
     * @param string $content Message of the error
     * @param string $backTraces Data of back Traces
     *
     * @return bool true when log saved, false otherwise
     */
    public function addLog($type, $errorCode, $content = '', &$backTraces = array())
    {
        list($time, $micro) = explode('.', microtime(true) . '.' . 0, 3);
        $date = date('l dS \of F Y h:i:s A', $time);
        $ip = \Facula\Framework::core('request')->getClientInfo('ip');

        if ($this->configs['LogRoot']) {
            $datefileName = date('Y-m-d H', $time);
            $errorType = '[' . strtoupper($type) . ']' . ($errorCode ? ':' . $errorCode : '');

            $filename = 'log.' . $datefileName . '.php';
            $format = "<?php exit(); ?> {$errorType} {$ip} ({$date}.{$micro}): {$content}";

            return file_put_contents(
                $this->configs['LogRoot']
                . DIRECTORY_SEPARATOR
                . $filename,
                $format
                . "\r\n",
                FILE_APPEND | LOCK_EX
            );
        }

        $this->errorRecords[] = array(
            'Time' => $date,
            'Type' => $type,
            'ErrorNo' => $errorCode,
            'Content' => $content,
            'backTraces' => $backTraces,
            'IP' => $ip,
        );

        return true;
    }

    /**
     * Report error using HTTP POST method to a remote server
     *
     * @return bool true success, false otherwise
     */
    protected function reportError()
    {
        if (!empty($this->errorRecords)
            && $this->configs['LogServer']['Enabled']) {
            $app = \Facula\Framework::getCoreInfo();

            $data = array(
                'KEY' => $this->configs['LogServer']['Key'],
                'APP' => $app['App'],
                'VER' => $app['Ver'],
                'ERRNO' => isset($this->errorRecords[0]['ErrorNo'])
                        ? $this->errorRecords[0]['ErrorNo'] : 'Default Error No',
                'DATA' => json_encode($this->errorRecords),
            );

            $http = array(
                'http' => array(
                    'method' => 'POST',
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n".
                                "User-Agent: Facula Framework Debug Reporter\r\n",
                    'timeout'=> 5,
                    'content' => http_build_query($data, '', '&'),
                ),
            );

            $this->criticalSection(true);
            $result = file_get_contents(
                $this->configs['LogServer']['Addr'],
                false,
                stream_context_create($http)
            );
            $this->criticalSection(false);

            if ($result) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }

    /**
     * Register the error handler
     *
     * @param mixed $handler The handler in Closure or other callable data types.
     *
     * @return bool true success, false otherwise
     */
    public function registerHandler($handler)
    {
        if (!$this->customHandler) {
            if (is_callable($handler)) {
                $this->customHandler = $handler;

                return true;
            }
        } else {
            $this->exception(
                'ERROR_HANDLER_ALREADY_REGISTERED',
                'conflict',
                true
            );
        }

        return false;
    }

    /**
     * Enter or leave error block mode
     *
     * @param bool $enter Set true to enter error message block mode, false to leave block mode
     * @param bool $fullEnter Set to true to enter error message and log block mode, false to leave it
     *
     * @return bool always true
     */
    public function criticalSection($enter, $fullEnter = false)
    {
        if ($enter) {
            $this->tempDisabled = true;

            if ($fullEnter) { // Disable all error message and logging
                $this->tempFullDisabled = true;
            }
        } else {
            $this->tempFullDisabled = $this->tempDisabled = false;
        }

        return true;
    }

    /**
     * Error Handler
     *
     * @param bool $errno Error mumber
     * @param bool $errstr Error message
     * @param bool $errfile File trigger that error
     * @param bool $errline Line of that code trigger the error
     * @param bool $errcontext Dump information
     *
     * @return mixed Return the result of static::exception
     */
    protected function errorHandler($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $exit = false;

        switch ($errno) {
            case E_ERROR:
                $exit = true;
                break;

            case E_PARSE:
                $exit = true;
                break;

            case E_CORE_ERROR:
                $exit = true;
                break;

            case E_CORE_WARNING:
                $exit = true;
                break;

            case E_COMPILE_ERROR:
                $exit = true;
                break;

            case E_COMPILE_WARNING:
                $exit = true;
                break;

            case E_USER_ERROR:
                $exit = true;
                break;
        }

        return $this->exception(
            sprintf(
                'Error code %s (%s) in file %s line %s',
                'PHP('.$errno.')',
                $errstr,
                $errfile,
                $errline
            ),
            'PHP|PHP:'
            . $errno,
            !$exit ? $this->configs['ExitOnAnyError'] : true
        );
    }

    /**
     * Exception Handler
     *
     * @param object $exception The instance of exception object
     *
     * @return mixed Return the result of static::exception
     */
    protected function exceptionHandler($exception)
    {
        return $this->exception(
            'Exception: '
            . $exception->getMessage(),
            'Exception',
            true,
            $exception
        );
    }

    /**
     * Fatal Handler
     *
     * @return mixed Return false when no error picked up, otherwise, return the result of static::errorHandler
     */
    protected function fatalHandler()
    {
        $errfile = 'Unknown file';
        $errstr  = '';
        $errno   = E_CORE_ERROR;
        $errline = 0;

        if ($error = error_get_last()) {
            $errno   = $error['type'];
            $errfile = $error['file'];
            $errline = $error['line'];
            $errstr  = $error['message'];

            return $this->errorHandler($errno, $errstr, $errfile, $errline, null);
        }

        return false;
    }

    /**
     * Processor of every exception
     *
     * @param string $info Error message
     * @param string $type Error type
     * @param string $exit Will the error triggers exit? true to yes, others to no
     * @param Exception $e Instance of Exception object
     *
     * @return void
     */
    public function exception($info, $type = '', $exit = false, \Exception $e = null)
    {
        if (!$this->tempFullDisabled) {
            $backTraces = array_reverse($this->backTrace($e));

            $types = explode('|', $type, 2);

            $this->addLog(
                $types[0] ? $types[0] : 'Exception',
                isset($types[1][0]) ? $types[1] : '',
                $info,
                $backTraces
            );

            if (!$this->tempDisabled) {
                if ($this->customHandler) {
                    $customHandler = $this->customHandler;
                    $customHandler($info, $type, $backTraces, $exit, $this->configs['Debug']);
                } else {
                    if ($e) {
                        $this->displayErrorBanner($e->getMessage(), $backTraces, false, 0);
                    } else {
                        $this->displayErrorBanner($info, $backTraces, false, 2);
                    }
                }
            }
        }

        if ($exit) {
            exit();
        }
    }

    /**
     * Get argument's data type
     *
     * @param string $split parameter spliter
     * @param string $args Arguments
     *
     * @return string Combined string of arguments in $args
     */
    protected function getArgsType($split, $args = array())
    {
        $tmpstr = '';

        if (is_array($args)) {
            foreach ($args as $key => $val) {
                if ($tmpstr) {
                    $tmpstr .= $split;
                }

                if (is_object($val)) {
                    $tmpstr .= 'object ' . get_class($val);
                } elseif (is_bool($val)) {
                    $tmpstr .= $val ? 'true' : 'false';
                } elseif (is_int($val)) {
                    $tmpstr .= 'integer ' . $val;
                } elseif (is_float($val)) {
                    $tmpstr .= 'float ' . $val;
                } elseif (is_array($val)) {
                    $tmpstr .= 'array(' . implode(', ', array_keys($val)) . ')';
                } elseif (is_resource($val)) {
                    $tmpstr .= 'resource ' . get_resource_type($val);
                } elseif (is_string($val)) {
                    $tmpstr .= '\'' . str_replace(
                        array(
                            PROJECT_ROOT,
                            FACULA_ROOT,
                        ),
                        array(
                            '[PROJECT]',
                            '[FACULA]',
                        ),
                        $val
                    ) . '\'';
                } elseif (is_null($val)) {
                    $tmpstr .= 'null';
                } else {
                    $tmpstr .= 'unknown';
                }
            }

            return $tmpstr;
        }

        return false;
    }

    /**
     * Perform a back trace
     *
     * @param Exception $e The instance of Exception
     *
     * @return array Return the result of back trace
     */
    protected function backTrace(\Exception $e = null)
    {
        $result = array();

        if ($e) {
            $trace = $e->getTrace();
        } else {
            $trace = debug_backtrace();
            array_shift($trace);
        }

        foreach ($trace as $key => $val) {
            $result[] = array(
                'caller' => (isset($val['class']) ?
                            $val['class']
                            . (isset($val['type']) ? $val['type'] : '::') : '')
                            . (isset($val['function']) ? $val['function']
                            . '(' : 'main (')
                            . (isset($val['args']) ? $this->getArgsType(', ', $val['args']) : '')
                            . ')',
                'file' => isset($val['file']) ? $val['file'] : 'Unknown',

                'line' => isset($val['line']) ? $val['line'] : 'Unknown',

                'nameplate' => isset($val['class']) && isset($val['class']::$plate) ? array(
                    'author' => isset($val['class']::$plate['Author'][0])
                                ? $val['class']::$plate['Author'] : 'Undeclared',

                    'reviser' => isset($val['class']::$plate['Reviser'][0])
                                ? $val['class']::$plate['Reviser'] : 'Undeclared',

                    'contact' => isset($val['class']::$plate['Contact'][0])
                                ? $val['class']::$plate['Contact'] : '',

                    'updated' => isset($val['class']::$plate['Updated'][0])
                                ? $val['class']::$plate['Updated'] : 'Undeclared',

                    'version' => isset($val['class']::$plate['Version'][0])
                                ? $val['class']::$plate['Version'] : 'Undeclared',
                ) : array(
                    'author' => 'Nobody',
                    'reviser' => 'Nobody',
                    'contact' => 'No one',
                    'updated' => 'Undeclared',
                    'version' => 'Undeclared',
                )
            );
        }

        return $result;
    }

    /**
     * Display a error message to user
     *
     * @param string $message Error message
     * @param array $backTraces Back traces information
     * @param bool $returnCode Return the html code instead to display them
     * @param integer $callerOffset Exclude debug functions in back traces result
     *
     * @return mixed
     */
    protected function displayErrorBanner(
        $message,
        array $backTraces,
        $returnCode = false,
        $callerOffset = 0
    ) {
        $detail = $templateString = $templateBanner = '';
        $line = 0;

        switch ($this->configs['SAPI']) {
            case 'cli':
                $templateString = static::$errorMessageTemplate['cli']['Code'];
                $templateBanner = static::$errorMessageTemplate['cli']['Banner'];
                break;

            default:
                if (!headers_sent()) {
                    header('HTTP/1.0 500 Internal Server Error');

                    $templateString = static::$errorMessageTemplate['page']['Code'];
                    $templateBanner = static::$errorMessageTemplate['page']['Banner'];
                } else {
                    $templateString = static::$errorMessageTemplate['line']['Code'];
                    $templateBanner = static::$errorMessageTemplate['line']['Banner'];
                }
                break;
        }

        if ($this->configs['Debug']) {
            $detail = static::renderErrorDetailBanners(
                $backTraces,
                $templateBanner,
                $callerOffset
            );
        } else {
            $detail = 'Debug disabled, error detail unavailable.';
        }

        $templateAssigns = array(
            '%Error:Detail%' => $detail,

            '%Error:Code%' => str_replace(
                array(
                    PROJECT_ROOT,
                    FACULA_ROOT,
                ),
                array(
                    '[PROJECT]',
                    '[FACULA]',
                ),
                $message
            ),

            '%Error:Time%' => date(DATE_ATOM, FACULA_TIME),

            '%Error:DebugStatus%' => $this->configs['Debug'] ? 'Enabled' : 'Disabled',

            '%Error:BootVersion%' => date(DATE_ATOM, $this->configs['BootVersion']),

            '%Error:AppName%' => $this->configs['AppName'],

            '%Error:AppVersion%' => $this->configs['AppVersion'],

            '%Error:ServerName%' => $this->configs['ServerName'],
        );

        $displayContent = str_replace(
            array_keys($templateAssigns),
            array_values($templateAssigns),
            $templateString
        );

        $displayContent = trim(
            str_replace(array("\t", '  '), '', $displayContent)
        );

        if ($returnCode) {
            return $displayContent;
        } else {
            echo $displayContent . "\r\n\r\n";
        }

        return false;
    }

    /**
     * Display a error message to user
     *
     * @param array $backTraces Back traces information
     * @param array $banner Banner setting
     * @param integer $callerOffset Exclude debug functions in back traces result
     *
     * @return string The rendered result according back traces info and banner setting
     */
    protected static function renderErrorDetailBanners(
        array $backTraces,
        array $banner,
        $callerOffset
    ) {
        $assigns = array();
        $detail = '';

        $detail = $banner['Start'];

        if ($traceSize = count($backTraces)) {
            $traceCallerOffset = $traceSize - ($callerOffset < $traceSize ? $callerOffset : 0);
            $tracesLoop = 0;
        }

        foreach ($backTraces as $key => $val) {
            $tracesLoop++;

            $assigns = array(
                '%Error:Banner:No%' => $tracesLoop,
                '%Error:Banner:Caller%' => $val['caller'],
                '%Error:Banner:File%' =>
                    \Facula\Base\Tool\File\PathParser::replacePathPrefixes(
                        array(
                            PROJECT_ROOT,
                            FACULA_ROOT,
                        ),
                        array(
                            '[PROJECT]',
                            '[FACULA]',
                        ),
                        $val['file']
                    ),
                '%Error:Banner:Line%' => $val['line'],
                '%Error:Banner:Plate:Author%' => $val['nameplate']['author'],
                '%Error:Banner:Plate:Reviser%' => $val['nameplate']['reviser'],
                '%Error:Banner:Plate:Contact%' => $val['nameplate']['contact'],
                '%Error:Banner:Plate:Updated%' => $val['nameplate']['updated'],
                '%Error:Banner:Plate:Version%' => $val['nameplate']['version'],
            );

            $detail .= str_replace(
                array_keys($assigns),
                array_values($assigns),
                $banner['Code']
            );
        }

        $detail .= $banner['End'];

        return $detail;
    }
}
