<?php


namespace ComposePress\Core\Abstracts;

use ComposePress\Core\ComponentInterface;


/**
 * Class Component
 *
 * @package ComposePress\Core\Abstracts
 * @property Plugin    $plugin
 * @property Component $parent
 */
abstract class Component implements ComponentInterface {
	use \ComposePress\Core\Traits\Component;
}
