<?php

/**
 * Template Core Prototype
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

namespace Facula\Base\Prototype\Core;

use Facula\Base\Error\Core\Template as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Template as Implement;
use Facula\Base\Tool\File\PathParser as PathParser;
use Facula\Base\Tool\File\ModuleScanner as ModuleScanner;
use Facula\Base\Tool\PHP\Ini as Ini;
use Facula\Framework;

/**
 * Prototype class for Template core for make core remaking more easy
 */
abstract class Template extends Factory implements Implement
{
    /**
     * Handle types for handleCacheExcludeArea method
     *
     * MAKE used for making initial template cache file, which can be use to
     * re-render for area caching.
     */
    const CACHE_EXCLUDE_HANDLE_TYPE_MAKE = 1;

    /**
     * Handle types for handleCacheExcludeArea method
     *
     * SECURE used to safelize initial template cache to make it proof from
     * cache injection ie. Saving executable '<?php hackwebsite(); ?>' to the cache.
     */
    const CACHE_EXCLUDE_HANDLE_TYPE_SECURE = 2;

    /** Declare maintainer information */
    public static $plate = array(
        'Author' => 'Rain Lee',
        'Reviser' => '',
        'Updated' => '2013',
        'Contact' => 'raincious@gmail.com',
        'Version' => __FACULAVERSION__,
    );

    /** Default configuration */
    protected static $setting = array(
        'TemplateFileSafeCode' => array(
            '<?php if (!defined(\'IN_FACULA\')) {exit(\'Access Denied\');} ',
            ' ?>',
        ),
        'NoIndexFile' =>
            '<html><head><title>Access Denied</title></head><body>Access Denied</body></html>',
        'NoCacheTag' => array(
            '<!-- NOCACHE -->',
            '<!-- /NOCACHE -->',
        ),
    );

    /** Instance configuration for caching */
    protected $configs = array();

    /** Pool for running data */
    protected $pool = array();

    /** Assigned template data */
    protected $assigned = array();

    /** A tag to not allow re-warming */
    protected $rewarmingMutex = false;

    /** File map that contains all template and language files */
    protected static $fileMap = array();

    /** Interfaces of sub operators */
    protected static $operatorsImpl = array(
        'Render' => 'Facula\Base\Implement\Core\Template\Render',
        'Compiler' => 'Facula\Base\Implement\Core\Template\Compiler'
    );

    /** Running mark */
    protected static $performMark = array();

    /**
     * Constructor
     *
     * @param array $cfg Array of core configuration
     * @param array $common Array of common configuration
     * @param \Facula\Framework $facula The framework itself
     *
     * @return void
     */
    public function __construct(&$cfg, $common, $facula)
    {
        $files = $fileNameSplit = array();

        // General settings
        $this->configs = array(
            'Cache' =>
                isset($cfg['CacheTemplate']) && $cfg['CacheTemplate'] ?
                    true : false,

            'Compress' =>
                isset($cfg['CompressOutput']) && PathParser::get($cfg['CompressOutput']) ?
                    true : false,

            'Renew' =>
                isset($cfg['ForceRenew']) && $cfg['ForceRenew'] ?
                    true : false,

            'CacheTTL' =>
                isset($cfg['CacheMaxLifeTime']) ?
                    (int)($cfg['CacheMaxLifeTime']) : null,

            'Charset' => strtoupper(
                isset($cfg['Charset'])
                ?
                $cfg['Charset']
                :
                isset($common['Charset']) ? $common['Charset'] : 'UTF-8'
            ),

            'AspTags' => Ini::getBool('asp_tags'),

            'CacheVer' => isset($common['BootVersion']) ? $common['BootVersion'] : 0,
        );

        // Use custom render
        if (isset($cfg['Render'][0])) {
            if (!class_exists($cfg['Render'])) {
                new Error(
                    'RENDER_CLASS_NOTFOUND',
                    array(
                        $cfg['Render']
                    ),
                    'ERROR'
                );

                return;
            }

            if (!class_implements(
                $cfg['Render'],
                static::$operatorsImpl['Render']
            )) {
                new Error(
                    'RENDER_INTERFACE_INVALID',
                    array(
                        $cfg['Render'],
                        static::$operatorsImpl['Render']
                    ),
                    'ERROR'
                );

                return;
            }

            $this->configs['Render'] = $cfg['Render'];
        }

        // Use custom compiler
        if (isset($cfg['Compiler'][0])) {
            if (!class_exists($cfg['Compiler'])) {
                new Error(
                    'COMPILER_CLASS_NOTFOUND',
                    array(
                        $cfg['Compiler']
                    ),
                    'ERROR'
                );

                return;
            }

            if (!class_implements(
                $cfg['Compiler'],
                static::$operatorsImpl['Compiler']
            )) {
                new Error(
                    'COMPILER_INTERFACE_INVALID',
                    array(
                        $cfg['Compiler'],
                        static::$operatorsImpl['Compiler']
                    ),
                    'ERROR'
                );

                return;
            }

            $this->configs['Compiler'] = $cfg['Compiler'];
        }

        // TemplatePool
        if (isset($cfg['TemplatePool'][0]) && is_dir($cfg['TemplatePool'])) {
            $this->configs['TplPool'] = PathParser::get(
                $cfg['TemplatePool']
            );
        } else {
            new Error(
                'PATH_TEMPLATEPOOL_NOTFOUND',
                array(),
                'ERROR'
            );

            return;
        }

        // CompiledTemplate
        if (isset($cfg['CompiledTemplate'][0]) && is_dir($cfg['CompiledTemplate'])) {
            $this->configs['Compiled'] = PathParser::get($cfg['CompiledTemplate']);
        } else {
            new Error(
                'PATH_COMPILEDTEMPLATE_NOTFOUND',
                array(),
                'ERROR'
            );

            return;
        }

        // Check if cache path has set
        if ($this->configs['Cache']) {
            if (isset($cfg['CachePath']) && is_dir($cfg['CachePath'])) {
                $this->configs['Cached'] = PathParser::get(
                    $cfg['CachePath']
                );
            } else {
                new Error(
                    'PATH_CACHEDTEMPLATE_NOTFOUND',
                    array(),
                    'ERROR'
                );

                return;
            }
        }

        $this->pool['SupportedLanguages'] = array();
        if ($this->loadFileMap() && isset(static::$fileMap['Lang'])) {
            $this->pool['SupportedLanguages'] = array_keys(
                static::$fileMap['Lang']
            );
        }

        $this->assigned['RootURL'] =
            $facula->request->getClientInfo('rootURL');

        $this->assigned['HostURIFormated'] =
            $facula->request->getClientInfo('hostURIFormated');

        $this->assigned['AbsRootURL'] =
            $facula->request->getClientInfo('absRootURL');

        $this->assigned['AbsRootFormated'] =
            $facula->request->getClientInfo('absRootFormated');
    }

    /**
     * Warm up initializer
     *
     * @return bool Return true when initialization complete, false otherwise
     */
    public function inited()
    {
        $error = '';
        $selectedLanguage = $clientLanguage = $errors = array();

        if ($this->rewarmingMutex) {
            new Error('REWARMING_NOTALLOWED');
        }

        $this->rewarmingMutex = true;

        // Determine what language can be used for this client
        if ($clientLanguage = Framework::core('request')->getClientInfo('languages')) {
            // Use $siteLanguage as the first param so we can follow clients priority
            $selectedLanguage = array_values(
                array_intersect(
                    $this->pool['SupportedLanguages'],
                    $clientLanguage
                )
            );
        }

        if (isset($selectedLanguage[0][0])) {
            $this->pool['Language'] = $selectedLanguage[0];
        } else {
            $this->pool['Language'] = 'default';
        }

        // Set Essential assign value
        $this->assigned['Time'] = FACULA_TIME;
        $this->assigned['_BOOTVER'] = $this->configs['CacheVer'];
        $this->assigned['_MESSAGE'] = array();

        Framework::summonHook(
            'template_inited',
            array(),
            $errors
        );

        return true;
    }

    /**
     * Assign a variable into template
     *
     * @param string $key Key name of the variable in template
     * @param string $val Value of the variable
     *
     * @return bool Return true when success, false otherwise
     */
    public function assign($key, $val)
    {
        if (!isset($this->assigned[$key])) {
            $this->assigned[$key] = $val;

            return true;
        } else {
            new Error(
                'ASSIGN_CONFLICT',
                array(
                    $key
                ),
                'ERROR'
            );
        }

        return false;
    }

    /**
     * Inject template content into specified inject area
     *
     * @param string $key Key name of the inject area
     * @param string $templatecontent Template content
     *
     * @return bool Always true
     */
    public function inject($key, $templateContent)
    {
        $this->pool['Injected'][$key][] = $templateContent;

        return true;
    }

    /**
     * Insert message into template
     *
     * Notice that the message will not showing if there is no message template made for display it
     *
     * @param array $message Message content in array
     *
     * @return bool Return the parsed message when inserted, false otherwise
     */
    public function insertMessage(array $message)
    {
        $argKeys = $argKeySearchs = $argVals = array();

        if (empty($message)) {
            return false;
        }

        if (isset($message['Code'])) {
            if (!empty($message['Args']) && is_array($message['Args'])) {
                $argKeys = array_keys($message['Args']);

                if ($argKeys == array_flip($argKeys)) {
                    $msgString = vsprintf(
                        $this->getLanguageString('MESSAGE_' . $message['Code']),
                        $message['Args']
                    );
                } else {
                    foreach ($argKeys as $argKeyKey => $argKeyVal) {
                        $argKeySearchs[$argKeyKey] = '%' . $argKeyVal . '%';
                    }

                    $argVals = array_values($message['Args']);

                    $msgString = str_replace(
                        $argKeySearchs,
                        $argVals,
                        $this->getLanguageString('MESSAGE_' . $message['Code'])
                    );
                }

            } else {
                $msgString = $this->getLanguageString(
                    'MESSAGE_' . $message['Code']
                );
            }
        } elseif (isset($message['Message'])) {
            $msgString = $message['Message'];
        } else {
            new Error(
                'MESSAGE_NOCONTENT',
                array(),
                'WARNING'
            );

            return false;
        }

        $messageContent = array(
            'Message' => $msgString ? $msgString : 'ERROR_UNKNOWN_ERROR',
            'Type' => isset($message['Type']) ? $message['Type'] : 'UNKNOWN'
        );

        if (isset($message['Name'])) {
            $this->assigned['_MESSAGE'][$message['Name']][] = $messageContent;
        } else {
            $this->assigned['_MESSAGE']['Default'][] = $messageContent;
        }

        return $messageContent;
    }

    /**
     * Render a page
     *
     * @param string $templateName Name of template
     * @param string $templateSet Name of the set in particular template series
     * @param integer $expire Time to expire relative to current second, set to null to disable caching
     * @param mixed $expiredCallback Callback that will be executed when template needs to re-render
     * @param string $cacheFactor Factor to make cache unique
     * @param array $specificalAssign Assign data which only available for this specific rendering progress
     *
     * @return bool Return the rendered content when success, false otherwise
     */
    public function render(
        $templateName,
        $templateSet = '',
        $expire = 0,
        $expiredCallback = null,
        $cacheFactor = '',
        array &$specificalAssign = array()
    ) {
        $templatePath = '';

        if (!is_null($expire)) {
            if (!$templatePath = $this->getCacheTemplate(
                $templateName,
                $templateSet,
                $expire,
                $expiredCallback,
                $cacheFactor,
                $specificalAssign
            )) {
                return false;
            }
        } else {
            // Or it just a normal call
            if (!$templatePath = $this->getCompiledTemplate(
                $templateName,
                $templateSet
            )) {
                return false;
            }
        }

        return $this->doRender(
            $templateName,
            $templatePath,
            $specificalAssign
        );
    }

    /**
     * Get a template file path from template file map
     *
     * @param string $template Name of template
     * @param string $setName Name of the set in particular template series
     *
     * @return mixed Return the file path when succeed, false otherwise
     */
    protected function getTemplateFromMap($template, $setName = 'default')
    {
        $this->loadFileMap();

        if (isset(static::$fileMap['Tpl'][$template][$setName])) {
            return static::$fileMap['Tpl'][$template][$setName];
        } elseif (isset(static::$fileMap['Tpl'][$template]['default'])) {
            return static::$fileMap['Tpl'][$template]['default'];
        }

        return false;
    }

    /**
     * Get a language file path from template file map
     *
     * @param string $languageCode Language code
     *
     * @return mixed Return an array contains all file paths when succeed, empty array otherwise
     */
    protected function getLanguageFormMap($languageCode)
    {
        $this->loadFileMap();

        if (isset(static::$fileMap['Lang'][$languageCode])) {
            return static::$fileMap['Lang'][$languageCode];
        }

        return array();
    }

    /**
     * Load the file map data from cache or generated file
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected function loadFileMap()
    {
        $fileMap = array();
        $fileMapFileName = $this->configs['Compiled']
            . DIRECTORY_SEPARATOR
            . 'cachedData.templateFileMap.php';

        if (!empty(static::$fileMap)) {
            return true;
        }

        if ($this->configs['Renew']
        || (!$fileMap = $this->loadDataCache(
            $fileMapFileName,
            $this->configs['CacheVer']
        ))) {
            // Get file and build maps
            foreach (array_merge(
                $this->getMapDataFromHook(),
                $this->getMapDataFromScanner()
            ) as $importedFile) {
                if (!isset(
                    $importedFile['Name'],
                    $importedFile['Prefix'],
                    $importedFile['Path']
                )) {
                    continue;
                }

                $fileNameSplit = explode('+', $importedFile['Name'], 2);

                switch ($importedFile['Prefix']) {
                    case 'language':
                        $fileMap['Lang'][$fileNameSplit[0]][] =
                            $importedFile['Path'];
                        break;

                    case 'template':
                        if (isset($fileNameSplit[1])) {
                            // If this is a ab testing file
                            if (!isset($fileMap['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]])) {
                                $fileMap['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]] =
                                    $importedFile['Path'];
                            } else {
                                new Error(
                                    'TEMPLATE_CONFLICT_SET',
                                    array(
                                        $importedFile['Path'],
                                        $fileNameSplit[1],
                                        $fileMap['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]]
                                    ),
                                    'ERROR'
                                );

                                return false;
                            }
                        } elseif (!isset($fileMap['Tpl'][$importedFile['Name']]['default'])) {
                            // If not, save current file to the default
                            $fileMap['Tpl'][$importedFile['Name']]['default'] = $importedFile['Path'];
                        } else {
                            new Error(
                                'TEMPLATE_CONFLICT',
                                array(
                                    $importedFile['Path'],
                                    $fileMap['Tpl'][$importedFile['Name']]['default']
                                ),
                                'ERROR'
                            );

                            return false;
                        }
                        break;
                }
            }

            $this->saveDataCache($fileMapFileName, $fileMap);
        }

        static::$fileMap = $fileMap;

        return true;
    }

    /**
     * Scan template and language files to generate file map
     *
     * @return array Return the array of File map
     */
    protected function getMapDataFromScanner()
    {
        $mappedFiles = array();

        // Scan for template files
        $scanner = new ModuleScanner(
            $this->configs['TplPool']
        );

        return $scanner->scan();
    }

    /**
     * Run template_import_files hook to get map data
     *
     * @return array Return the array of File map
     */
    protected function getMapDataFromHook()
    {
        $files = $errors = array();

        // Get files from hooks
        foreach (Framework::summonHook(
            'template_import_paths',
            array(),
            $errors
        ) as $hook => $importedPaths) {
            if (!is_array($importedPaths)) {
                continue;
            }

            foreach ($importedPaths as $path) {
                $pathScanner = new ModuleScanner(
                    $path
                );

                foreach ($pathScanner->scan() as $file) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    /**
     * Get template content from cache
     *
     * @param string $templateName Name of template
     * @param string $templateSet Name of the set in particular template series
     * @param integer $expire Time to expire relative to current second
     * @param mixed $expiredCallback Callback that will be executed when template needs to re-render
     * @param string $cacheFactor Factor to make cache unique
     * @param array &$specificalAssign Assigned partial template data
     *
     * @return bool Return the rendered content when success, false otherwise
     */
    protected function getCacheTemplate(
        $templateName,
        $templateSet = '',
        $expire = 0,
        $expiredCallback = null,
        $cacheFactor = '',
        array &$specificalAssign = array()
    ) {
        $templatePath = $templateContent = $cachedPagePath = $cachedPageRoot =
        $cachedPageFactor = $cachedPageFile = $cachedPageFactorDir = $cachedTmpPage =
        $renderCachedContent = '';

        $currentExpireTimestamp = 0;

        $splitedCompiledContent = $splitedRenderedContent = $errors = array();

        if ($expire) {
            $currentExpireTimestamp = FACULA_TIME - $expire;
        } elseif ($this->configs['CacheTTL']) {
            $currentExpireTimestamp = FACULA_TIME - $this->configs['CacheTTL'];
        }

        if (isset($this->configs['Cached'][0])) {
            $cachedPageFactor = !$cacheFactor ?
                'default' : str_replace(array('/', '\\', '|'), '#', $cacheFactor);

            $cachedPageFactorDir = !$cacheFactor ?
                'default' : $this->getCacheSubPath($cacheFactor);

            $cachedPageRoot = $this->configs['Cached']
                            . DIRECTORY_SEPARATOR
                            . $cachedPageFactorDir
                            . DIRECTORY_SEPARATOR;

            $cachedPageFile = 'cachedPage.'
                            . $templateName
                            . ($templateSet ? '+' . $templateSet : '')
                            . '.'
                            . $this->pool['Language']
                            . '.'
                            . $cachedPageFactor. '.php';

            $cachedPagePath = $cachedPageRoot . $cachedPageFile;

            if (!$this->configs['Renew']
            && $this->templateCacheReadable($cachedPagePath, $currentExpireTimestamp)) {
                return $cachedPagePath;
            } else {
                if ($expiredCallback
                && is_callable($expiredCallback)
                && !$expiredCallback()) {
                    return false;
                }

                if ($templatePath = $this->getCompiledTemplate($templateName, $templateSet)) {
                    if ($templateContent = $this->getCachedTemplate($templatePath)) {
                        $compiledContentForCached = $this->handleCacheExcludeArea(
                            $templateContent,
                            static::CACHE_EXCLUDE_HANDLE_TYPE_MAKE,
                            false
                        );

                        if ($this->buildCachingPath($cachedPageFactorDir)) {
                            $cachedTmpPage = $cachedPagePath . '.temp.php';

                            if ($this->saveCachedTemplate($cachedTmpPage, $compiledContentForCached)) {
                                if (!isset(static::$performMark['template_cache_prerendered'])) {
                                    Framework::summonHook(
                                        'template_cache_prerender_*',
                                        array(
                                            'Template' => $templateName
                                        ),
                                        $errors
                                    );

                                    static::$performMark['template_cache_prerendered'] = true;
                                }

                                Framework::summonHook(
                                    'template_cache_prerender_' . $templateName,
                                    array(
                                        'Template' => $templateName
                                    ),
                                    $errors
                                );

                                // Render nocached compiled content
                                if (($renderCachedContent = $this->doRender(
                                    $templateName,
                                    $cachedTmpPage,
                                    $specificalAssign
                                )) !== false) {
                                    // Clear old template
                                    $this->saveCachedTemplate($cachedTmpPage, null);

                                    if ($this->saveCachedTemplate(
                                        $cachedPagePath,
                                        $this->handleCacheExcludeArea(
                                            $renderCachedContent,
                                            static::CACHE_EXCLUDE_HANDLE_TYPE_SECURE,
                                            true
                                        )
                                    )) {
                                        return $cachedPagePath;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            new Error(
                'CACHE_DISABLE',
                array(),
                'ERROR'
            );

            return false;
        }

        return false;
    }

    /**
     * Handle the cache and exclude area of a template
     *
     * @param string $compliedTemplate Content of the template
     * @param string $task Type of task
     * @param bool $removeTag Remove the <!-- NOCACHE --> tag from output
     *
     * @return string The cache-already content
     */
    protected function handleCacheExcludeArea(&$compliedTemplate, $task, $removeTag)
    {
        $tempSelectedStr = '';
        $positions = $tempPop = $tempPair = $splitedArea = $safelizeTags = array();
        $lastPos = $nextPos = $tagStrLen = $splitedNextStarted = 0;

        foreach (static::$setting['NoCacheTag'] as $tagStr) {
            $nextPos = 0;
            $tagStrLen = strlen($tagStr);

            while (($lastPos = strpos($compliedTemplate, $tagStr, $nextPos)) !== false) {
                $positions[$lastPos] = array(
                    'Tag' => $tagStr,
                    'Pos' => $lastPos,
                    'End' => $lastPos + $tagStrLen,
                    'Len' => $tagStrLen,
                );

                $nextPos = $lastPos + 1;
            }
        }

        ksort($positions);

        while (!empty($positions)) {
            $tempPair = array();

            foreach (static::$setting['NoCacheTag'] as $tag) {
                $tempPop = array_shift($positions);

                if ($tempPop['Tag'] != $tag) {
                    new Error(
                        'CACHE_EXCLUDE_AREA_UNEXECPTED_SEQUENCE',
                        array(
                            $tag,
                            $tempPop['Tag'],
                            substr(
                                $compliedTemplate,
                                $tempPop['Pos'],
                                1024
                            ) . '...'
                        ),
                        'ERROR'
                    );

                    return false;
                }

                $tempPair[] = $tempPop;
            }

            if ($removeTag) {
                $tempSelectedStr = substr(
                    $compliedTemplate,
                    $tempPair[0]['End'],
                    $tempPair[1]['Pos'] - $tempPair[0]['End']
                );
            } else {
                $tempSelectedStr = substr(
                    $compliedTemplate,
                    $tempPair[0]['Pos'],
                    $tempPair[1]['End'] - $tempPair[0]['Pos']
                );
            }

            $splitedArea[] = substr(
                $compliedTemplate,
                $splitedNextStarted,
                $tempPair[0]['Pos'] - $splitedNextStarted
            );

            $splitedArea[] = $tempSelectedStr;

            $splitedNextStarted = $tempPair[1]['End'];
        }

        $splitedArea[] = substr(
            $compliedTemplate,
            $splitedNextStarted,
            strlen($compliedTemplate) - $splitedNextStarted
        );

        switch ($task) {
            case static::CACHE_EXCLUDE_HANDLE_TYPE_MAKE:
                foreach ($splitedArea as $aKey => $aVal) {
                    if ($aKey % 2) {
                        $splitedArea[$aKey] =
                            '<?php echo(stripslashes(\''
                            . addslashes($aVal)
                            . '\')); ?>';
                    }
                }
                break;

            case static::CACHE_EXCLUDE_HANDLE_TYPE_SECURE:
                // Notice that the programmer may forgot to filter and convert user's input,
                // which may a security breach that allowing user to submit PHP code without
                // safeize. Normally it's not a huge problem, but when those code been re-rendered
                // among compiled template (During page cacheize process), it will turn to
                // executable. Following code is used to resisting this problem by replacing PHP
                // mark tag to neutral HTML escape code.
                $safelizeTags = array(
                    array('<?', '?>'),
                    array('&lt;?', '?&gt;'),
                );

                // Notice we now use cached setting to check this. Reason is:
                // Even we do the check with real-time setting, we still can't change the fact
                // that the vulnerable page already been cached and still able to exec because
                // it's ALREADY there.
                // The only way to avoid this problem is perform a fully cache clean which will
                // cause cache rebuild on every each page we currently had.
                // But the best shot is actually DO NOT ENABLE 'asp_tags' function at all OR
                // ALWAYS ENABLE IT.
                // In my opinion, you should always use my paging engine or something like Smarty
                // disable 'asp_tags', then we should be safe.
                if ($this->configs['AspTags']) {
                    $safelizeTags[0][] = '<%';
                    $safelizeTags[0][] = '%>';

                    $safelizeTags[1][] = '&lt;%';
                    $safelizeTags[1][] = '%&gt;';
                }

                foreach ($splitedArea as $aKey => $aVal) {
                    if (!($aKey % 2)) {
                        $splitedArea[$aKey] = str_replace(
                            $safelizeTags[0],
                            $safelizeTags[1],
                            $aVal
                        );
                    }
                }
                break;

            default:
                new Error(
                    'UNDEFINED_CACHE_EXCLUDE_HANDLE_TYPE',
                    array(
                        $task
                    ),
                    'ERROR'
                );
                break;
        }

        return implode('', $splitedArea);
    }

    /**
     * Get cache path with cache name
     *
     * @param string $cacheName The name of cache
     *
     * @return string Return the path
     */
    protected function getCacheSubPath($cacheName)
    {
        $current = 0;
        $path = array();

        $current = abs(crc32($cacheName));

        while (true) {
            if ($current > 1024) {
                $path[] = $current = (int)($current / 1024);
            } else {
                break;
            }
        }

        return implode(
            DIRECTORY_SEPARATOR,
            array_reverse($path)
        );
    }

    /**
     * Get compiled template
     *
     * @param string $templateName Name of the template
     * @param string $templateSet Name of the template set
     *
     * @return mixed Return the path of the compiled template when success, or false when failed
     */
    protected function getCompiledTemplate($templateName, $templateSet)
    {
        $content = $error = $templatePath = '';
        $compiledTpl = $this->configs['Compiled']
            . DIRECTORY_SEPARATOR
            . 'compiledTemplate.'
            . $templateName
            . ($templateSet ? '+' . $templateSet : '')
            . '.'
            . $this->pool['Language'] . '.php';

        if (!$this->configs['Renew']
        && $this->templateCacheReadable($compiledTpl, $this->configs['CacheVer'])) {
            return $compiledTpl;
        } else {
            if (!$templatePath = $this->getTemplateFromMap($templateName, $templateSet)) {
                new Error(
                    'TEMPLATE_NOTFOUND',
                    array(
                        $templateName
                    ),
                    'ERROR'
                );

                return false;
            }

            if ($this->doCompile($templateName, $templatePath, $compiledTpl)) {
                return $compiledTpl;
            }
        }

        return false;
    }

    /**
     * Import a file path into template pool
     *
     * @param string $name Name of the template
     * @param string $path Path to the template
     * @param string $templateSet Set name of the template
     *
     * @return mixed Return true when success, false otherwise
     */
    public function importTemplateFile($name, $path, $templateSet = 'default')
    {
        $this->loadFileMap();

        if (!isset(static::$fileMap['Tpl'][$name][$templateSet])) {
            static::$fileMap['Tpl'][$name][$templateSet] = $path;

            return true;
        } else {
            new Error(
                'TEMPLATE_IMPORTING_EXISTED',
                array(
                    $name,
                    $templateSet,
                ),
                'WARNING'
            );
        }

        return false;
    }

    /**
     * Import a file path into language pool
     *
     * @param string $languageCode Language code like zh-CN etc
     * @param string $path Path to the language file
     *
     * @return mixed Return true when success, false otherwise
     */
    public function importLanguageFile($languageCode, $path)
    {
        $this->loadFileMap();

        if (isset(static::$fileMap['Lang'][$languageCode])) {
            static::$fileMap['Lang'][$languageCode][] = $path;

            return true;
        } else {
            new Error(
                'LANGUAGE_IMPORTING_UNSUPPORTED',
                array(
                    $languageCode,
                    implode(
                        ', ',
                        array_keys(static::$fileMap['Lang'])
                    )
                ),
                'WARNING'
            );
        }

        return false;
    }

    /**
     * Get language string with language key
     *
     * @param string $key Name of the language file
     *
     * @return mixed Return the language string when success, or false otherwise
     */
    public function getLanguageString($key)
    {
        if (!isset($this->pool['LanguageMap'])) {
            $this->loadLangMap();
        }

        if (isset($this->pool['LanguageMap'][$key])) {
            return $this->pool['LanguageMap'][$key];
        } else {
            return $key;
        }

        return false;
    }

    /**
     * Preform a template rendering
     *
     * @param string $templateName Name of the template that will be rendered for hook calling
     * @param string $compiledTpl Path to the compiled template file
     * @param array $specificalAssign Reference to assigned data for rendering content
     *
     * @return mixed Return the rendered content of the template when success, or false when failed
     */
    protected function doRender($templateName, $compiledTpl, array &$specificalAssign)
    {
        $renderedResult = '';
        $mergedAssign = $conflictedAssign = $errors = array();

        if (!isset(static::$performMark['template_render'])) {
            Framework::summonHook(
                'template_render_*',
                array(
                    'Template' => $templateName
                ),
                $errors
            );

            static::$performMark['template_render'] = true;
        }

        Framework::summonHook(
            'template_render_' . $templateName,
            array(
                'Template' => $templateName
            ),
            $errors
        );

        // This is a dangerous game. Don't change when you not fully understand.
        // I tell you why:
        // The $specificalAssign which be passed all the way to here is actually
        // a very important reference (not value) assigned by procedures outside
        // the Template function core (so it's not controllable) for rending current
        // template out.
        // As it's a reference, the data pointed by the $specificalAssign may change
        // during rendering progress. e.g: a rendering callback assigned a new value
        // etc.
        // Through this dangerous call chain you staring here, we be able to implement
        // the "Template specific data assign scope" and at same time keep the
        // compatibility of existing code.
        // Do NOT break this reference chain until the template fully rendered. Or
        // you will get incompleted render result.
        $conflictedAssign = array_intersect_key($specificalAssign, $this->assigned);

        if (!empty($conflictedAssign)) {
            new Error(
                'ASSIGN_MERGE_CONFLICT',
                array(
                    implode(', ', array_keys($conflictedAssign))
                ),
                'ERROR'
            );
        } else {
            $mergedAssign = $specificalAssign + $this->assigned;
        }

        if (isset($this->configs['Render'])) {
            $render = $this->configs['Render'];

            $renderedResult = $render::render(
                $compiledTpl,
                $mergedAssign
            )->result();
        } else {
            $renderedResult = static::renderPage(
                $compiledTpl,
                $mergedAssign
            );
        }

        if (!$renderedResult) {
            new Error(
                'RENDER_FAILED',
                array(
                    $compiledTpl,
                ),
                'ERROR'
            );

            return false;
        }

        return $renderedResult;
    }

    /**
     * The default render when no render has set
     *
     * @param string $compiledTpl Path to the compiled template file
     * @param array $assigned Assigned data
     *
     * @return mixed Return the rendered content when succeed, or false otherwise.
     */
    protected static function renderPage($compiledTpl, $assigned)
    {
        ob_start();

        extract($assigned);
        unset($assigned);

        Framework::core('debug')->criticalSection(true);

        require($compiledTpl);

        Framework::core('debug')->criticalSection(false);

        return ob_get_clean();
    }

    /**
     * Preform a template compile
     *
     * @param string $templateName Name of the template that will be rendered for hook calling
     * @param string $sourceTpl Path to source template
     * @param string $resultTpl Path to where the compiled file to be save
     *
     * @return mixed Return true when compiled, false otherwise
     */
    protected function doCompile($templateName, $sourceTpl, $resultTpl)
    {
        $sourceContent = $compiledContent = '';
        $errors = array();
        $poolCompexted = array();

        if (!isset(static::$performMark['template_compile'])) {
            Framework::summonHook(
                'template_compile_*',
                array(
                    'Template' => $templateName
                ),
                $errors
            );

            static::$performMark['template_compile'] = true;
        }

        Framework::summonHook(
            'template_compile_' . $templateName,
            array(
                'Template' => $templateName
            ),
            $errors
        );

        if (!isset($this->pool['LanguageMap'])) {
            $this->loadLangMap();
        }

        if (!is_readable($sourceTpl)) {
            new Error(
                'COMPILE_FILE_NOTFOUND',
                array(
                    $sourceTpl,
                ),
                'ERROR'
            );

            return false;
        }

        // Do not use trim here as compiler may need those info
        if (!$sourceContent = file_get_contents($sourceTpl)) {
            new Error(
                'COMPILE_FILE_EMPTY',
                array(
                    $sourceTpl,
                ),
                'ERROR'
            );

            return false;
        }

        $poolCompexted = $this->pool + array(
            'File' => static::$fileMap,
            'Charset' => $this->configs['Charset'],
        );

        if (isset($this->configs['Compiler'])) {
            $compiler = $this->configs['Compiler'];

            $compiledContent = $compiler::compile(
                $poolCompexted,
                $sourceContent
            )->result();
        } else {
            $compiledContent = static::compilePage(
                $poolCompexted,
                $sourceContent
            );
        }

        if ($compiledContent === false) {
            new Error(
                'COMPILER_FAILED',
                array(
                    $sourceTpl,
                ),
                'ERROR'
            );

            return false;
        }

        if ($this->configs['Compress']) {
            $compiledContent = str_replace(
                array('  ', "\r", "\n", "\t"),
                '',
                $compiledContent
            );
        }

        return $this->saveCachedTemplate($resultTpl, $compiledContent);
    }

    /**
     * The default compiler when no compiler has set
     *
     * @param array $poolCompexted The data needed to compile the template file
     * @param string $sourceContent Content of source template file
     *
     * @return string Return the rendered content
     */
    protected static function compilePage($poolCompexted, $sourceContent)
    {
        if (isset($poolCompexted['LanguageMap'])) {
            foreach ($poolCompexted['LanguageMap'] as $langKey => $langString) {
                $sourceContent = str_replace(
                    '<!-- Lang:' . $langKey . ' -->',
                    $langString,
                    $sourceContent
                );
            }
        }

        return $sourceContent;
    }

    /**
     * Load languages
     *
     * @return bool Return true when language has been loaded. false otherwise
     */
    protected function loadLangMap()
    {
        // Set LanguageMap first, because we need to tell application,
        // we already tried to get lang file so it will not waste time retrying it.
        $this->pool['LanguageMap'] = $typedLangData =
        $langMapTemp = $errors = array();

        $compiledLangFile = $this->configs['Compiled']
                            . DIRECTORY_SEPARATOR
                            . 'compiledLanguage.'
                            . $this->pool['Language'] . '.php';

        $langContent = $defaultLangContent = $langKey = $langVal = '';

        if ($this->configs['Renew']
        || (!$this->pool['LanguageMap'] = $this->loadDataCache(
            $compiledLangFile,
            $this->configs['CacheVer']
        ))) {
            Framework::summonHook(
                'template_load_language',
                array(),
                $errors
            );

            // load default lang file then client lang file

            // Must load default lang first
            foreach ($this->getLanguageFormMap('default') as $file) {
                if (is_readable($file)) {
                    $defaultLangContent .= file_get_contents($file) . "\n";
                } else {
                    new Error(
                        'LANGUAGE_DEFAULT_FILE_NOTFOUND',
                        array(
                            $file,
                        ),
                        'ERROR'
                    );

                    return false;
                }
            }

            // And then, the client lang
            if ($this->pool['Language'] != 'default') {
                foreach ($this->getLanguageFormMap($this->pool['Language']) as $file) {
                    if (is_readable($file)) {
                        $langContent .= file_get_contents($file) . "\n";
                    } else {
                        new Error(
                            'LANGUAGE_FILE_NOTFOUND',
                            array(
                                $file,
                            ),
                            'WARNING'
                        );
                    }
                }
            }

            foreach (array(
                'Default' => explode("\n", $defaultLangContent),
                'Client' =>  explode("\n", $langContent)
            ) as $langType => $langMapPre) {
                foreach ($langMapPre as $lang) {
                    $langMapTemp = explode('=', $lang, 2);

                    if (!isset($langMapTemp[1])) {
                        // If $langMapTemp[1] not set, may means this is just a comment.
                        continue;
                    }

                    $langKey = trim($langMapTemp[0]);
                    $langVal = trim($langMapTemp[1]);

                    if (isset($typedLangData[$langType][$langKey])) {
                        new Error(
                            'LANGUAGE_KEY_ALREADY_DECLARED',
                            array(
                                $langKey,
                                $typedLangData[$langType][$langKey]
                            ),
                            'WARNING'
                        );

                        break;
                    }

                    $this->pool['LanguageMap'][$langKey] = $langVal;

                    $typedLangData[$langType][$langKey] = $langVal;
                }
            }

            if ($this->saveDataCache(
                $compiledLangFile,
                $this->pool['LanguageMap']
            )) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build path for cache file based on $this->configs['Cached']
     *
     * @param string $path Directory path to the cache
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected function buildCachingPath($path)
    {
        $enteredPath = $this->configs['Cached'];

        foreach (explode(
            DIRECTORY_SEPARATOR,
            $path
        ) as $pathEl) {
            $enteredPath .= DIRECTORY_SEPARATOR . $pathEl;

            if (!file_exists($enteredPath)) {
                if (!mkdir($enteredPath, 0744, true)) {
                    return false;
                } else {
                    file_put_contents(
                        $enteredPath
                        . DIRECTORY_SEPARATOR
                        . 'index.htm',
                        static::$setting['NoIndexFile']
                    );
                }
            }
        }

        return true;
    }

    /**
     * Load cached template
     *
     * @param string $path Path to the cache
     *
     * @return mixed Return the template content when succeed, false otherwise
     */
    protected function getCachedTemplate($path)
    {
        if (!is_readable($path)) {
            return false;
        }

        return file_get_contents($path);
    }

    /**
     * Check if template file is readable
     *
     * @param string $path Path to the cache
     * @param integer $expire Time of expiration
     *
     * @return bool Return true when cache is readable, false otherwise
     */
    protected function templateCacheReadable($path, $expire = 0)
    {
        if (!is_readable($path)) {
            return false;
        }

        if ($expire > 0 && filemtime($path) <= $expire) {
            return false;
        }

        return true;
    }

    /**
     * Save template cache
     *
     * @param string $path Path to the cache
     * @param string $content Content of cache
     *
     * @return bool Return true when saved, false otherwise
     */
    protected function saveCachedTemplate($path, $content)
    {
        if (file_exists($path)) {
            unlink($path);
        }

        if (is_null($content)) {
            return true;
        }

        return file_put_contents(
            $path,
            static::$setting['TemplateFileSafeCode'][0]
            . static::$setting['TemplateFileSafeCode'][1]
            . $content
        );
    }

    /**
     * Load cache data
     *
     * @param string $path Path to the cache
     * @param integer $expire Expire timestamp, greater than cache save time means expired
     *
     * @return mixed Return cached data when succeed, false otherwise
     */
    protected function loadDataCache($path, $expire = 0)
    {
        $data = static::loadDataCacheFile($path);

        if (empty($data)) {
            return false;
        }

        if ($expire > 0 && $data['Meta']['SavedTime'] <= $expire) {
            return false;
        }

        return $data['Data'];
    }

    /**
     * Save Data Cache
     *
     * @param string $path Path to the cache
     * @param string $data Content to save
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected function saveDataCache($path, $data)
    {
        return static::saveDataCacheFile($path, $data);
    }

    /**
     * Static Wrapper: Load cached
     *
     * @param string $path Path to the cache
     *
     * @return array Return data content when succeed, or empty array when failed
     */
    protected static function loadDataCacheFile($path)
    {
        $cacheData = array();
        $cacheMeta = array();

        if (!is_readable($path)) {
            return array();
        }

        require($path);

        if (empty($cacheData) || empty($cacheMeta)) {
            return array();
        }

        return array(
            'Data' => $cacheData,
            'Meta' => $cacheMeta
        );
    }

    /**
     * Static Wrapper: Save data cache
     *
     * @param string $path Path to the cache
     * @param string $data Content to save
     *
     * @return bool Return true when succeed, false otherwise
     */
    protected static function saveDataCacheFile($path, $data)
    {
        if (file_exists($path)) {
            unlink($path);
        }

        return file_put_contents(
            $path,
            static::$setting['TemplateFileSafeCode'][0]
            . '$cacheData = ' . var_export($data, true) . '; '
            . '$cacheMeta = ' . var_export(array(
                'SavedTime' => time()
            ), true) . '; '
            . static::$setting['TemplateFileSafeCode'][1]
        );
    }
}
