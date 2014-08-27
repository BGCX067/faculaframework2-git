<?php

/**
 * HttpGet Source
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

namespace Facula\Unit\Input\Source;

use Facula\Unit\Input\Base\Source;
use Facula\Unit\Input\Base\Error;
use Facula\Framework;

/**
 * HttpGet Source
 */
class HttpGet extends Source
{
    /** The request core instance */
    protected $request = null;

    /** Is this source has been accepted? */
    protected $accepted = true;

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->request = Framework::core('request');
    }

    /**
     * Get the field data from the source
     *
     * @param string $fieldName The name of get field
     *
     * @return string The data in the get field
     */
    public function get($fieldName)
    {
        $inputVal = $this->request->getGet($fieldName);

        if (is_null($inputVal)) {
            return null;
        } elseif ((is_string($inputVal)
        || is_numeric($inputVal)
        || is_integer($inputVal)
        || is_float($inputVal))
        && !$inputVal) {
            return null;
        }

        return $inputVal;
    }

    /**
     * Set outbound allowance
     *
     * @param bool $allowed Allow it or not
     *
     * @return object Current instance of source
     */
    public function outbound($allowed)
    {
        if (!$allowed
        && !$this->request->getClientInfo('fromSelf')) {
            $this->error(new Error('ERROR', 'OUTBOUND_SUBMIT'));

            $this->accepted = false;
        }

        return $this;
    }

    /**
     * Set and check hash field
     *
     * @param string $fieldName The field that hash should be in
     * @param string $correctHash The correct hash value
     *
     * @return object Current instance of source
     */
    public function hash($fieldName, $correctHash)
    {
        if ($this->request->getGet($fieldName) != $correctHash) {
            $this->error(new Error('ERROR', 'SUBMIT_HASH'));

            $this->accepted = false;
        }

        return $this;
    }

    /**
     * Check if the source is accepted
     *
     * @return bool Return the result of acception
     */
    public function accepted()
    {
        return $this->accepted;
    }
}
