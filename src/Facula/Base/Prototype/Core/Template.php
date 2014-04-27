<?php

/**
 * Template Core Prototype
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

use Facula\Base\Error\Core\Template as Error;
use Facula\Base\Prototype\Core as Factory;
use Facula\Base\Implement\Core\Template as Implement;
use Facula\Base\Tool\File\PathParser as PathParser;

/**
 * Prototype class for Template core for make core remaking more easy
 */
abstract class Template extends Factory implements Implement
{
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
        )
    );

    /** Instance configuration for caching */
    protected $configs = array();

    /** Pool for running data */
    protected $pool = array();

    /** Assigned template data */
    protected $assigned = array();

    /** File map that contains all template and language files */
    protected static $fileMap = array();

    /** Interfaces of sub operators */
    protected static $operatorsImpl = array(
        'Render' => 'Facula\Base\Implement\Core\Template\Render',
        'Compiler' => 'Facula\Base\Implement\Core\Template\Compiler'
    );

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

            'CacheVer' => $common['BootVersion'],
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
                    'PATH_COMPILEDTEMPLATE_NOTFOUND',
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

        $this->assigned['AbsRootURL'] =
            $facula->request->getClientInfo('absRootURL');
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

        // Determine what language can be used for this client
        if ($clientLanguage = \Facula\Framework::core('request')->getClientInfo('languages')) {
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
        $this->assigned['Message'] = array();

        \Facula\Framework::summonHook(
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
        if (!empty($message)) {
            if (isset($message['Code'])) {
                if (isset($message['Args'])) {
                    $msgString = vsprintf(
                        $this->getLanguageString('MESSAGE_' . $message['Code']),
                        $message['Args']
                    );
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
                $this->assigned['Message'][$message['Name']][] = $messageContent;
            } else {
                $this->assigned['Message']['Default'][] = $messageContent;
            }

            return $messageContent;
        }

        return false;
    }

    /**
     * Render a page
     *
     * @param string $templateName Name of template
     * @param string $templateSet Name of the set in particular template series
     * @param integer $expire Time to expire relative to current second
     * @param mixed $expiredCallback Callback that will be executed when template needs to re-render
     * @param string $cacheFactor Factor to make cache unique
     *
     * @return bool Return the rendered content when success, false otherwise
     */
    public function render(
        $templateName,
        $templateSet = '',
        $expire = 0,
        $expiredCallback = null,
        $cacheFactor = ''
    ) {
        $templatePath = '';

        if (!is_null($expire)) {
            if (!$templatePath = $this->getCacheTemplate(
                $templateName,
                $templateSet,
                $expire,
                $expiredCallback,
                $cacheFactor
            )) {
                return false;
            }
        } else { // Or it just a normal call
            if (!$templatePath = $this->getCompiledTemplate(
                $templateName,
                $templateSet
            )) {
                return false;
            }
        }

        return $this->doRender(
            $templateName,
            $templatePath
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
            . 'cachedData.fileMap.php';

        if (!empty(static::$fileMap)) {
            return true;
        }

        if (!$this->configs['Renew']
            && is_readable($fileMapFileName)
            && filemtime($fileMapFileName) >= $this->configs['CacheVer']) {
            require($fileMapFileName);

            if (isset($fileMap)) {
                static::$fileMap = $fileMap;

                return true;
            }
        } else {
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
                        if (isset($fileNameSplit[1])) { // If this is a ab testing file
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

            static::$fileMap = $fileMap;

            // Must. APC may not know the file has renewed if we just call file_put_content
            if (file_exists($fileMapFileName)) {
                unlink($fileMapFileName);
            }

            return file_put_contents(
                $fileMapFileName,
                static::$setting['TemplateFileSafeCode'][0]
                . '$fileMap = '
                . var_export($fileMap, true)
                . '; '
                . static::$setting['TemplateFileSafeCode'][1]
            );
        }

        return false;
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
        $scanner = new \Facula\Base\Tool\File\ModuleScanner(
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
        foreach (\Facula\Framework::summonHook(
            'template_import_paths',
            array(),
            $errors
        ) as $hook => $importedPaths) {
            if (!is_array($importedPaths)) {
                continue;
            }

            foreach ($importedPaths as $path) {
                $pathScanner = new \Facula\Base\Tool\File\ModuleScanner(
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
     *
     * @return bool Return the rendered content when success, false otherwise
     */
    protected function getCacheTemplate(
        $templateName,
        $templateSet = '',
        $expire = 0,
        $expiredCallback = null,
        $cacheFactor = ''
    ) {
        $templatePath = $templateContent = $cachedPagePath = $cachedPageRoot =
        $cachedPageFactor = $cachedPageFile = $cachedPageFactorDir = $cachedTmpPage =
        $renderCachedContent = $renderCachedOutputContent = '';

        $splitedCompiledContentIndexLen = $splitedRenderedContentLen = $currentExpireTimestamp = 0;

        $splitedCompiledContent = $splitedRenderedContent = $errors = array();

        if (!$expire && !is_null($this->configs['CacheTTL'])) {
            $expire = $this->configs['CacheTTL'];
        }

        $currentExpireTimestamp = FACULA_TIME - $expire;

        if (isset($this->configs['Cached'][0])) {
            $cachedPageFactor =
                !$cacheFactor
                ?
                'default'
                :
                str_replace(
                    array('/', '\\', '|'),
                    '#',
                    $cacheFactor
                );

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
                && is_readable($cachedPagePath)
                && (!$expire || filemtime($cachedPagePath) > $currentExpireTimestamp)) {
                return $cachedPagePath;
            } else {
                if ($expiredCallback
                    && is_callable($expiredCallback)
                    && !$expiredCallback()) {
                    return false;
                }

                if ($templatePath = $this->getCompiledTemplate($templateName, $templateSet)) {
                    if ($templateContent = file_get_contents($templatePath)) {
                        // Spilt using no cache
                        $splitedCompiledContent = explode(
                            '<!-- NOCACHE -->',
                            $templateContent
                        );

                        $splitedCompiledContentIndexLen = count($splitedCompiledContent) - 1;

                        // Deal with area which need to be cached
                        foreach ($splitedCompiledContent as $key => $val) {
                            if ($key > 0
                                && $key < $splitedCompiledContentIndexLen
                                && $key%2) {
                                $splitedCompiledContent[$key] = '<?php echo(stripslashes(\''
                                                                . addslashes($val)
                                                                . '\')); ?>';
                            }
                        }

                        // Reassembling compiled content;
                        $compiledContentForCached = implode(
                            '<!-- NOCACHE -->',
                            $splitedCompiledContent
                        );

                        // Save compiled content to a temp file
                        unset(
                            $templateContent,
                            $splitedCompiledContent,
                            $splitedCompiledContentIndexLen
                        );

                        if (is_dir($cachedPageRoot)
                            || mkdir($cachedPageRoot, 0744, true)) {
                            $cachedTmpPage = $cachedPagePath . '.temp.php';

                            if (file_put_contents($cachedTmpPage, $compiledContentForCached)) {
                                \Facula\Framework::summonHook(
                                    'template_cache_prerender_*',
                                    array(),
                                    $errors
                                );

                                \Facula\Framework::summonHook(
                                    'template_cache_prerender_' . $templateName,
                                    array(),
                                    $errors
                                );

                                // Render nocached compiled content
                                if (($renderCachedContent = $this->doRender(
                                    $templateName,
                                    $cachedTmpPage
                                )) !== false) {
                                    \Facula\Framework::core('debug')->criticalSection(true);

                                    unlink($cachedTmpPage);

                                    \Facula\Framework::core('debug')->criticalSection(false);

                                    /*
                                        Beware the renderCachedContent as it may contains code that assigned
                                        by user. After render and cache, the php code may will
                                        turn to executable.

                                        Web ui designer should filter those code to avoid danger by using
                                        compiler's variable format, but they usually know nothing
                                        about how to keep user input safe.

                                        So: belowing code will help you to filter those code if the web ui
                                        designer not filter it by their own.
                                    */
                                    $splitedRenderedContent = explode(
                                        '<!-- NOCACHE -->',
                                        $renderCachedContent
                                    );

                                    $splitedRenderedContentLen = count($splitedRenderedContent) - 1;

                                    foreach ($splitedRenderedContent as $key => $val) {
                                        if (!($key > 0 && $key < $splitedRenderedContentLen
                                            && $key%2)) { // Inverse as above to tag and select cached area.
                                            $splitedRenderedContent[$key] = str_replace(
                                                array('<?', '?>'),
                                                array('&lt;?', '?&gt;'),
                                                $val
                                            );
                                            // Replace php code tag to unexecutable tag before save file.
                                        }
                                    }

                                    $renderCachedOutputContent = static::$setting['TemplateFileSafeCode'][0]
                                                                . static::$setting['TemplateFileSafeCode'][1]
                                                                . implode('', $splitedRenderedContent);

                                    unset($splitedRenderedContent, $splitedRenderedContentLen);

                                    // Remove the old file, let APC cache knows the update.
                                    \Facula\Framework::core('debug')->criticalSection(true);

                                    if (file_exists($cachedPagePath)) {
                                        unlink($cachedPagePath);
                                    }

                                    \Facula\Framework::core('debug')->criticalSection(false);

                                    if (file_put_contents($cachedPagePath, $renderCachedOutputContent)) {
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
            && is_readable($compiledTpl)
            && filemtime($compiledTpl) >= $this->configs['CacheVer']) {
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
     *
     * @return mixed Return the rendered content of the template when success, or false when failed
     */
    protected function doRender($templateName, $compiledTpl)
    {
        $renderedResult = '';
        $errors = array();

        \Facula\Framework::summonHook(
            'template_render_*',
            array(),
            $errors
        );

        \Facula\Framework::summonHook(
            'template_render_' . $templateName,
            array(),
            $errors
        );

        if (isset($this->configs['Render'])) {
            $render = $this->configs['Compiler'];

            $renderedResult = $render::render(
                $compiledTpl,
                $this->assigned
            )->result();
        } else {
            $renderedResult = static::renderPage(
                $compiledTpl,
                $this->assigned
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

        \Facula\Framework::core('debug')->criticalSection(true);

        require($compiledTpl);

        \Facula\Framework::core('debug')->criticalSection(false);

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

        \Facula\Framework::summonHook(
            'template_compile_*',
            array(),
            $errors
        );

        \Facula\Framework::summonHook(
            'template_compile_' . $templateName,
            array(),
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
            'File' => static::$fileMap
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

        \Facula\Framework::core('debug')->criticalSection(true);

        if (file_exists($resultTpl)) {
            unlink($resultTpl);
        }

        \Facula\Framework::core('debug')->criticalSection(false);

        return file_put_contents(
            $resultTpl,
            static::$setting['TemplateFileSafeCode'][0]
            . static::$setting['TemplateFileSafeCode'][1]
            . trim($compiledContent)
        );
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
        $this->pool['LanguageMap'] = $langMap = $langMapPre =
        $langMapTemp = $errors = array();

        $compiledLangFile = $this->configs['Compiled']
                            . DIRECTORY_SEPARATOR
                            . 'compiledLanguage.'
                            . $this->pool['Language'] . '.php';

        $langContent = '';

        if (!$this->configs['Renew']
            && is_readable($compiledLangFile)
            && filemtime($compiledLangFile) >= $this->configs['CacheVer']) { // Try load lang cache first
            require($compiledLangFile); // require for opcode optimizing

            if (!empty($langMap)) {
                $this->pool['LanguageMap'] = $langMap;
                return true;
            }
        } else { // load default lang file then client lang file
            \Facula\Framework::summonHook(
                'template_load_language',
                array(),
                $errors
            );

            // Must load default lang first
            foreach ($this->getLanguageFormMap('default') as $file) {
                if (is_readable($file)) {
                    $langContent .= file_get_contents($file) . "\n";
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

            $langMapPre = explode("\n", $langContent);

            foreach ($langMapPre as $lang) {
                $langMapTemp = explode('=', $lang, 2);

                if (isset($langMapTemp[1])) {
                    // If $langMapTemp[1] not set, may means this is just a comment.
                    $this->pool['LanguageMap'][trim($langMapTemp[0])] =
                        trim($langMapTemp[1]);
                }
            }

            \Facula\Framework::core('debug')->criticalSection(true);

            if (file_exists($compiledLangFile)) {
                unlink($compiledLangFile);
            }

            \Facula\Framework::core('debug')->criticalSection(false);

            if (file_put_contents(
                $compiledLangFile,
                static::$setting['TemplateFileSafeCode'][0]
                . ' $langMap = '
                . var_export($this->pool['LanguageMap'], true)
                . '; '
                . static::$setting['TemplateFileSafeCode'][1]
            )) {
                return true;
            }
        }

        return false;
    }
}
