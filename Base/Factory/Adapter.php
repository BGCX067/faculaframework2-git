<?php

/**
 * Base Factory for factories which base on adapter registration
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
 * Extends by adapter based factory
 */
abstract class Adapter
{
    /** Default container for adapters if child classes not overwrite */
    protected static $adapters = array();

    /**
     * Register a adapter
     *
     * @param string $adapter The name of adapter
     * @param string $adapterClass The Class name of adapter
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function registerAdapter($adapter, $adapterClass)
    {
        if (!isset(static::$adapters[$adapter]) && class_exists($adapterClass)) {
            static::$adapters[$adapter] = $adapterClass;

            return true;
        }

        return false;
    }

    /**
     * Unregister a adapter
     *
     * @param string $adapter The name of adapter
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function unregisterAdapter($adapter)
    {
        if (isset(static::$adapters[$adapter])) {
            unset(static::$adapters[$adapter]);

            return true;
        }

        return false;
    }

    /**
     * Get a adapter
     *
     * @param string $adapter The name of adapter
     *
     * @return bool Return the class name of adapter when succeed, false otherwise
     */
    protected static function getAdapter($adapter)
    {
        if (isset(static::$adapters[$adapter])) {
            return static::$adapters[$adapter];
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_ADAPTERFACTORY_ADAPTER_NOT_FOUND|' . get_called_class() . '::' . $adapter,
                'adapterfactory',
                true
            );
        }

        return false;
    }
}
