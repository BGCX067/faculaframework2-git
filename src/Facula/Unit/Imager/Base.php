<?php

/**
 * Image Adapter Base Class
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

/**
 * Interface for image adapter
 */
abstract class Base
{
    /**
     * Get the position of other object for image inserting
     *
     * @param string $alignType Position type of the image
     * @param integer $imageWidth Width of the image
     * @param integer $imageHeight Height of the image
     * @param integer $subjectWidth Width of the result image
     * @param integer $subjectHeight Height of the result image
     * @param integer $margin Margin for image
     *
     * @return array The position of X, Y value in array
     */
    protected function getAlignPos(
        $alignType,
        $imageWidth,
        $imageHeight,
        $subjectWidth,
        $subjectHeight,
        $margin = 0
    ) {
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
