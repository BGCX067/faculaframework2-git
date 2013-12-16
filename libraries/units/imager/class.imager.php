<?php

/*****************************************************************************
    Facula Framework Imager

    FaculaFramework 2013 (C) Rain Lee <raincious@gmail.com>

    @Copyright 2013 Rain Lee <raincious@gmail.com>
    @Author Rain Lee <raincious@gmail.com>
    @Package FaculaFramework
    @Version 2.0 prototype

    This file is part of Facula Framework.

    Facula Framework is free software: you can redistribute it and/or modify
    it under the terms of the GNU Lesser General Public License as published
    by the Free Software Foundation, version 3.

    Facula Framework is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public License
    along with Facula Framework. If not, see <http://www.gnu.org/licenses/>.
*******************************************************************************/

interface ImageHandlerInterface
{
    public function __construct($file, &$config = array());

    public function getLastError();
    public function getImageRes();

    public function resize($width, $height, $resizeSmall = false, $drawAreaWidth = 0, $drawAreaHeight = 0);
    public function ratioResize($width, $height, $resizeSmall = false);
    public function fillResize($width, $height);

    public function waterMark($file, $align = 'center center', $margin = 0);
    public function waterMarkText($text, $align = 'center center', $margin = 0, $color = array(255, 255, 255));

    public function save($file);
}

class Imager
{
    private static $handlerClassName = '';

    private static $setting = array(
        'Temp' => '',
        'Font' => '',
        'FontSize' => 12,
        'MemoryLimit' => 0,
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
                Facula::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTFOUND', 'imager', true);

                return false;
            }
        }

        $className = __CLASS__ . $type;

        if (class_exists($className)) {
            self::$handlerClassName = $className;

            self::$setting = array(
                'MemoryLimit' => (int)((PHPIni::convertIniUnit(ini_get('memory_limit')) * 0.8) - memory_get_peak_usage()),
                'Font' => isset($setting['Font']) && is_readable($setting['Font']) ? $setting['Font'] : null,
                'FontSize' => isset($setting['FontSize']) && is_readable($setting['FontSize']) ? $setting['FontSize'] : 12,
            );

            return true;
        } else {
            Facula::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTFOUND', 'imager', true);
        }

        return false;
    }

    public static function get($file)
    {
        $handler = null;

        if (self::$handlerclassName) {
            $handler = new self::$handlerClassName($file, self::$setting);

            if ($handler instanceof ImageHandlerInterface) {
                if ($handler->getImageRes()) {
                    return $handler;
                }
            } else {
                Facula::core('debug')->exception('ERROR_IMAGE_HANDLER_INTERFACE_INVALID', 'imager', true);
            }
        } else {
            Facula::core('debug')->exception('ERROR_IMAGE_HANDLER_NOTSPECIFIED', 'imager', true);
        }

        return false;
    }
}

class ImageCommon
{
    protected function getAlignPos($alignType, $imageWidth, $imageHeight, $subjectWidth, $subjectHeight, $margin = 0)
    {
        $result = array(0, 0);

        switch ($alignType) {
            // Tops
            case 'top left':
                $result[0] = $margin;
                $result[1] = $margin;
                break;

            case 'top center':
                $result[0] = (int)(($imageWidth) - ($subjectWidth / 2));
                $result[1] = $margin;
                break;

            case 'top right':
                $result[0] = (int)($imageWidth - $subjectWidth) - $margin;
                $result[1] = $margin;
                break;

            // Center
            case 'center left':
                $result[0] = $margin;
                $result[1] = (int)(($imageHeight / 2) - ($subjectHeight / 2));
                break;

            case 'center right':
                $result[0] = (int)($imageWidth - $subjectWidth) - $margin;
                $result[1] = (int)(($imageHeight / 2) - ($subjectHeight / 2));
                break;

            // Buttons
            case 'bottom left':
                $result[0] = $margin;
                $result[1] = (int)($imageHeight - $subjectHeight) - $margin;
                break;

            case 'bottom center':
                $result[0] = (int)(($imageWidth / 2) - ($subjectWidth / 2));
                $result[1] = (int)($imageHeight - $subjectHeight) - $margin;
                break;

            case 'bottom right':
                $result[0] = (int)($imageWidth - $subjectWidth) - $margin;
                $result[1] = (int)($imageHeight - $subjectHeight) - $margin;
                break;

            // Center Center
            default:
                $result[0] = (int)(($imageWidth / 2) - ($subjectWidth / 2));
                $result[1] = (int)(($imageHeight / 2) - ($subjectHeight / 2));
                break;
        }

        return $result;
    }
}
