Facula PHP Framwork
==================================

Facula is a A highly extensible experimental non-full-stack framework written in PHP.

It aim to provide a stable interface for the ground floor to make your application can be fit to various environments without changing existing code.


Status
----------------------------------

* Under: Development
* Project: Experimental (Production not recommended)
* [![Build Status](https://travis-ci.org/raincious/facula.svg)](https://travis-ci.org/raincious/facula)


How to get it
----------------------------------

You can download or clone it from Github, Bitbucket, or use Composer


### Install with composer (Recommended)

Install the Composer, and run:

	cd [To/Your/Project/Path]
	composer require raincious/facula "*"


### Clone with Git

Install Git and run:

	cd [To/Your/Project/Path]
	# If you use Github
	git clone https://github.com/raincious/facula

	# Or you use Bitbucket
	git clone https://bitbucket.org/raincious/facula-framework-2

After that, `require` The framework by adding

	require('facula/Bootstrap.php');

Into your index.php or other common procedure file.


### Download it manually

See The [release](https://github.com/raincious/facula/releases) section.


Initialize it
----------------------------------

To power on the framework, you need to initialize by the run method.

	<?php

	...

	use Facula\Framework;

	Framework::run();

And then, you will be able to use all the function that provided by the framework:

	Framework::core('response')->setContent('Hello World');
	Framework::core('response')->send();

For more information of framework usage, please see the examples.


Porting to Facula
----------------------------------

Facula is Not a MVC framework that strictly limit the usage and force you to use MVC pattern.

Which means you can still work with your procedural project and add the framework in.


### Notes on porting from a procedural project

For managing the ground floor properly, the framework need a environment that can't be interfere. That's means, you can't use function like `echo` to output or `ob_*` to create buffer. Function that effect globally like `mb_internal_encoding` is not recommended to use in data handle process.

Keep in mind, in the presuppose, facula will manage the whole workflow from input to output.

For example, when you want to get data from request, you need to use function core `request`:

	$pageID = Facula\Framework::core('request')->getGet('page_id');
	$content = Facula\Framework::core('request')->getPost('Content');
	$cookie = Facula\Framework::core('request')->getCookie('Cookie');

For response, use the `response` core:

	Facula\Framework::core('response')->setCookie('Cookie', 'I remember you');
	Facula\Framework::core('response')->setHeader('Your: header');
	Facula\Framework::core('response')->setHeader('Another: header');
	Facula\Framework::core('response')->setContent('Hi');
	Facula\Framework::core('response')->send('Hi');

However, if you want to keep it your own way and not use the `request` and `response` function core, you can disable the `request` and `response` function core by simply extends the `Facula\Framework` class, and modify the `protected static $requiredCores` array.


### Notes on porting from a MVC project

If you already have a MVC project, you can still use your old libraries and even frameworks. And only let Facula to help you to handle tasks you want.

For example, you can disable all function cores and just let Facula to manage your project file as a autoloader.

Facula core basically is a component loader, which load up component according your configuartion and get them ready to use.

That means you can flexibly select or decide which you want to load to enable some function or just disable those functions by simply not to loading it.


Start a new project with Facula
----------------------------------

Please see the example project in `examples\BasicStart` folder. It showing how to start a fresh MVC project based on Facula Framework.


Links
----------------------------------

Facula also on
[Github](https://github.com/raincious/facula/), [Google Code](https://code.google.com/p/faculaframework2/) and [Bitbucket](https://bitbucket.org/raincious/facula-framework-2/).

* Issues: [https://github.com/raincious/facula/issues](https://github.com/raincious/facula/issues)
* Wiki: [https://github.com/raincious/facula/wiki](https://github.com/raincious/facula/wiki)


License
----------------------------------

See LICENSE
