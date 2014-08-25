<?php

/**
 * Cache Core Prototype
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

namespace Facula\Base\Prototype\Core;

use Facula\Base\Error\Core\Cache as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Cache as Implement;
use Facula\Base\Tool\File\PathParser as PathParser;
use Facula\Framework;

/**
 * Prototype class for Cache core for make core remaking more easy
 */
abstract class Cache extends Factory implements Implement
{
    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Default settings */
    protected static $setting = array(
        'CacheFileSafeCode' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            ' ?>',
        )
    );

    /** Instance setting for caching */
    protected $configs = array();

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common)
    {
        if (isset($cfg['CacheRoot'][0]) && is_dir($cfg['CacheRoot'])) {
            $this->configs['Root'] = PathParser::get($cfg['CacheRoot']);
        } else {
            new Error(
                'CACHEPATH_NOTFOUND',
                array(),
                'ERROR'
            );

            return;
        }

        $this->configs['BootVer'] = $common['BootVersion'];
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        return true;
    }

    /**
     * Load data from cache
     *
     * @param string $cacheName Cache name
     *
     * @return bool Return true success, false otherwise
     */
    public function load($cacheName)
    {
        $path = $file = '';
        $cache = array();
        $expireMethod = null;

        if ($path = $this->getCacheFileByName($cacheName)) {
            $file = $this->configs['Root'] . DIRECTORY_SEPARATOR . $path['File'];

            if (is_readable($file)) {
                require($file);

                if (($cache[0] && $cache[0] < FACULA_TIME)
                || $this->configs['BootVer'] > $cache[1]) {
                    return false;
                }

                return $cache[2]; // Yeah, actually null cannot be isset.
            }
        }

        return false;
    }

    /**
     * Save data to cache
     *
     * @param string $cacheName Cache name
     * @param string $data Data will be saved in cache
     * @param string $expire How long (in second) the cache will expired after saving
     *
     * @return bool Return true success, false otherwise
     */
    public function save($cacheName, $data, $expire = 0)
    {
        $cacheData = array();
        $expireMethod = null;
        $expiredTime = 0;

        if ($path = $this->getCacheFileByName($cacheName)) {
            $file = $this->configs['Root'] . DIRECTORY_SEPARATOR . $path['File'];

            if ($expire) {
                if ($expire > FACULA_TIME) {
                    $expireMethod = 2;
                }

                switch ($expireMethod) {
                    case 2:
                        // expireMethod = scheduled.
                        // The cache will expired when reach specified date
                        $expiredTime = $expire;
                        break;

                    default:
                        // expireMethod = remaining.
                        // The cache will expired when remaining time come to zero.
                        $expiredTime = FACULA_TIME + $expire;
                        break;
                }
            }

            if ($this->makeCacheDir($path['Path'])) {
                $cacheData = array(
                    0 => (int)$expiredTime, // Expired
                    1 => (int)FACULA_TIME, // Cached Time
                    2 => $data ? $data : null, // Data
                );

                Framework::core('debug')->criticalSection(true);

                if (file_exists($file)) {
                    unlink($file);
                }

                Framework::core('debug')->criticalSection(false);

                return file_put_contents(
                    $file,
                    static::$setting['CacheFileSafeCode'][0]
                    . ' $cache = '
                    . var_export($cacheData, true)
                    . '; '
                    . static::$setting['CacheFileSafeCode'][1]
                );
            }
        }

        return false;
    }

    /**
     * Get cache path from cache name
     *
     * @param string $cacheName Cache name
     *
     * @return string Return cache path
     */
    protected function getCacheFileByName($cacheName)
    {
        $pathArray = array();

        $result = array(
            'Path' => '',
            'File' => '',
        );

        $crc = abs(crc32($cacheName));

        while ($crc > 0) {
            $pathArray[] = ($crc = (int)($crc / 10240)) . '';
        }

        if ($pathArray[0][0]) {
            $result['Path'] = implode(DIRECTORY_SEPARATOR, array_reverse($pathArray));
            $result['File'] = $result['Path']
                            . DIRECTORY_SEPARATOR
                            . 'CacheFile.'
                            . $cacheName
                            . '.php';

            return $result;
        } else {
            $result['File'] = $result['Path']
                            . DIRECTORY_SEPARATOR
                            . 'CacheFile.'
                            . $cacheName
                            . '.php';

            return $result;
        }
    }

    /**
     * Make directory for cache
     *
     * @param string $pathName Path name
     *
     * @return string Return cache path
     */
    protected function makeCacheDir($pathName)
    {
        $fullPath = $this->configs['Root'] . DIRECTORY_SEPARATOR . $pathName;
        $currentPath = $this->configs['Root'] . DIRECTORY_SEPARATOR;

        if (!file_exists($fullPath)) {
            foreach (explode(DIRECTORY_SEPARATOR, $pathName) as $path) {
                if (!file_exists($currentPath . $path)
                    && mkdir($currentPath . $path, 0744)) {
                    file_put_contents($currentPath . 'index.htm', 'Access Denied');
                }

                $currentPath .= $path . DIRECTORY_SEPARATOR;
            }

            return $currentPath;
        } else {
            return $fullPath;
        }

        return false;
    }
}
