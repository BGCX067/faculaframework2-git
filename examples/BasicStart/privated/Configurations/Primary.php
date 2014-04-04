<?php

/**
 * Framework Demo: Configuration file
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
 * @version    0.1 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

/**
 * Set default time zone
 */
date_default_timezone_set('UTC');

/**
 * Set default MB encode
 */
mb_internal_encoding('utf-8');

/**
 * Framework configuration array
 */
$cfg = array(
    'AppName' => 'Project Demo',
    'AppVersion' => '0.0.2',
    'Common' => array(
        'CookiePrefix' => '_demo_',
        // 'SiteRootURL' => '',
    ),
    'UsingCore' => array(
        // 'pdo' => '\Facula\Core\PDO',
        'cache' => '\Facula\Core\Cache',
        'template' => '\Facula\Core\Template',
    ),
    'Namespaces' => array(
        '\MyProject\Controller' => \Facula\Framework::PATH . '/privated/Components/Controllers',
        '\MyProject\Model' => \Facula\Framework::PATH . '/privated/Components/Models',
    ),
    'Packages' => array(
    ),
    // State cache file need to be remove once configuration
    // or project has new change
    'StateCache' => \Facula\Framework::PATH
                    . DIRECTORY_SEPARATOR .'privated'
                    . DIRECTORY_SEPARATOR . 'Caches'
                    . DIRECTORY_SEPARATOR . 'state.php',
    'Paths' => array(
        \Facula\Framework::PATH . '/privated/Components/Includes',
    ),
    'Core' => array(
        'debug' => array(
            'Debug' => true,
            'LogRoot' => \Facula\Framework::PATH . '/privated/Caches',
            // 'LogServerInterface' => 'http://reports.engine.3ax.org/interface',
            // 'LogServerKey' => '3f8871562ed0f1e8d1a69cbf4d20c664',
        ),
        'object' => array(
            'ObjectCacheRoot' => \Facula\Framework::PATH . '/privated/Caches',
        ),
        'request' => array(
            'MaxRequestBlocks' => 64,
            'MaxDataSize' => 5120,
            'MaxHeaderSize' => 4096,
            'TrustedProxies' => array(
                '192.168.1.1-192.168.1.254',
                '127.0.0.1'
            ),
        ),
        'response' => array(
            'UseGZIP' => true,
            'PostProfileSignal' => true,
        ),
        /*
        'pdo' => array(
            'DefaultTimeout' => 3,
            'SelectMethod' => 'Normal',
            'PriorMethod' => 'Redundance',
            'DatabaseGroup' => array(
                array(
                    'Driver' => 'pgsql',
                    'Connection' => 'host=localhost;port=5432;dbname=facula',
                    'Prefix' => 'facula_',
                    'Username' => 'facula',
                    'Password' => 'facula',
                    'Persistent' => false,
                    'Timeout' => 1
                ),
                array(
                    'Driver' => 'mysql',
                    'Connection' => 'host=localhost;dbname=facula',
                    'Prefix' => 'facula_',
                    'Username' => 'facula',
                    'Password' => 'facula',
                    'Persistent' => false,
                    'Options' => array(
                        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
                    ),
                    'Timeout' => 1
                ),

            ),
        ),
        */
        'template' => array(
            'TemplatePool' => \Facula\Framework::PATH . '/privated/Templates',
            'CompiledTemplate' => \Facula\Framework::PATH . '/privated/Caches',
            'CachePath' => \Facula\Framework::PATH . '/privated/Caches',
            'CacheTemplate' => true,
            'CacheMaxLifeTime' => 0,
            'CompressOutput' => true,
            'ForceRenew' => false
        ),
        'cache' => array(
            'CacheRoot' => \Facula\Framework::PATH . '/privated/Caches',
        ),
    ),
);
