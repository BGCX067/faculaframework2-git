<?php

/**
 * Core Factory
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

namespace Facula\Base\Factory;

use Facula\Base\Error\Factory\Core as Error;
use Facula\Base\Implement\Factory\Core as Implement;
use Facula\Framework;

/**
 * Core Factory
 *
 * A base factory needs to be include by every core factories
 */
abstract class Core implements Implement
{
    /** Instances of function cores */
    private static $instances = array();

    /**
     * Get a new function core instance
     *
     * @param array $cfg Function core configuration for initialization
     * @param array $common Common configuration for initialization
     * @param \Facula\Framework $parent Instance of Facula Framework itself.
     *
     * @return mixed Return a instance of function core when success, false otherwise
     */
    final public static function getInstance(
        array $cfg,
        array $common,
        Framework $parent
    ) {
        $caller = get_called_class();
        // If $cfg['Core'] has beed set, means user wants
        // to use their own core instead of default one

        if (isset($cfg['Custom'][0])) {
            $class = $cfg['Custom'];
        } elseif (isset(static::$default)) {
            $class = static::$default;
        } else {
            new Error(
                'CLASS_NOTFOUND',
                array(
                    $caller
                ),
                'ERROR'
            );

            return false;
        }

        if (!isset(self::$instances[$class])) {
            if (!class_exists($class)) {
                new Error(
                    'CLASS_NOTLOAD',
                    array(
                        $class
                    ),
                    'ERROR'
                );

                return false;
            }

            $classInterface = class_implements($class);
            if (!isset($classInterface[static::$interface])) {
                new Error(
                    'CLASS_INTERFACE',
                    array(
                        $class,
                        static::$interface
                    ),
                    'ERROR'
                );

                return false;
            }

            if (!is_subclass_of(
                $class,
                'Facula\Base\Prototype\Core'
            )) {
                new Error(
                    'CLASS_BASE',
                    array(
                        $class,
                        'Facula\\Base\\Prototype\\Core'
                    ),
                    'ERROR'
                );

                return false;
            }

            return (self::$instances[$class] = new $class(
                $cfg,
                $common,
                $parent
            ));
        }

        return self::$instances[$class];
    }
}
