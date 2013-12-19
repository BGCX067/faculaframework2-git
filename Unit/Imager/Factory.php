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

namespace Facula\Unit\Imager;

class Factory extends \Facula\Base\Factory\Adapter
{
    private static $handlerClassName = '';

    private static $setting = array(
        'Temp' => '',
        'Font' => '',
        'FontSize' => 12,
        'MemoryLimit' => 0,
    );

    protected static $adapters = array(
        'GD' => 'Facula\Unit\Imager\Adapter\GD',
        'Gmagick' => 'Facula\Unit\Imager\Adapter\Gmagick',
        'Imagick' => 'Facula\Unit\Imager\Adapter\Imagick',
    );

    public static function setup($setting, $type = '')
    {
        if (!$type) {
            if (extension_loaded('gd')) {
                $type = 'GD';
            } elseif (extension_loaded('gmagick')) {
                $type = 'Gmagick';
            } elseif (extension_loaded('imagick')) {
                $type = 'Imagick';
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTFOUND', 'imager', true);

                return false;
            }
        }

        $className = static::getAdapter($type);

        if (class_exists($className)) {
            self::$handlerClassName = $className;

            self::$setting = array(
                'MemoryLimit' => (int)((\Facula\Base\Tool\Misc\PHPIni::convertIniUnit(ini_get('memory_limit')) * 0.8) - memory_get_peak_usage()),
                'Font' => isset($setting['Font']) && is_readable($setting['Font']) ? $setting['Font'] : null,
                'FontSize' => isset($setting['FontSize']) && is_readable($setting['FontSize']) ? $setting['FontSize'] : 12,
            );

            return true;
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTFOUND', 'imager', true);
        }

        return false;
    }

    public static function get($file)
    {
        $handler = null;

        if (self::$handlerClassName) {
            $handler = new self::$handlerClassName($file, self::$setting);

            if ($handler instanceof AdapterImplement) {
                if ($handler->getImageRes()) {
                    return $handler;
                }
            } else {
                \Facula\Framework::core('debug')->exception('ERROR_IMAGE_HANDLER_INTERFACE_INVALID', 'imager', true);
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTSPECIFIED', 'imager', true);
        }

        return false;
    }
}

