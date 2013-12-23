<?php

/**
 * SMTP Socketor
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

namespace Facula\Unit\SMTP;

/**
 * SMTP Socket
 */
class Socket
{
    /** Socket connection */
    private $connection = null;

    /** Container of parser */
    private $responseParsers = array();

    /** Host name */
    private $host = 'localhost';

    /** Host port */
    private $port = 0;

    /** Timeout */
    private $timeout = 0;

    /**
     * Constructor of the socket operator
     *
     * @param string $host Host name
     * @param integer $port Host Port
     * @param integer $timeout Connection timeout
     */
    public function __construct($host, $port, $timeout)
    {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;
    }

    /**
     * Destructor of the socket operator
     *
     * @param string $host Host name
     * @param integer $port Host Port
     * @param integer $timeout Connection timeout
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open a new socket
     *
     * @param string &$error String of error number
     * @param string &$errorstr String of error message
     *
     * @return bool Return true when success, or false when fail
     */
    public function open(&$error, &$errorstr)
    {
        if (function_exists('fsockopen')) {
            if ($this->connection = fsockopen(
                $this->host,
                $this->port,
                $error,
                $errorstr,
                $this->timeout
            )) {
                stream_set_blocking($this->connection, true);
                stream_set_timeout($this->connection, $this->timeout);

                return true;
            }
        } else {
            \Facula\Framework::core('debug')->exception('ERROR_SMTP_SOCKET_DISABED', 'smtp', true);
        }

        return false;
    }

    /**
     * Send socket data
     *
     * @param string $command The data to send
     * @param mixed $getReturn Return method of response data
     *
     * @return bool Return a return according to $getReturn, or false when fail
     */
    public function put($command, $getReturn = false)
    {
        if ($this->connection) {
            if (fputs($this->connection, $command . "\r\n")) {
                switch ($getReturn) {
                    case false:
                        return true;

                        break;

                    case 'one':
                        return $this->get(true);

                        break;

                    case 'last':
                        return $this->getLast(true);

                        break;
                }
            } else {
                return false;
            }
        }
    }

    /**
     * Get response
     *
     * @param bool $parseResponse Trigger to enable or disable response parsing
     * @param bool $hasNext There still remaining data
     *
     * @return bool Return response when got any, or false when fail
     */
    public function get($parseResponse = false, &$hasNext = false)
    {
        $response = null;
        $dashPOS = $spacePOS = null;
        $hasNext = false; // Reassign this as we referred it.

        if ($this->connection) {
            if ($response = trim(fgets($this->connection, 512))) {
                // If response contain a '-' and all char before the - is numberic (response code)
                if ((($dashPOS = strpos($response, '-')) !== false)
                    && is_numeric(substr($response, 0, $dashPOS))) {
                    $hasNext = true;
                } elseif ((($spacePOS = strpos($response, ' ')) !== false)
                    && is_numeric(substr($response, 0, $spacePOS))) {
                    // Only when response contain a ' ' and all char before the ' ' is number (response code)
                    // Means the response got only one line (Or this is the end of responses)
                    $hasNext = false;
                } else {
                    $hasNext = true;
                }

                if ($parseResponse) {
                    $response = $this->parseResponse($response);
                }

                return $response ? $response : null;
            }
        }

        return false;
    }

    /**
     * Get last line of the response
     *
     * @param bool $parseResponse Trigger to enable or disable response parsing
     *
     * @return bool Return the last response
     */
    public function getLast($parseResponse = false)
    {
        $response = $responseLast = null;
        $responseHasNext = false;

        while (($response = $this->get($parseResponse, $responseHasNext)) !== false) {
            $responseLast = $response;

            if (!$responseHasNext) {
                break;
            }
        }

        return $responseLast;
    }

    /**
     * Close socket connection
     *
     * @return bool Return true when success, or false when fail
     */
    public function close()
    {
        if ($this->connection) {
            if ($this->put('QUIT')) {
                $this->connection = null;

                return true;
            };
        }

        return false;
    }

    /**
     * Register a new response parser
     *
     * @param mixed $responseCode The response code
     * @param closure $parser Callback of the parser itself
     *
     * @return bool Return true when success, or false when fail
     */
    public function registerResponseParser($responseCode, \Closure $parser)
    {
        if (!isset($this->responseParsers[$responseCode])) {
            $this->responseParsers[$responseCode] = $parser;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_SMTP_SOCKET_RESPONSE_PARSER_EXISTED',
                'smtp',
                true
            );
        }

        return false;
    }

    /**
     * Parse the response using registered handler
     *
     * @param mixed $response The response code
     *
     * @return bool Return the result of parsed response code, or false for fail
     */
    private function parseResponse($response)
    {
        $responseParam = $parserName = $splitType = '';
        $responseCode = $responseCode = 0;
        $responseParams = array();
        $parser = null;

        if ($responseContent = trim($response)) {
            // Position check seems the only stable way is do determine which
            // we will use ('-' OR ' ').
            if (($fstSpacePos = strpos($responseContent, ' ')) === false) {
                $fstSpacePos = null;
            }

            if (($fstDashPos = strpos($responseContent, '-')) === false) {
                $fstDashPos = null;
            }

            if (is_null($fstDashPos) && $fstSpacePos) {
                $splitType = 'SPACE';
            } elseif (is_null($fstSpacePos) && $fstDashPos) {
                $splitType = 'DASH';
            } elseif ($fstDashPos && $fstDashPos < $fstSpacePos) {
                $splitType = 'DASH';
            } elseif ($fstSpacePos && $fstSpacePos < $fstDashPos) {
                $splitType = 'SPACE';
            } else {
                $splitType = 'UNKONWN';
            }

            // Use splitType to determine how to split response
            switch ($splitType) {
                case 'UNKONWN':
                    $responseParams[] = $responseContent;
                    break;

                case 'DASH':
                    $responseParams = explode('-', $responseContent, 2);
                    break;

                case 'SPACE':
                    $responseParams = explode(' ', $responseContent, 2);
                    break;
            }

            if (isset($responseParams[0])
                && $responseParams[0]
                && is_numeric($responseParams[0])) {
                $responseCode = (int)($responseParams[0]);
            }

            if (isset($responseParams[1]) && $responseParams[1]) {
                $responseParam = $responseParams[1];
            }

            // Check if parser's existed.
            if (isset($this->responseParsers[$responseCode])) {
                $parser = $this->responseParsers[$responseCode];

                return $parser($responseParam);
            }

            return $responseCode;
        }

        return false;
    }
}
