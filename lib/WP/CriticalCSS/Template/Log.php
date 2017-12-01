<?php


namespace WP\CriticalCSS\Template;


class Log extends \WP\CriticalCSS\Log {
	public function get( $template ) {
		$query = "SELECT `template`, `object_id`, `type`, `url` FROM {$this->get_table_name()} WHERE `template` = %s";
		$args  = [ $template ];

		if ( is_multisite() ) {
			$query  .= " AND `blog_id` = %d";
			$args[] = get_current_blog_id();
		}
		$query .= " GROUP BY `object_id`, `type`, `url`";

		return $this->wpdb->get_results( $this->wpdb->prepare( $query, $args ), ARRAY_A );
	}

	public function get_table_name() {
		$wpdb = $this->wpdb;
		if ( is_multisite() ) {
			return "{$wpdb->base_prefix}{$this->plugin->get_safe_slug()}_template_log";
		}

		return $table = "{$wpdb->prefix}{$this->plugin->get_safe_slug()}_template_log";

	}

	public function delete( $object_id, $type, $url ) {
		$query = "DELETE FROM {$this->get_table_name()} WHERE ((`object_id` = %d and `type` = %s) OR (`type` = %s and `url` = %s and `url` IS NOT NULL))";
		$args  = [ $object_id, $type, $type, $url ];
		if ( is_multisite() ) {
			$query  .= " AND `blog_id` = %d";
			$args[] = get_current_blog_id();
		}

		$this->wpdb->query( $this->wpdb->prepare( $query, $args ) );
	}
}