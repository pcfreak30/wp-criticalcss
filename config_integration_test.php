<?php

/* @var $container \Dice\Dice */

$container->addRule(
	'WP\\CriticalCSS', [
		'shared' => true,
	]
);
$container->addRule(
	'WP\\CriticalCSS\\Integration\\Manager', [
		'instanceOf' => 'WP\\CriticalCSS\\Testing\\Integration\\CriticalCSS\\Integration\\Manager',
	]
);
$container->addRule(
	'WP\\CriticalCSS\\Request', [
		'instanceOf' => 'WP\\CriticalCSS\\Testing\\RequestMock',
	]
);
$container->addRule( '\WP\CriticalCSS\Web\Check\Background\Process', [
	'shared' => true,
] );
$container->addRule( '\WP\CriticalCSS\API\Background\Process', [
	'shared' => true,
] );
