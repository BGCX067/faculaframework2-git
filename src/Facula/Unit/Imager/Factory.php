<?php

/**
 * Imager Factory
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

namespace Facula\Unit\Imager;

use Facula\Unit\Imager\Exception as Exception;
use Facula\Base\Factory\Operator as BaseFactory;
use Facula\Base\Tool\PHP\Ini as Ini;

/**
 * Imager Factory
 */
class Factory extends BaseFactory
{
    /** Handler class name */
    private static $handlerClassName = '';

    /** Settings */
    private static $setting = array(
        'Font' => '',
        'FontSize' => 12,
        'MemoryLimit' => 0,
    );

    /** Declare default operators */
    protected static $operators = array(
        'GD' => '\Facula\Unit\Imager\Operator\GD',
    );

    /**
     * Setup and initialize the class
     *
     * @param array $setting Global setting for imager
     * @param string $type Specify the image handler
     *
     * @return bool Return true when succeed. false when fail
     */
    public static function setup(array $setting, $type = '')
    {
        if (!$type) {
            if (extension_loaded('gd')) {
                $type = 'GD';
            } elseif (extension_loaded('gmagick')) {
                $type = 'Gmagick';
            } elseif (extension_loaded('imagick')) {
                $type = 'Imagick';
            } else {
                throw new Exception\ImageHandlerTypeUnknown(
                    $type
                );

                return false;
            }
        }

        $className = static::getOperator($type);

        if (class_exists($className)) {
            self::$handlerClassName = $className;

            static::$setting = array(
                'MemoryLimit' =>
                    (Ini::getBytes('memory_limit', '3M') - memory_get_peak_usage()) * 0.9,

                'Font' => isset($setting['Font']) && is_readable($setting['Font'])
                    ? static::$setting['Font'] : null,

                'FontSize' => isset($setting['FontSize'])
                    ? static::$setting['FontSize']  : 12,
            );

            return true;
        } else {
            throw new Exception\ImageHandlerClassNotFound(
                $className
            );
        }

        return false;
    }

    /**
     * Get a image hander with file
     *
     * @param array $file Path to the file
     * @param string $error Reference for error feedback
     *
     * @return mixed Return the image handler instance for success or false for fail
     */
    public static function get($file, &$error = '')
    {
        $handler = null;

        if (self::$handlerClassName) {
            $handler = new self::$handlerClassName($file, static::$setting);

            if ($handler instanceof OperatorImplement) {
                if ($handler->getImageRes()) {
                    return $handler;
                } else {
                    $error = $handler->getLastError();
                }
            } else {
                throw new Exception\ImageHandlerInterfaceInvaild(
                    $className
                );
            }
        } else {
            throw new Exception\ImageHandlerNotSpecified();
        }

        return false;
    }
}
