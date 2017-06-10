<?php

/* @var $container \Dice\Dice */

$container->addRule( 'WP\\CriticalCSS', [
	'shared'     => true,
	'instanceOf' => 'WP\\CriticalCSS\\Testing\\CriticalCSSMock',
] );
$container->addRule( 'WP\\CriticalCSS\\Request', [
	'instanceOf' => 'WP\\CriticalCSS\\Testing\\RequestMock',
] );
$container->addRule( '\\WP\\CriticalCSS\Admin\UI', [
	'instanceOf' => '\\WP\\CriticalCSS\\Testing\Admin\UIMock',
] );
