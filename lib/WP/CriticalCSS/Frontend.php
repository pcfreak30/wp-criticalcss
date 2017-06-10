<?php


namespace WP\CriticalCSS;


class Frontend extends ComponentAbstract {
	public function init() {
		parent::init();
		if ( ! is_admin() ) {
			add_action(
				'wp_print_styles', [
				$this,
				'print_styles',
			], 7 );
			add_action(
				'wp', [
					$this,
					'wp_action',
				]
			);
			add_action(
				'wp_head', [
					$this,
					'wp_head',
				]
			);
		}
	}

	/**
	 *
	 */
	public function wp_head() {
		if ( get_query_var( 'nocache' ) ) :
			?>
			<meta name="robots" content="noindex, nofollow"/>
			<?php
		endif;
	}

	/**
	 *
	 */
	public function wp_action() {
		set_query_var( 'nocache', $this->app->get_request()->is_no_cache() );
		$this->app->get_integration_manager()->enable_integrations();
	}

	/**
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 */
	public function print_styles() {
		if ( ! get_query_var( 'nocache' ) && ! is_404() ) {
			$cache = $this->app->get_data_manager()->get_cache();
			$cache = apply_filters( 'wp_criticalcss_print_styles_cache', $cache );

			do_action( 'wp_criticalcss_before_print_styles', $cache );

			if ( ! empty( $cache ) ) {
				?>
				<style type="text/css" id="criticalcss" data-no-minify="1"><?php echo $cache ?></style>
				<?php
			}
			$type  = $this->app->get_request()->get_current_page_type();
			$hash  = $this->app->get_data_manager()->get_item_hash( $type );
			$check = $this->app->get_cache_manager()->get_cache_fragment( [ $hash ] );
			if ( 'on' == $this->settings['template_cache'] && ! empty( $type['template'] ) ) {
				if ( empty( $cache ) && ! $this->app->get_api_queue()->get_item_exists( $type ) ) {
					$this->app->get_api_queue()->push_to_queue( $type )->save();
				}
			} else {
				if ( empty( $check ) && ! $this->app->get_web_check_queue()->get_item_exists( $type ) ) {
					$this->app->get_web_check_queue()->push_to_queue( $type )->save();
					$this->app->get_cache_manager()->update_cache_fragment( [ $hash ], true );
				}
			}

			do_action( 'wp_criticalcss_after_print_styles' );
		}
	}
}