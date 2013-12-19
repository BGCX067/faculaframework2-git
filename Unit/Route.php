<?php

/**
 * Facula Framework Struct Manage Unit
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

abstract class Route
{
    public static $routeSplit = '/';
    private static $routeMap = array();

    private static $defaultHandler = null;
    private static $errorHandler = null;

    private static $pathParams = array();
    private static $operatorParams = array();

    public static function setup(array $paths)
    {
        $tempLastRef = $tempLastUsedRef = null;

        foreach ($paths as $path => $operator) {
            $tempLastRef = &self::$routeMap;

            foreach (explode(self::$routeSplit, trim($path, self::$routeSplit)) as $key => $val) {
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

    public static function exportMap()
    {
        return self::$routeMap;
    }

    public static function importMap(array $maps)
    {
        return (self::$routeMap = $maps);
    }

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
                    self::execErrorHandler('PATH_NOT_FOUND');

                    return false;
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

                    return \Facula\Framework::core('object')->run($lastPathOperator[0], self::$operatorParams, true);
                } else {
                    return self::execErrorHandler('PATH_NO_OPERATOR_SPECIFIED');
                }
            } else {
                self::execErrorHandler('PATH_NO_OPERATOR');
            }
        } else {
            self::execDefaultHandler();
        }

        return false;
    }

    public static function getPath()
    {
        return self::$pathParams;
    }

    public static function setPath($path)
    {
        if ($path !== null && (self::$pathParams = explode(self::$routeSplit, trim($path, self::$routeSplit), 256))) {
            return true;
        }

        return false;
    }

    public static function getParam()
    {
        return self::$operatorParams;
    }

    public static function setDefaultHandler(\Closure $handler)
    {
        if (!self::$defaultHandler) {
            self::$defaultHandler = $handler;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_ROUTER_DEFAULT_HANDLER_EXISTED', 'router', true);
        }

        return false;
    }

    public static function execDefaultHandler()
    {
        $handler = null;

        if (is_callable(self::$defaultHandler)) {
            $handler = self::$defaultHandler;

            return $handler();
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_ROUTER_DEFAULT_HANDLER_UNCALLABLE', 'router', true);
            return false;
        }

        return false;
    }

    public static function setErrorHandler(\Closure $handler)
    {
        if (!self::$errorHandler) {
            self::$errorHandler = $handler;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_ROUTER_ERROR_HANDLER_EXISTED', 'router', true);
        }

        return false;
    }

    private static function execErrorHandler($type)
    {
        $handler = null;

        if (is_callable(self::$errorHandler)) {
            $handler = self::$errorHandler;

            return $handler($type);
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_ROUTER_ERROR_HANDLER_UNCALLABLE', 'router', true);
            return false;
        }

        return false;
    }
}
