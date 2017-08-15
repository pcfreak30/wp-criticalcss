<?php

/* @var $container \Dice\Dice */

$container->addRule( '\WP\CriticalCSS', [
	'shared' => true,
] );
$container->addRule( '\WP\CriticalCSS\Web\Check\Background\Process', [
	'shared' => true,
] );
$container->addRule( '\WP\CriticalCSS\API\Background\Process', [
	'shared' => true,
] );
