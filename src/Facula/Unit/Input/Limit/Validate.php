<?php

/**
 * Validate Limit
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

namespace Facula\Unit\Input\Limit;

use Facula\Unit\Input\Base\Limit as Base;
use Facula\Unit\Input\Base\Field\Error as Error;
use Facula\Unit\Validator;

/**
 * Validate Limit
 */
class Validate extends Base
{
    /** Default format */
    protected $format = '';

    /** Max length */
    protected $maxlen = 0;

    /** Min length */
    protected $minlen = 0;

    /**
     * Check if the input is valid
     *
     * @param mixed $value The value to check
     * @param Error $error The reference for getting error feedback
     *
     * @return bool Return True when it's qualified, false otherwise
     */
    public function qualified(&$value, &$error)
    {
        $formatError = '';

        if (!is_string($value)) {
            $error = new Error('INVALID', 'DATATYPE', array(gettype($value)));

            return false;
        }

        if (!Validator::check($value, $this->format, $this->maxlen, $this->minlen, $formatError)) {
            switch ($formatError) {
                case 'TOOLONG':
                    $error = new Error('INVALID', 'TOOLONG', array(
                        'Max' => $this->maxlen
                    ));
                    break;

                case 'TOOSHORT':
                    $error = new Error('INVALID', 'TOOSHORT', array(
                        'Min' => $this->minlen
                    ));
                    break;

                default:
                    $error = new Error('INVALID', $formatError);
                    break;
            }


            return false;
        }

        return true;
    }

    /**
     * Set the format
     *
     * @param string $format The name of format
     *
     * @return object Return current object instance
     */
    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    /**
     * Set the max length
     *
     * @param integer $maxLen The max length
     *
     * @return object Return current object instance
     */
    public function maxlen($maxLen)
    {
        $this->maxlen = $maxLen;

        return $this;
    }

    /**
     * Set the min length
     *
     * @param integer $minlen The min length
     *
     * @return object Return current object instance
     */
    public function minlen($minlen)
    {
        $this->minlen = $minlen;

        return $this;
    }
}
