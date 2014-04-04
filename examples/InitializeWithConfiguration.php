<?php

require('..' . DIRECTORY_SEPARATOR . 'Bootstrap.php');

// date_default_timezone_set('UTC');
// mb_internal_encoding('utf-8');

$configuration = array(
    'AppName' => 'Facula Demo',
    'AppVersion' => '0.0.0',
    'Common' => array(
        'CookiePrefix' => '_facula_',
        // 'SiteRootURL' => '',
    ),
    'UsingCore' => array(
        // Enabled optional function core
        'cache' => '\Facula\Core\Cache',
    ),
    'Namespaces' => array(
        // Namespaces that will be automatically registered

        // '\RootName' => '/Path/To/The/Class/Folder',
    ),
    'Packages' => array(
        // Packages that will be automatically registered

        // '/Path/To/The/Package/Folder',
    ),

    // State cache file
    /*'StateCache' => METHOD_ROOT
                    . DIRECTORY_SEPARATOR .'privated'
                    . DIRECTORY_SEPARATOR . 'Temporary'
                    . DIRECTORY_SEPARATOR . 'Caches'
                    . DIRECTORY_SEPARATOR . 'State'
                    . DIRECTORY_SEPARATOR . 'state.php',*/
    'Paths' => array(
        // Paths that will be scan.

        // '/Paths/To/The/Folder',
    ),

    // Function core configuration
    'Core' => array(

    ),
);

\Facula\Framework::run();

\Facula\Framework::core('response')->setContent('Hello Word!');
\Facula\Framework::core('response')->send(); // See http response header for changes
