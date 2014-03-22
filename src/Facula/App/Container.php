<?php

/**
 * IoC Container
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

namespace Facula\App;

/**
 * IoC Container
 */
abstract class Container
{
    /** Storage all injecting object */
    private static $contains = array();

    /**
     * Found out who is the caller when call from outside of the extends chain.
     *
     * @return mixed Name of the caller when succeeded, or null otherwise
     */
    private static function getCallerClass()
    {
        $debug = array();
        $btResult = debug_backtrace(
            DEBUG_BACKTRACE_PROVIDE_OBJECT + DEBUG_BACKTRACE_IGNORE_ARGS
        );

        $debug[] = array_shift($btResult);
        $debug[] = array_shift($btResult);

        foreach ($btResult as $bt) {
            if (isset($bt['function'])
                && strpos(strtolower($bt['function']), '{closure') !== false) {
                continue;
            }

            if (isset($bt['class'])) {
                return $bt['class'];
                break;
            }
        }

        return null;
    }

    /**
     * Register a new inject
     *
     * @param string $name Name of the injection
     * @param Closure $processor Injection it self
     * @param mixed $accesser Permitted accesser(s)
     *
     * @return bool Return true when succeeded
     */
    public static function register($name, \Closure $processor, $accesser = false)
    {
        $accessers = array();

        if (is_array($accesser)) {
            $accessers = $accesser;
        } elseif (is_string($accesser)) {
            $accessers = $accesser ? array($accesser) : array();
        } elseif (is_bool($accesser)) {
            if ($accesser) {
                $accessers = array('!PUBLIC!');
            } elseif ((($accesser = get_called_class()) != __CLASS__)
                    || ($accesser = self::getCallerClass())) {
                $accessers = array($accesser);
            }
        }

        if (!isset(self::$contains[$name])) {
            self::$contains[$name] = array(
                'Processor' => $processor,
                'Accesser' => array_flip($accessers),
            );

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_CONTAINER_NAME_ALREADY_EXISTED|' . $name,
                'container',
                true
            );
        }

        return false;
    }

    /**
     * Call a inject
     *
     * @param string $name Name of the injection
     * @param Closure $args Parameters in array
     * @param mixed $default When inject call failed, call this for follow-up
     *
     * @return mixed Return value from injected function
     */
    public static function resolve($name, $args = array(), \Closure $default = null)
    {
        $accesser = '';

        if (isset(self::$contains[$name])) {
            if (isset(self::$contains[$name]['Accesser']['!PUBLIC!'])
                || (
                        (
                            (($accesser = get_called_class()) != __CLASS__)
                            || ($accesser = self::getCallerClass())
                        )
                        && isset(self::$contains[$name]['Accesser'][$accesser])
                    )
                ) {
                $selectedContain = self::$contains[$name]['Processor'];

                switch (count($args)) {
                    case 0:
                        return $selectedContain();
                        break;

                    case 1:
                        return $selectedContain($args[0]);
                        break;

                    case 2:
                        return $selectedContain(
                            $args[0],
                            $args[1]
                        );
                        break;

                    case 3:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2]
                        );
                        break;

                    case 4:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3]
                        );
                        break;

                    case 5:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4]
                        );
                        break;

                    case 6:
                        return $selectedContain($args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4],
                            $args[5]
                        );
                        break;

                    case 7:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4],
                            $args[5],
                            $args[6]
                        );
                        break;

                    case 8:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4],
                            $args[5],
                            $args[6],
                            $args[7]
                        );
                        break;

                    case 9:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4],
                            $args[5],
                            $args[6],
                            $args[7],
                            $args[8]
                        );
                        break;

                    case 10:
                        return $selectedContain(
                            $args[0],
                            $args[1],
                            $args[2],
                            $args[3],
                            $args[4],
                            $args[5],
                            $args[6],
                            $args[7],
                            $args[8],
                            $args[9]
                        );
                        break;

                    default:
                        return call_user_func_array(self::$contains[$name], $args);
                        break;

                }
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_CONTAINER_ACCESS_DENIED|' . $accesser . ' -> ' . $name,
                    'setting',
                    true
                );
            }
        } elseif ($default && is_callable($default)) {
            return $default();
        }

        return false;
    }
}
