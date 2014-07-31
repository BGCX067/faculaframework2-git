<?php

/**
 * Manual Source
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
 * Manual Source
 */
class Manual extends Source
{
    /** The data of manual source */
    protected $parameters = array();

    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Set the parameter data into source
     *
     * @param array $parameters The parameter data
     *
     * @return Current instance of Manual Source
     */
    public function set(array $parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * Get the field data from the source
     *
     * @param string $fieldName The name of post field
     *
     * @return string The data in the post field
     */
    public function get($fieldName)
    {
        if (isset($this->parameters[$fieldName])) {
            return $this->parameters[$fieldName];
        }

        return null;
    }

    /**
     * Check if the source is accepted
     *
     * @return bool Return the result of acception
     */
    public function accepted()
    {
        return true;
    }
}
