<?php


namespace WP\CriticalCSS\Admin\UI;


use ComposePress\Core\Abstracts\Component;

/**
 * Class Post
 *
 * @package WP\CriticalCSS\Admin\UI
 * @property \WP\CriticalCSS $plugin
 */
class Post extends Component {

	/**
	 *
	 */
	public function init() {
		if ( is_admin() ) {
			add_action( 'init', [
				$this,
				'init_action',
			] );
		}
	}

	public function init_action() {
		add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		add_action( 'save_post', [
			$this,
			'save_manual_css_meta_box',
		] );
		add_action( 'save_post', [
			$this,
			'save_override_css_meta_box',
		] );
	}

	public function add_meta_box() {
		foreach ( get_post_types() as $post_type ) {
			add_meta_box( "{$this->plugin->get_safe_slug()}_css_options", __( 'WP Critical CSS Options', $this->plugin->get_lang_domain() ), [
				$this,
				'render_meta_box',
			], $post_type );
		}

	}

	/**
	 *
	 */
	public function render_meta_box() {
		$slug      = $this->plugin->get_safe_slug();
		$object_id = $this->post->ID ?>
		<?php
		$css                     = $this->plugin->data_manager->get_item_data( [
			'type'      => 'post',
			'object_id' => $object_id,
		], 'manual_css' );
		$override                = $this->plugin->data_manager->get_item_data( [
			'type'      => 'post',
			'object_id' => $object_id,
		], 'override_css' );
		$post_type               = get_post_type_object( $this->post->post_type );
		$post_type_name          = get_post_type_object( $this->post->post_type )->label;
		$post_type_name_singular = get_post_type_object( $this->post->post_type )->labels->singular_name;
		?>
		<table class="form-table">
			<?php if ( apply_filters( 'wp_criticalcss_manual_post_css', true ) ) : ?>
				<tr>
					<td width="20%">
						<label for="<?php echo $slug ?>_manual_css"><?php _e( 'Enter your manual critical css here:', $this->plugin->get_lang_domain() ); ?></label>
					</td>
					<td>
					<textarea name="<?php echo $slug ?>_manual_css" id="<?php echo $slug ?>_manual_css"
							  class="widefat" rows="10"><?php echo $css ?></textarea>
					</td>
				</tr>
			<?php endif; ?>
			<?php
			if ( $post_type->hierarchical ) : ?>
				<tr>
					<td width="20%">
						<label for="<?php echo $slug ?>_override_css"><?php _e( 'Override Child Critical CSS', $this->plugin->get_lang_domain() ); ?></label>
					</td>
					<td>
						<input type="checkbox" name="<?php echo $slug ?>_override_css"
							   id="<?php echo $slug ?>_override_css"
							   value="1" <?php checked( $override ) ?>/>
						<span style="font-weight: bold;"><?php _e( sprintf( 'This will force any child %s of this %s to use this %s\'s critical css.', $post_type_name, $post_type_name_singular, $post_type_name_singular ), $this->plugin->get_lang_domain() ); ?></span>
					</td>
				</tr>
			<?php endif; ?>
		</table>
		<h2>
			<?php

			?>
		</h2>

		<?php
	}

	/**
	 * @param $post_id
	 */
	public function save_manual_css_meta_box( $post_id ) {
		$slug = $this->plugin->get_safe_slug();
		$field = "{$slug}_manual_css";
		if ( apply_filters( 'wp_criticalcss_manual_post_css', true ) && isset($_POST[$field]) ) {
			$css = sanitize_textarea_field( $_POST["{$slug}_manual_css"] );
			$this->plugin->data_manager->set_item_data( [
				'type'      => 'post',
				'object_id' => $post_id,
			], 'manual_css', $css );
		}
	}

	public function save_override_css_meta_box( $object_id ) {
		$slug      = $this->plugin->get_safe_slug();
		$post_type = get_post_type_object( get_post_type( $object_id ) );

		if ( $post_type->hierarchical ) {
			$value = ! empty( $_POST["{$slug}_override_css"] ) && 1 == $_POST["{$slug}_override_css"];
			$this->plugin->data_manager->set_item_data( [
				'type'      => 'post',
				'object_id' => $object_id,
			], 'override_css', $value );
		}
	}
}
