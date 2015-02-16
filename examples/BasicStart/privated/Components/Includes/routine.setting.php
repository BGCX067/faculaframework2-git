<?php

/**
 * Framework Demo: Setting routine
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
 * @version    0.1 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

/**
 * Register the site setting
 */
Facula\App\Setting::registerSetting('Site', function () {
    return array(); // Return a empty.
}, array('MyProject\Controller\Home\Index', 'MyProject\Demo\Controller\API'));
