<?php

require('..' . DIRECTORY_SEPARATOR . 'Bootstrap.php');

\Facula\Framework::run();

\Facula\Framework::core('response')->setContent('Hello Word!');
\Facula\Framework::core('response')->send();
