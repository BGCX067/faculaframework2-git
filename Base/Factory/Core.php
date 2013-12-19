<?php

/**
 * Core Base Factory
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

namespace Facula\Base\Factory;

/**
 * Base factory for all built-in cores
 */
abstract class Core implements \Facula\Base\Implement\Factory\Core
{
    private static $instances = array();

    final public static function getInstance(array $cfg, array $common, \Facula\Framework $parent)
    {
        $caller = get_called_class();
        // If $cfg['Core'] has beed set, means user wants to use their own core instead of default one

        if (isset($cfg['Custom'][0])) {
            $class = $cfg['Custom'];
        } elseif (isset(static::$default)) {
            $class = static::$default;
        } else {
            throw new \Exception(
                'Producing core instance for '
                . $caller
                . '. But no class specified.'
            );
        }

        if (!isset(self::$instances[$class])) {
            if (!class_exists($class)) {
                throw new \Exception(
                    'Facula core '
                    . $class
                    . ' is not loadable. Please make sure object file has been included before preform this task.'
                );

                return false;
            }

            // Create and check new instance
            if ($caller::checkInstance(self::$instances[$class] = new $class($cfg, $common, $parent))) {
                return self::$instances[$class];
            } else {
                throw new Exception(
                    'An error happened when facula creating core '
                    . $class
                    . '.'
                );

                return false;
            }
        }

        return self::$instances[$class];
    }

    final protected static function checkInstance($instance)
    {
        $classInstance = get_class($instance);
        $classInterface = '';

        if (!($instance instanceof \Facula\Base\Prototype\Core)) {
            throw new \Exception(
                'Facula core '
                . $classInstance
                . ' must be extend from base class '
                . '\\Facula\\Base\\Prototype\\Core'
            );
        }

        if (!isset(static::$interface)) {
            return true;
        } else {
            $classInterface = static::$interface;
        }

        if ($instance instanceof $classInterface) {
            return true;
        } else {
            throw new \Exception(
                'Facula core '
                . $classInstance
                . ' needs to implements interface '
                . $classInterface
            );
        }

        return false;
    }
}
