<?php

if (file_exists('Lock')) {
    exit('Example disabled. Remove Lock file to enable.');
}

require('..' . DIRECTORY_SEPARATOR . 'Bootstrap.php');

\Facula\Framework::run();

\Facula\Framework::core('response')->setContent('Hello Word!');
\Facula\Framework::core('response')->send();
