<?php

/**
 * Route Selector
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

namespace Facula\Unit;

use Facula\Framework;

/*
    VALID ROUTE FORMAT:

    $routes = array(
        '/level1.1/level1.run1/level1.run1.sub1/?/?/' => array(
            '\controllers\SomeController',
            array(0, 1)
        )
    );
*/

/**
 * Route Selector
 */
abstract class Route
{
    /** Consts for call type */
    const CALL_TYPE_STATIC = 1;
    const CALL_TYPE_INSTANCE = 2;
    const CALL_TYPE_METHOD = 3;

    /** Consts for array indexes of route item */
    const ITEM_CALL_STRING = 0;
    const ITEM_CALL_PARAMETER = 1;
    const ITEM_CALL_CACHE = 2;

    /** Consts for call methods */
    const CALL_STRING_CLASS = 0;
    const CALL_STRING_METHOD = 1;
    const CALL_STRING_TYPE = 2;

    /** Consts for handler errors */
    const HANDLER_FAIL_RESULT_FALSE = 1;
    const HANDLER_FAIL_NO_HANDLER = 2;
    const HANDLER_FAIL_NOT_ALLOWED = 3;

    /** Character will be use to split the route */
    public static $routeSplit = '/';

    /** Route Map */
    private static $routeMap = array(
        'Subs' => array()
    );

    /** Allowed Access method */
    protected static $allowedMethods = array(
        'GET' => 'get',
        'POST' => 'post',
        'PUT' => 'put',
        'HEAD' => 'head',
        'DELETE' => 'delete',
        'TRACE' => 'trace',
        'OPTIONS' => 'options',
        'CONNECT' => 'connect',
        'PATCH' => 'patch',
    );

    /** Handler that will be use when no route specified */
    private static $defaultHandler = null;

    /** Handler that will be use when the route not be found */
    private static $errorHandler = null;

    /** Requested path */
    private static $pathParams = array();

    /** Handlers that will be executed when path is matched */
    private static $operatorParams = array();

    /**
     * Allow a new HTTP request method
     *
     * @param string $requestMethod The name of the HTTP Method
     * @param string $handlerMethod Name of the method in handler class
     *
     * @return bool Return true when added, false otherwise
     */
    public static function addMethod($requestMethod, $handlerMethod)
    {
        if (isset(self::$allowedMethods[$requestMethod])) {
            return false;
        }

        self::$allowedMethods[$requestMethod] = $handlerMethod;

        return true;
    }

    /**
     * Set up the route
     *
     * @param array $paths Paths with Path => Operator pair
     * @param bool $checkClass Validate if defined class and
     *                         method in path item is existed
     *
     * @return bool Always return true
     */
    public static function setup(array $paths, $checkClass = false)
    {
        $tempLastRef = $tempLastUsedRef = null;

        foreach ($paths as $path => $operator) {
            $tempLastRef = &self::$routeMap['Subs'];

            foreach (explode(
                self::$routeSplit,
                trim($path, self::$routeSplit)
            ) as $key => $val) {
                $tempLastUsedRef = &$tempLastRef[$val];

                if (isset($tempLastRef[$val])) {
                    $tempLastRef = &$tempLastRef[$val]['Subs'];
                } else {
                    $tempLastRef[$val] = array('Subs' => array());
                    $tempLastRef = &$tempLastRef[$val]['Subs'];
                }
            }

            if (!isset(
                $operator[static::ITEM_CALL_STRING],
                $operator[static::ITEM_CALL_PARAMETER],
                $operator[static::ITEM_CALL_CACHE]
            )) {
                continue;
            }

            $tempLastUsedRef['Operator'] = array(
                static::ITEM_CALL_STRING => static::parseCallString(
                    $operator[static::ITEM_CALL_STRING],
                    $checkClass
                ),
                static::ITEM_CALL_PARAMETER => $operator[static::ITEM_CALL_PARAMETER],
                static::ITEM_CALL_CACHE => $operator[static::ITEM_CALL_CACHE],
            );
        }

        return true;
    }

    /**
     * Parse the call string
     *
     * @param string $callString Calling string
     * @param bool $checkClass Validate if defined class and method is existed
     *
     * @return array Parsed result
     */
    protected static function parseCallString($callString, $checkClass)
    {
        $callType = 0;
        $class = '';
        $method = '';

        if (strpos($callString, '::') !== false) {
            $splitedCallStr = explode('::', $callString, 2);

            if (!isset($splitedCallStr[0], $splitedCallStr[1])) {
                trigger_error(
                    'Key parameter in calling string "'
                    . $callString
                    . '" is lost. Please use Class::Method format.',
                    E_USER_ERROR
                );

                return false;
            }

            if ($checkClass) {
                if (!class_exists($splitedCallStr[0])) {
                    trigger_error(
                        'The static route handler class "'
                        . $splitedCallStr[0] .'" in calling string "'
                        . $callString
                        . '" can\'t be found.',
                        E_USER_ERROR
                    );

                    return false;
                }

                if (!method_exists($splitedCallStr[0], $splitedCallStr[1])) {
                    trigger_error(
                        'The static method "'
                        . $splitedCallStr[1]
                        . '" for handler class "'
                        . $splitedCallStr[0] .'" in calling string "'
                        . $callString
                        . '" can\'t be found.',
                        E_USER_ERROR
                    );

                    return false;
                }
            }

            $class = $splitedCallStr[0];
            $method = $splitedCallStr[1];
            $callType = static::CALL_TYPE_STATIC;
        } elseif (strpos($callString, '->') !== false) {
            $splitedCallStr = explode('->', $callString, 2);

            if (!isset($splitedCallStr[0], $splitedCallStr[1])) {
                trigger_error(
                    'Key parameter in calling string "'
                    . $callString
                    . '" is lost. Please use Class::Method format.',
                    E_USER_ERROR
                );

                return false;
            }

            if ($checkClass) {
                if (!class_exists($splitedCallStr[0])) {
                    trigger_error(
                        'The route handler class "'
                        . $splitedCallStr[0] .'" in calling string "'
                        . $callString
                        . '" can\'t be found.',
                        E_USER_ERROR
                    );

                    return false;
                }

                if (!method_exists($splitedCallStr[0], $splitedCallStr[1])) {
                    trigger_error(
                        'The method "'
                        . $splitedCallStr[1]
                        . '" for handler class "'
                        . $splitedCallStr[0] .'" in calling string "'
                        . $callString
                        . '" can\'t be found.',
                        E_USER_ERROR
                    );

                    return false;
                }
            }

            $class = $splitedCallStr[0];
            $method = $splitedCallStr[1];
            $callType = static::CALL_TYPE_INSTANCE;
        } else {
            if (!class_exists($callString)) {
                trigger_error(
                    'The handler class "'
                    . $callString
                    . '" can\'t be found.',
                    E_USER_ERROR
                );

                return false;
            }

            $class = $callString;
            $method = '';
            $callType = static::CALL_TYPE_METHOD;
        }

        return array(
            static::CALL_STRING_CLASS => $class,
            static::CALL_STRING_METHOD => $method,
            static::CALL_STRING_TYPE => $callType,
        );
    }

    /**
     * Export route map
     *
     * @return array The route map
     */
    public static function exportMap()
    {
        return self::$routeMap;
    }

    /**
     * Import route map
     *
     * @param array $maps The route map in valid format
     *
     * @return array The new route map
     */
    public static function importMap(array $maps)
    {
        return (self::$routeMap = $maps);
    }

    /**
     * Run the router
     *
     * @return mixed Return the result of respective path
     *         handlers or false of totally failed
     */
    public static function run()
    {
        $usedParams = self::$operatorParams = array();
        $lastPathOperator = null;
        $lastPathRef = &self::$routeMap;
        $callResult = $callError = null;

        if (!isset(self::$pathParams[0][0]) && !isset($lastPathRef['Subs'][''])) {
            self::execDefaultHandler();

            return false;
        }

        foreach (self::$pathParams as $param) {
            if (empty($lastPathRef['Subs'])) {
                self::execErrorHandler('PATH_NOT_FOUND');

                return false;
            }

            if (isset($lastPathRef['Subs'][$param])) {
                $lastPathRef = &$lastPathRef['Subs'][$param];

                continue;
            }

            if (is_numeric($param)) {
                if (strpos($param, '.') !== false && isset($lastPathRef['Subs']['?float'])) {
                    $lastPathRef = &$lastPathRef['Subs']['?float'];

                    $usedParams[] = (float)$param;

                    continue;
                }

                if (isset($lastPathRef['Subs']['?integer'])) {
                    $lastPathRef = &$lastPathRef['Subs']['?integer'];

                    $usedParams[] = (int)$param;

                    continue;
                }
            }

            if (isset($lastPathRef['Subs']['?'])) {
                $lastPathRef = &$lastPathRef['Subs']['?'];

                $usedParams[] = $param;

                continue;
            }

            foreach ($lastPathRef['Subs'] as $subKey => $subVal) {
                $preg = '';
                $keyLen = strlen($subKey);

                if ($keyLen > 2 && $subKey[0] == '{' && $subKey[$keyLen - 1] == '}') {
                    $preg = substr($subKey, 1, $keyLen - 2);
                }

                if ($preg && preg_match('/^(' . $preg . ')$/', $param)) {
                    $lastPathRef = &$lastPathRef['Subs'][$subKey];

                    $usedParams[] = $param;

                    continue 2;
                }
            }

            self::execErrorHandler('PATH_NOT_FOUND');

            return false;
            break;
        }

        if (isset($lastPathRef['Operator'])) {
            $lastPathOperator = &$lastPathRef['Operator'];
        }

        if (!$lastPathOperator) {
            self::execErrorHandler('PATH_NO_OPERATOR');

            return false;
        }

        if (!isset($lastPathOperator[static::ITEM_CALL_STRING])) {
            self::execErrorHandler('PATH_NO_OPERATOR_SPECIFIED');

            return false;
        }

        if (isset($lastPathOperator[static::ITEM_CALL_PARAMETER])) {
            foreach ($lastPathOperator[static::ITEM_CALL_PARAMETER] as $paramIndex) {
                if (isset($usedParams[$paramIndex])) {
                    self::$operatorParams[] = $usedParams[$paramIndex];
                } else {
                    self::$operatorParams[] = null;
                }
            }
        }

        $callResult = static::call(
            $lastPathOperator[static::ITEM_CALL_STRING],
            self::$operatorParams,
            $lastPathOperator[static::ITEM_CALL_CACHE],
            $callError
        );

        switch ($callError) {
            case static::HANDLER_FAIL_RESULT_FALSE:
                self::execErrorHandler('HANDLE_FAILED');
                break;

            case static::HANDLER_FAIL_NO_HANDLER:
                self::execErrorHandler('HANDLE_NO_HANDLER');
                break;

            case static::HANDLER_FAIL_NOT_ALLOWED:
                self::execErrorHandler('HANDLE_NOT_ALLOWED');
                break;

            default:
                break;
        }

        return $callResult;
    }

    /**
     * Call path handler
     *
     * @param array $callParameter The combined class and method name
     * @param array $parameters Parameters used for calling
     * @param bool $cacheCall Make object function core cache the class instance
     *
     * @return mixed Return false on false, return the calling result otherwise
     */
    protected static function call(array $callParameter, array $parameters, $cacheCall, &$failType)
    {
        $callResult = null;

        switch ($callParameter[static::CALL_STRING_TYPE]) {
            case static::CALL_TYPE_STATIC:
                $callResult = Framework::core('object')->callFunction(
                    array(
                        $callParameter[static::CALL_STRING_CLASS],
                        $callParameter[static::CALL_STRING_METHOD]
                    ),
                    $parameters
                );
                break;

            case static::CALL_TYPE_INSTANCE:
                $callResult = Framework::core('object')->callFunction(
                    array(
                        Framework::core('object')->getInstance(
                            $callParameter[static::CALL_STRING_CLASS],
                            array(),
                            $cacheCall
                        ),
                        $callParameter[static::CALL_STRING_METHOD]
                    ),
                    $parameters
                );
                break;

            case static::CALL_TYPE_METHOD:
                $instance = Framework::core('object')->getInstance(
                    $callParameter[static::CALL_STRING_CLASS],
                    array(),
                    $cacheCall
                );

                $accessMethod = Framework::core('request')->getClientInfo('method');

                if (!isset(static::$allowedMethods[$accessMethod])) {
                    $failType = static::HANDLER_FAIL_NOT_ALLOWED;

                    return false;
                }

                if (!method_exists($instance, $accessMethod)) {
                    $failType = static::HANDLER_FAIL_NO_HANDLER;

                    return false;
                }

                $callResult = Framework::core('object')->callFunction(
                    array($instance, $accessMethod),
                    $parameters
                );
                break;

            default:
                break;
        }

        if ($callResult) {
            return $callResult;
        }

        $failType = static::HANDLER_FAIL_RESULT_FALSE;

        return false;
    }

    /**
     * Get path parameters currently using
     *
     * @return array Path parameters
     */
    public static function getPath()
    {
        return self::$pathParams;
    }

    /**
     * Set the path parameters of the request
     *
     * @param string $path Set the path of the request
     *
     * @return bool Return true when successfully set, false otherwise
     */
    public static function setPath($path)
    {
        if (!is_null($path)
            && (self::$pathParams = explode(
                self::$routeSplit,
                trim($path, self::$routeSplit),
                256
            ))) {
            return true;
        }

        return false;
    }

    /**
     * Get parameters of the operators
     *
     * @return array Operator parameters
     */
    public static function getOperatorParam()
    {
        return self::$operatorParams;
    }

    /**
     * Set default handler
     *
     * @param closure $handler Handler that will handle
     *                         the request if pathParams is empty
     *
     * @return bool When set succeed, return true, or false for otherwise
     */
    public static function setDefaultHandler(\Closure $handler)
    {
        if (!self::$defaultHandler) {
            self::$defaultHandler = $handler;

            return true;
        } else {
            trigger_error(
                'ERROR_ROUTER_DEFAULT_HANDLER_EXISTED',
                E_USER_ERROR
            );
        }

        return false;
    }

    /**
     * Execute the default handler
     *
     * @return mixed Return the result of handler,
     *               or false when no handler has set
     */
    public static function execDefaultHandler()
    {
        $handler = null;

        if (is_callable(self::$defaultHandler)) {
            $handler = self::$defaultHandler;

            return $handler();
        } else {
            trigger_error(
                'ERROR_ROUTER_DEFAULT_HANDLER_UNCALLABLE',
                E_USER_ERROR
            );

            return false;
        }

        return false;
    }

    /**
     * Set error handler
     *
     * @param closure $handler Handler that will handle the request if
     *                         requested path not found in pathParams
     *
     * @return bool true when succeed, or false for otherwise
     */
    public static function setErrorHandler(\Closure $handler)
    {
        if (!self::$errorHandler) {
            self::$errorHandler = $handler;

            return true;
        } else {
            trigger_error(
                'ERROR_ROUTER_ERROR_HANDLER_EXISTED',
                E_USER_ERROR
            );
        }

        return false;
    }

    /**
     * Execute the error handler
     *
     * @param string $type Type of the error
     *
     * @return bool Return the result of the handler,
     *              or false for when handler not set
     */
    private static function execErrorHandler($type)
    {
        $handler = null;

        if (is_callable(self::$errorHandler)) {
            $handler = self::$errorHandler;

            return $handler($type);
        } else {
            trigger_error(
                'ERROR_ROUTER_ERROR_HANDLER_UNCALLABLE',
                E_USER_ERROR
            );

            return false;
        }

        return false;
    }
}
