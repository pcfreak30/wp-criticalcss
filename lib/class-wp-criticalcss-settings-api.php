<?php

class WP_CriticalCSS_Settings_API extends WeDevs_Settings_API {
	/**
	 * Sanitize callback for Settings API
	 *
	 * @param $options
	 *
	 * @return mixed
	 */
	function sanitize_options( $options ) {

		if ( ! $options ) {
			return $options;
		}

		foreach ( $options as $option_slug => $option_value ) {
			$sanitize_callback = $this->get_sanitize_callback( $option_slug );

			// If callback is set, call it
			if ( $sanitize_callback ) {
				$options[ $option_slug ] = call_user_func( $sanitize_callback, $options );
				continue;
			}
		}

		return $options;
	}

	function callback_html( $args ) {
		echo $args['desc'];
	}

}