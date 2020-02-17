<?php


namespace WP_CriticalCSS\Managers;


use WP_CriticalCSS\Core\Manager;

/**
 * Class Model
 *
 * @package WP_CriticalCSS\Managers
 * @property \WP_CriticalCSS\Core\Plugin      $plugin
 * @property \WP_CriticalCSS\Models\Web_Queue $Web_Queue
 * @property \WP_CriticalCSS\Models\API_Queue $API_Queue
 * @property \WP_CriticalCSS\Models\Template_Log $Template_Log
 * @property \WP_CriticalCSS\Models\Processed_Log $Processed_Log
 */
class Model extends Manager {
	const MODULE_NAMESPACE = '\WP_CriticalCSS\Models';

	protected $modules = [
		'Web_Queue',
		'API_Queue',
		'Processed_Log',
		'Template_Log',
	];
}
