<?php

/**
 * Debug Core Prototype
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
     * @param array &$cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, &$common)
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
        );

        return true;
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
     * @param string &$backTraces Data of back Traces
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
                FILE_APPEND
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
     * @param bool $errno Line of that code trigger the error
     * @param bool $errcontext Dump information
     *
     * @return mixed Return the result of self::exception
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
     * @return mixed Return the result of self::exception
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
     * @return mixed Return false when no error picked up, otherwise, return the result of self::errorHandler
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
                } elseif (is_integer($val)) {
                    $tmpstr .= 'integer ' . $val;
                } elseif (is_float($val)) {
                    $tmpstr .= 'float ' . $val;
                } elseif (is_array($val)) {
                    $tmpstr .= var_export(array_keys($val), true);
                } elseif (is_resource($val)) {
                    $tmpstr .= 'resource ' . get_resource_type($val);
                } elseif (is_string($val)) {
                    $tmpstr .= '\'' . $val . '\'';
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
                'file' => isset($val['file']) ? $val['file'] : 'unknown',

                'line' => isset($val['line']) ? $val['line'] : 'unknown',

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
                    'contact' => '',
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
    protected function displayErrorBanner($message, array $backTraces, $returnCode = false, $callerOffset = 0)
    {
        $code = $file = '';
        $line = 0;

        if (!headers_sent($file, $line)) {
            if ($this->configs['Debug']) {
                $code = '<div class="facula-error" style="clear:both;">'
                        . '<span class="title" style="clear:both;font-size:150%;">'
                        . 'Facula Error: <strong>'
                        . str_replace(
                            array(
                                FACULA_ROOT,
                                PROJECT_ROOT
                            ),
                            array(
                                '[Facula Dir]',
                                '[Project Dir]'
                            ),
                            $message
                        )
                        . '</strong></span><ul>';

                if ($traceSize = count($backTraces)) {
                    $traceCallerOffset = $traceSize - ($callerOffset < $traceSize ? $callerOffset : 0);
                    $tracesLoop = 0;

                    foreach ($backTraces as $key => $val) {
                        $tracesLoop++;
                        $code .= '<li'
                                . ($tracesLoop >= $traceCallerOffset
                                ? ' class="current" style="margin:10px;padding:10px;background-color:#fcc;'
                                . 'border-radius:5px;color:#a33;"' : ' style="padding:10px;"')
                                . '><span style="line-height:1.5;"><span class="trace" style="display:block;'
                                . 'font-size:120%;">'
                                . str_replace(
                                    array(
                                        FACULA_ROOT,
                                        PROJECT_ROOT
                                    ),
                                    array(
                                        '[Facula Dir]',
                                        '[Project Dir]'
                                    ),
                                    $val['caller']
                                ) . '</span><span class="notice" style="display:block;margin-bottom:3px;'
                                . 'font-size:60%;">Author: <u>' . $val['nameplate']['author']
                                . '</u> Reviser: <u>'
                                . $val['nameplate']['reviser']
                                . '</u> '
                                . ' Version: <u>'
                                . $val['nameplate']['version']
                                . '</u> Updated in: <u>'
                                . $val['nameplate']['updated']
                                . '</u> Contact: <u>'
                                . ($val['nameplate']['contact'] ? $val['nameplate']['contact'] : 'Nobody')
                                . '</u></span><span class="notice" '
                                . 'style="display:block;font-size:60%;font-weight:bold;">Triggered in file: '
                                . str_replace(
                                    array(
                                        FACULA_ROOT,
                                        PROJECT_ROOT
                                    ),
                                    array(
                                        '[Facula Dir]',
                                        '[Project Dir]'
                                    ),
                                    $val['file']
                                )
                                . ' (line ' . $val['line'] . ')'
                                . '</span></span></li>';
                    }
                }

                $code .= '</ul></div>';
            } else {
                $code = '<div class="facula-error-min" style="text-align:center;clear:both;">'
                        . 'Sorry, we got a problem while cooking the page for you.</div>';
            }

            if ($returnCode) {
                return $code;
            } else {
                echo($code);
            }

            return true;
        } else {
            $this->addLog(
                'Error banner',
                '0',
                'Encountered an error but header already sent in file '
                . $file
                . ' line: '
                . $line
            );
        }

        return false;
    }
}
