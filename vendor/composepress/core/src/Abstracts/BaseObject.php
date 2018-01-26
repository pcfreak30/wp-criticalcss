<?php

namespace ComposePress\Core\Abstracts;

use ComposePress\Core\ComponentInterface;
use ComposePress\Core\Exception\InexistentProperty;
use ComposePress\Core\Exception\ReadOnly;

/**
 * Class BaseObjectAbstract
 *
 * @package ComposePress\Core\Abstracts
 * @property \wpdb       $wpdb
 * @property \WP_Post    $post
 * @property \WP_Rewrite $wp_rewrite
 * @property \WP         $wp
 * @property \WP_Query   $wp_query
 * @property \WP_Query   $wp_the_query
 * @property string      $pagenow
 * @property int         $page
 */
abstract class BaseObject {
	use \ComposePress\Core\Traits\BaseObject;
}
