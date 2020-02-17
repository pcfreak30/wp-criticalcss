<?php


namespace WP_CriticalCSS\Models;


use WP_CriticalCSS\Abstracts\Queue;

class Web_Queue extends Queue {
	const TABLE_NAME = 'web_queue';
	const NAME  = 'web_check';
}
