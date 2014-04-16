<?php

/**
 * Error Base Prototype
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

namespace Facula\Base\Prototype;

/**
 * Base class of execption
 */
abstract class Error implements \Facula\Base\Implement\Error
{
    /** Error type to php error type */
    protected static $errorTypes = array(
        'ERROR' => E_USER_ERROR,
        'WARNING' => E_USER_WARNING,
        'NOTICE' => E_USER_NOTICE,
        'DEPRECATED' => E_USER_DEPRECATED,
    );

    /** Error code to error string convert table */
    protected static $errorStrings = array();

    /** Result of backtrace */
    protected $trace = array();

    /** Last trace */
    protected $lastTrace = array();

    /** Caller file */
    protected $file = '';

    /** Caller line in the file */
    protected $line = 0;

    /** Caller function name */
    protected $function = '';

    /** Caller class name */
    protected $class = '';

    /** Type of caller class method */
    protected $functionType = '';

    /** Arguments */
    protected $args = array();

    /** Error message */
    protected $message = '';

    /**
     * Constructor of an error
     *
     * @param string $errorCode The code of the error, like: NO_ERROR
     * @param array $assign Information for formating the error message
     * @param string $errorType Error type: ERROR, WARNING, NOTICE, DEPRECATED
     *
     * @return void
     */
    final public function __construct(
        $errorCode,
        array $errorAssign = array(),
        $errorType = 'NOTICE'
    ) {
        $cores = \Facula\Framework::getAllCores();
        $backTrace = array();

        if (!isset(static::$errorTypes[$errorType])) {
            trigger_error(
                'Specified error type ' . $errorType . ' not found.',
                E_USER_ERROR
            );

            return false;
        }

        $backTrace = debug_backtrace();

        array_shift($backTrace);

        if (!isset($backTrace[0])) {
            trigger_error(
                'The trace result not exist. Must be called from current function.',
                E_USER_ERROR
            );

            return false;
        }

        if (isset($backTrace[0]['file'])) {
            $this->file = $backTrace[0]['file'];
        }

        if (isset($backTrace[0]['line'])) {
            $this->line = $backTrace[0]['line'];
        }

        if (isset($backTrace[0]['function'])) {
            $this->function = $backTrace[0]['function'];
        }

        if (isset($backTrace[0]['class'])) {
            $this->class = $backTrace[0]['class'];
        }

        if (isset($backTrace[0]['type'])) {
            $this->functionType = $backTrace[0]['type'];
        }

        if (isset($backTrace[0]['args'])) {
            $this->args = $backTrace[0]['args'];
        }

        $this->trace = $backTrace;
        $this->lastTrace = $backTrace[0];

        if (isset(static::$errorStrings[$errorCode])) {
            $this->message = $this->trimErrorMessage(vsprintf(
                static::$errorStrings[$errorCode],
                $errorAssign
            ));
        } else {
            $this->message = $errorCode;
        }

        if (isset($cores['debug'])) {
            $cores['debug']->error(
                static::$errorTypes[$errorType],
                ('[' . $this->class . ']: ') . $this->message,
                $this->file,
                $this->line,
                array(),
                $this->trace
            );
        } else {
            trigger_error(
                ('[' . $this->class . ']: ') . $this->message,
                static::$errorTypes[$errorType]
            );
        }
    }

    /**
     * Get trace information
     *
     * @param string $string The error message
     *
     * @return string Trimmed string
     */
    protected static function trimErrorMessage($string)
    {
        $newString = $string;

        // Yeah, for error message, slow is acceptable currently
        while (true) {
            $newString = str_replace(
                array(
                    "\r",
                    "\n",
                    "\t",
                    '  ' // 2 spaces
                ),
                ' ',
                $string
            );

            if ($newString == $string) {
                break;
            } else {
                $string = $newString;
            }
        }

        return trim($newString);
    }

    /**
     * Set error format string
     *
     * @param string $key The error code
     * @param string $string The error message string
     *
     * @return bool Return true when success, fail otherwise.
     */
    final public function setErrorString($key, $string)
    {
        if (isset(static::$errorStrings[$key])) {
            static::$errorStrings[$key] = $string;

            return true;
        }

        trigger_error(
            'Error code: '
            . $key
            . ' seems not available. so you cannot set it.',
            static::$errorTypes[$errorType]
        );

        return false;
    }

    /**
     * Get trace information
     *
     * @return array The trace information
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * Get file name
     *
     * @return string File name when it has, empty string otherwise
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * Get error line
     *
     * @return integer Error line when it has, 0 otherwise
     */
    public function getLine()
    {
        return $this->line;
    }

    /**
     * Get caller class
     *
     * @return string caller class name when it has, empty string otherwise
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Get caller function
     *
     * @return string caller function name when it has, empty string otherwise
     */
    public function getFunction()
    {
        return $this->function;
    }

    /**
     * Get caller function type
     *
     * @return string caller function type name when it has, empty string otherwise
     */
    public function getFunctionType()
    {
        return $this->functionType;
    }

    /**
     * Get caller function arguments
     *
     * @return string caller function arguments name when it has, empty string otherwise
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Get error message
     *
     * @return string Formated error message
     */
    public function getMessage()
    {
        return $this->message;
    }
}
