<?php

/**
 * Template Core Prototype
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

namespace Facula\Base\Prototype\Core;

/**
 * Prototype class for Template core for make core remaking more easy
 */
abstract class Template extends \Facula\Base\Prototype\Core implements \Facula\Base\Implement\Core\Template
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

    /**
     * Constructor
     *
     * @param array &$cfg Array of core configuration
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
            'Cache' => isset($cfg['CacheTemplate'])
                        && \Facula\Base\Tool\File\PathParser::get($cfg['CacheTemplate'])
                        ? true : false,

            'Compress' => isset($cfg['CompressOutput'])
                        && \Facula\Base\Tool\File\PathParser::get($cfg['CompressOutput'])
                        ? true : false,

            'Renew' => isset($cfg['ForceRenew'])
                        && $cfg['ForceRenew']
                        ? true : false,

            'Render' => isset($cfg['Render'][0])
                        && class_exists($cfg['Render'])
                        ? $cfg['Render'] : '\Facula\Base\Tool\Paging\Render',

            'Compiler' => isset($cfg['Compiler'][0])
                        && class_exists($cfg['Compiler'])
                        ? $cfg['Compiler'] : '\Facula\Base\Tool\Paging\Compiler',

            'CacheTTL' => isset($cfg['CacheMaxLifeTime'])
                        ? (int)($cfg['CacheMaxLifeTime']) : null,

            'CacheVer' => $common['BootVersion'],
        );

        // TemplatePool
        if (isset($cfg['TemplatePool'][0]) && is_dir($cfg['TemplatePool'])) {
            $this->configs['TplPool'] = \Facula\Base\Tool\File\PathParser::get(
                $cfg['TemplatePool']
            );
        } else {
            throw new \Exception('TemplatePool must be defined and existed.');
        }

        // CompiledTemplate
        if (isset($cfg['CompiledTemplate'][0]) && is_dir($cfg['CompiledTemplate'])) {
            $this->configs['Compiled'] = \Facula\Base\Tool\File\PathParser::get($cfg['CompiledTemplate']);
        } else {
            throw new \Exception(
                'CompiledTemplate must be defined and existed.'
            );
        }

        if ($this->configs['Cache']) {
            if (isset($cfg['CachePath']) && is_dir($cfg['CachePath'])) {
                $this->configs['Cached'] = $cfg['CachePath'];
            } else {
                throw new \Exception(
                    'CachePath must be defined and existed.'
                );
            }
        }

        // Scan for template files
        $scanner = new \Facula\Base\Tool\File\ModuleScanner(
            $this->configs['TplPool']
        );

        if ($files = $scanner->scan()) {
            foreach ($files as $file) {
                $fileNameSplit = explode('+', $file['Name'], 2);

                switch ($file['Prefix']) {
                    case 'language':
                        $this->pool['File']['Lang'][$fileNameSplit[0]][] =
                            $file['Path'];
                        break;

                    case 'template':
                        if (isset($fileNameSplit[1])) { // If this is a ab testing file
                            if (!isset($this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]])) {
                                $this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]] =
                                    $file['Path'];
                            } else {
                                throw new \Exception(
                                    'Template file '
                                    . $this->pool['File']['Tpl'][$fileNameSplit[0]][$fileNameSplit[1]]
                                    . ' conflicted with '
                                    . $file['Path']
                                    . '.'
                                );

                                return false;
                            }
                        } elseif (!isset($this->pool['File']['Tpl'][$file['Name']]['default'])) {
                            // If not, save current file to the default
                            $this->pool['File']['Tpl'][$file['Name']]['default'] = $file['Path'];
                        } else {
                            throw new \Exception(
                                'Template file '
                                . $this->pool['File']['Tpl'][$file['Name']]['default']
                                . ' conflicted with '
                                . $file['Path']
                                . '.'
                            );

                            return false;
                        }
                        break;
                }
            }

            if (!isset($this->pool['File']['Lang']['default'])) {
                throw new \Exception(
                    'Default file for language (language.default.txt) must be defined.'
                );
            }
        }
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
        if ($siteLanguage = \Facula\Framework::core('request')->getClientInfo('languages')) {
            if (isset($this->pool['File']['Lang'])) {
                $clientLanguage = array_keys(
                    $this->pool['File']['Lang']
                );

                // Use $siteLanguage as the first param so we can follow clients priority
                $selectedLanguage = array_values(
                    array_intersect(
                        $siteLanguage,
                        $clientLanguage
                    )
                );
            }
        }

        if (isset($selectedLanguage[0][0])) {
            $this->pool['Language'] = $selectedLanguage[0];
        } else {
            $this->pool['Language'] = 'default';
        }

        // Set Essential assign value
        $this->assigned['Time'] = FACULA_TIME;
        $this->assigned['RootURL'] = \Facula\Framework::core('request')->getClientInfo('rootURL');
        $this->assigned['AbsRootURL'] = \Facula\Framework::core('request')->getClientInfo('absRootURL');
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
    public function inject($key, $templatecontent)
    {
        $this->pool['Injected'][$key][] = $templatecontent;

        return true;
    }

    /**
     * Insert message into template
     *
     * Notice that the message will not showing if there is no message template made for display it
     *
     * @param string $message Message content in string or array
     *
     * @return bool Return the parsed message when inserted, false otherwise
     */
    public function insertMessage($message)
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
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_MESSAGE_NOCONTENT',
                    'template',
                    true
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
                if ($templatePath = $this->getCompiledTemplate($templateName, $templateSet)) {
                    if ($expiredCallback
                        && is_callable($expiredCallback)
                        && !$expiredCallback()) {
                        return false;
                    }

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
                                )) && unlink($cachedTmpPage)) {
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

                                    $renderCachedOutputContent = self::$setting['TemplateFileSafeCode'][0]
                                                                . self::$setting['TemplateFileSafeCode'][1]
                                                                . implode('', $splitedRenderedContent);

                                    unset($splitedRenderedContent, $splitedRenderedContentLen);

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
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_CACHE_DISABLED',
                'template',
                true
            );
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

        while (1) {
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
            if ($templateSet && isset($this->pool['File']['Tpl'][$templateName][$templateSet])) {
                $templatePath = $this->pool['File']['Tpl'][$templateName][$templateSet];
            } elseif (isset($this->pool['File']['Tpl'][$templateName]['default'])) {
                $templatePath = $this->pool['File']['Tpl'][$templateName]['default'];
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_NOTFOUND|' . $templateName,
                    'template',
                    true
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
        if (!isset($this->pool['File']['Tpl'][$name][$templateSet])) {
            $this->pool['File']['Tpl'][$name][$templateSet] = $path;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_IMPORT_TEMPLATE_EXISTED|' . $name,
                'template',
                true
            );
        }

        return false;
    }

    /**
     * Import a file path into language pool
     *
     * @param string $name Name of the language file
     * @param string $path Path to the language file
     * @param string $templateSet Set name of the language file
     *
     * @return mixed Return true when success, false otherwise
     */
    public function importLanguageFile($languageCode, $path)
    {
        if (isset($this->pool['File']['Lang'][$languageCode])) {
            $this->pool['File']['Lang'][$languageCode][] = $path;

            return true;
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_IMPORT_LANGUAGE_UNSPPORTED|' . $name,
                'template',
                true
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

        $render = new $this->configs['Render']($compiledTpl, $this->assigned);

        if (!($render instanceof \Facula\Base\Implement\Core\Template\Render)) {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_RENDER_INVALID_INTERFACE',
                'template',
                true
            );

            return false;
        }

        return $render->getResult();
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

        if ($sourceContent = trim(file_get_contents($sourceTpl))) {
            $compiler = new $this->configs['Compiler']($this->pool, $sourceContent);

            if (!($compiler instanceof \Facula\Base\Implement\Core\Template\Compiler)) {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_COMPILER_INVALID_INTERFACE',
                    'template',
                    true
                );

                return false;
            }

            if ($compiledContent = $compiler->compile()) {
                if ($this->configs['Compress']) {
                    $compiledContent = str_replace(
                        array('  ', "\r", "\n", "\t"),
                        '',
                        $compiledContent
                    );
                }

                return file_put_contents(
                    $resultTpl,
                    self::$setting['TemplateFileSafeCode'][0]
                    . self::$setting['TemplateFileSafeCode'][1]
                    . $compiledContent
                );
            } else {
                \Facula\Framework::core('debug')->exception(
                    'ERROR_TEMPLATE_COMPILE_FAILED|' . $sourceTpl,
                    'template',
                    true
                );
            }
        } else {
            \Facula\Framework::core('debug')->exception(
                'ERROR_TEMPLATE_COMPILE_OPEN_FAILED|' . $sourceTpl,
                'template',
                true
            );
        }

        return false;
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
            foreach ($this->pool['File']['Lang']['default'] as $file) {
                $langContent .= file_get_contents($file) . "\r\n";
            }

            // And then, the client lang
            if ($this->pool['Language'] != 'default') {
                foreach ($this->pool['File']['Lang'][$this->pool['Language']] as $file) {
                    $langContent .= file_get_contents($file) . "\r\n";
                }
            }

            $langMapPre = explode("\r\n", $langContent);

            foreach ($langMapPre as $lang) {
                $langMapTemp = explode('=', $lang, 2);

                if (isset($langMapTemp[1])) {
                    // If $langMapTemp[1] not set, may means this is just a comment.
                    $this->pool['LanguageMap'][trim($langMapTemp[0])] =
                                                                    trim($langMapTemp[1]);
                }
            }

            if (file_put_contents(
                $compiledLangFile,
                self::$setting['TemplateFileSafeCode'][0]
                . ' $langMap = '
                . var_export($this->pool['LanguageMap'], true)
                . '; '
                . self::$setting['TemplateFileSafeCode'][1]
            )) {
                return true;
            }
        }

        return false;
    }
}
