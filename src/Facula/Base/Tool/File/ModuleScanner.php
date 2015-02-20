<?php

/**
 * Module Scanner
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
 * @version    0.1.0 alpha
 * @see        https://github.com/raincious/facula FYI
 *
 */

namespace Facula\Base\Tool\File;

use Facula\Base\Exception\Tool\File\ModuleScanner as Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use FilesystemIterator;

/**
 * Use to scan Facula components
 */
class ModuleScanner
{
    /** The path that will be scan */
    protected $path = '';

    /** Max depth for seek */
    protected $depth = '';

    /**
     * Scanner Constructer
     *
     * @param string $path Directory that will be scan
     * @param integer $depth Depth to search
     *
     * @return void
     */
    public function __construct($path, $depth = -1)
    {
        if (is_dir($path)) {
            $this->path = realpath($path);
            $this->depth = (int)$depth;
        } else {
            throw new Exception\NotDirectory($path);
        }
    }

    /**
     * Scanner function
     *
     * @return array That contains all matched module
     */
    public function scan()
    {
        $modules = array();
        $moduleFilenames = $tempModuleFilenames = array();

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->path,
                FilesystemIterator::SKIP_DOTS
            )
        );

        $iterator->setMaxDepth($this->depth);

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->isReadable()) {
                $moduleFilenames = explode('.', $file->getFilename());

                switch (count($moduleFilenames)) {
                    case 1:
                        break;

                    case 2:
                        $modules[] = array(
                            'Prefix' => '',
                            'Name' => $moduleFilenames[0],
                            'Ext' => $moduleFilenames[1],
                            'Path' => $file->getPathname(),
                            'Dir' => $file->getPath(),
                        );
                        break;

                    case 3:
                        $modules[] = array(
                            'Prefix' => $moduleFilenames[0],
                            'Name' => $moduleFilenames[1],
                            'Ext' => $moduleFilenames[2],
                            'Path' => $file->getPathname(),
                            'Dir' => $file->getPath(),
                        );
                        break;

                    default:
                        $tempModuleFilenames = array(
                            'Prefix' => array_shift($moduleFilenames),
                            'Ext' => array_pop($moduleFilenames),
                        );

                        $modules[] = array(
                            'Prefix' => $tempModuleFilenames['Prefix'],
                            'Name' => implode('.', $moduleFilenames),
                            'Ext' => $tempModuleFilenames['Ext'],
                            'Path' => $file->getPathname(),
                            'Dir' => $file->getPath(),
                        );
                        break;
                }
            }
        }

        if (empty($modules)) {
            throw new Exception\EmptyResult($this->path);
        }

        return $modules;
    }
}
