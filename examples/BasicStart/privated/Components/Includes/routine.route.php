<?php

/**
 * Framework Demo: Route setup routine
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
 * Register the project execution function
 */
\Facula\Framework::registerHook('ready', 'exec', function () {
    if (!$routeMap = \Facula\Framework::core('cache')->load('Routes')) {
        \Facula\Framework::summonHook('route_init');

        \Facula\Framework::core('cache')->save(
            'Route-map',
            \Facula\Unit\Route::exportMap(),
            0
        );
    } else {
        \Facula\Unit\Route::importMap($routeMap);
    }

    \Facula\Unit\Route::setDefaultHandler(function () {
        \Facula\Framework::core('object')->run(
            'MyProject\Controller\Home\Index',
            array(),
            true
        );
    });

    \Facula\Unit\Route::setErrorHandler(function ($type) {
        $pageContent = '';

        switch ($type) {
            case 'PATH_NOT_FOUND':

            case 'PATH_NO_OPERATOR':

            case 'PATH_NO_OPERATOR_SPECIFIED':

            default:
                if ($pageContent = \Facula\Framework::core(
                    'template'
                )->render('message.404')) {
                    \Facula\Framework::core('response')->setHeader(
                        'HTTP/1.1 404 Not Found'
                    );

                    \Facula\Framework::core('response')->setContent(
                        $pageContent
                    );

                    \Facula\Framework::core('response')->send();
                }
                break;
        }

        return true;
    });

    if (file_exists(\Facula\Framework::PATH . DIRECTORY_SEPARATOR . '.htaccess')) {
        $path = array(
            'Prefix' => '/',
            'Path' => $_SERVER['QUERY_STRING']
        );
    } else {
        $path = array(
            'Prefix' => '/?/',
            'Path' => $_SERVER['QUERY_STRING']
        );
    }

    if (\Facula\Unit\Route::setPath($path['Path'])) {
        \Facula\Framework::core('template')->assign(
            'RouteRoot',
            \Facula\Framework::core('request')->getClientInfo('rootURL')
            . $path['Prefix']
        );

        \Facula\Framework::core('template')->assign(
            'AbsRouteRoot',
            \Facula\Framework::core('request')->getClientInfo('absRootURL')
            . $path['Prefix']
        );

        \Facula\Framework::core('template')->assign(
            'RoutePath',
            \Facula\Unit\Route::getPath()
        );
    }

    unset($path);

    \Facula\Unit\Route::run();

    return true;
});
