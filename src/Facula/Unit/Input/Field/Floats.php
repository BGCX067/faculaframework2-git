<?php

/**
 * Float Field
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

namespace Facula\Unit\Input\Field;

use Facula\Unit\Input\Base\Field as Base;

/**
 * Float Fields
 */
class Floats extends Base
{
    /** Set the resulting class */
    protected static $resulting =
        'Facula\Unit\Input\Resulting\Floats';

    /**
     * Check imported data, and provide a valid fail value if needed
     *
     * @param mixed $value Inputing value
     * @param mixed $newValue Reference to a new input value used to replace the invalid one
     * @param mixed $error Reference to get error feedback
     *
     * @return bool Return false to truncate value input, true otherwise.
     */
    protected function parseImport($value, &$newValue, &$errorRef)
    {
        if (is_numeric($value)) {
            // No nothing if that is a integer
            if (is_float($value)) {
                return true;
            }

            $newValue = (float)$value;

            return false;
        }

        $errorRef = new Error(
            'INVALID',
            'DATATYPE'
        );

        $newValue = 0.0;

        return false;
    }
}
