<?php

/**
 * Route Selector
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

namespace Facula\Unit;

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
    /** Character will be use to split the route */
    public static $routeSplit = '/';

    /** Route Map */
    private static $routeMap = array();

    /** Handler that will be use when no route specified */
    private static $defaultHandler = null;

    /** Handler that will be use when the route not be found */
    private static $errorHandler = null;

    /** Requested path */
    private static $pathParams = array();

    /** Handlers that will be executed when path is matched */
    private static $operatorParams = array();

    /**
     * Set up the route
     *
     * @param array $paths Paths with Path => Operator pair
     *
     * @return bool Always return true
     */
    public static function setup(array $paths)
    {
        $tempLastRef = $tempLastUsedRef = null;

        foreach ($paths as $path => $operator) {
            $tempLastRef = &self::$routeMap;

            foreach (explode(
                self::$routeSplit,
                trim($path, self::$routeSplit)
            ) as $key => $val) {
                $val = $val ? $val : '?';

                $tempLastUsedRef = &$tempLastRef[$val];

                if (isset($tempLastRef[$val])) {
                    $tempLastRef = &$tempLastRef[$val]['Subs'];
                } else {
                    $tempLastRef[$val] = array('Subs' => array());
                    $tempLastRef = &$tempLastRef[$val]['Subs'];
                }
            }

            $tempLastUsedRef['Operator'] = $operator;
        }

        return true;
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

        if (isset(self::$pathParams[0]) && self::$pathParams[0] != '') {
            foreach (self::$pathParams as $param) {
                if (isset($lastPathRef[$param])) {
                    $lastPathRef = &$lastPathRef[$param];
                } elseif (isset($lastPathRef['?'])) {
                    $lastPathRef = &$lastPathRef['?'];
                    $usedParams[] = $param;
                } else {
                    return self::execErrorHandler('PATH_NOT_FOUND');
                    break;
                }

                if (isset($lastPathRef['Operator'])) {
                    $lastPathOperator = &$lastPathRef['Operator'];
                }

                $lastPathRef = &$lastPathRef['Subs'];
            }

            if ($lastPathOperator) {
                if (isset($lastPathOperator[0])) {
                    if (isset($lastPathOperator[1])) {
                        foreach ($lastPathOperator[1] as $paramIndex) {
                            if (isset($usedParams[$paramIndex])) {
                                self::$operatorParams[] = $usedParams[$paramIndex];
                            } else {
                                self::$operatorParams[] = null;
                            }
                        }
                    }

                    return \Facula\Framework::core('object')->run(
                        $lastPathOperator[0],
                        self::$operatorParams,
                        true
                    );
                } else {
                    return self::execErrorHandler('PATH_NO_OPERATOR_SPECIFIED');
                }
            } else {
                return self::execErrorHandler('PATH_NO_OPERATOR');
            }
        } else {
            return self::execDefaultHandler();
        }

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
            \Facula\Framework::core('debug')->exception(
                'ERROR_ROUTER_DEFAULT_HANDLER_EXISTED',
                'router',
                true
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
            \Facula\Framework::core('debug')->exception(
                'ERROR_ROUTER_DEFAULT_HANDLER_UNCALLABLE',
                'router',
                true
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
            \Facula\Framework::core('debug')->exception(
                'ERROR_ROUTER_ERROR_HANDLER_EXISTED',
                'router',
                true
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
            \Facula\Framework::core('debug')->exception(
                'ERROR_ROUTER_ERROR_HANDLER_UNCALLABLE',
                'router',
                true
            );

            return false;
        }

        return false;
    }
}
