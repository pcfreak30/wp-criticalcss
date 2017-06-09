<?php


namespace WP\CriticalCSS\Testing;


use WP\CriticalCSS\Request;

class RequestMock extends Request {
	/**
	 * @param string $template
	 */
	public function set_template( $template ) {
		$this->template = $template;
	}

	/**
	 * @param string $nocache
	 */
	public function set_nocache( $nocache ) {
		$this->nocache = $nocache;
	}

}