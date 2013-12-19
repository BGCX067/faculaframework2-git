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

interface AdapterImplement
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
