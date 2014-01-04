<?php

/**
 * Base Factory for factories which based on adapter registration
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
 * Extends by operator based factory
 */
abstract class Operator
{
    /** Default container for operators if child classes not overwrite */
    protected static $operators = array();

    /**
     * Register a operator
     *
     * @param string $operator The name of operator
     * @param string $operatorClass The Class name of operator
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function registerOperator($operator, $operatorClass)
    {
        if (!isset(static::$operators[$operator]) && class_exists($operatorClass)) {
            static::$operators[$operator] = $operatorClass;

            return true;
        }

        return false;
    }

    /**
     * Unregister a operator
     *
     * @param string $operator The name of operator
     *
     * @return bool Return true when succeed, false otherwise
     */
    public static function unregisterOperator($operator)
    {
        if (isset(static::$operators[$operator])) {
            unset(static::$operators[$operator]);

            return true;
        }

        return false;
    }

    /**
     * Get a operator
     *
     * @param string $operator The name of operator
     *
     * @return bool Return the class name of operator when succeed, false otherwise
     */
    protected static function getOperator($operator)
    {
        if (isset(static::$operators[$operator])) {
            return static::$operators[$operator];
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_OPERATORFACTORY_OPERATOR_NOT_FOUND|' . get_called_class() . '::' . $operator,
                'operator factory',
                true
            );
        }

        return false;
    }
}
