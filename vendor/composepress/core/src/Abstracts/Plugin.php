<?php

namespace ComposePress\Core\Abstracts;

use Dice\Dice;
use ComposePress\Core\Exception\ContainerInvalid;
use ComposePress\Core\Exception\ContainerNotExists;

/**
 * Class Plugin
 *
 * @package ComposePress\Core\Abstracts
 *
 * @property \Dice\Dice            $container
 * @property string                $slug
 * @property string                $safe_slug
 * @property array                 $plugin_info
 * @property string                $plugin_file
 * @property \WP_Filesystem_Direct $wp_filesystem
 */
abstract class Plugin extends Component {
	/**
	 * Default version constant
	 */
	const VERSION = '';
	/**
	 * Default slug constant
	 */
	const PLUGIN_SLUG = '';

	/**
	 * Path to plugin entry file
	 *
	 * @var string
	 */
	protected $plugin_file;
	/**
	 * Dependency Container
	 *
	 * @var Dice
	 */
	protected $container;

	/**
	 * Dependency Container
	 *
	 * @var \WP_Filesystem_Direct
	 */
	protected $wp_filesystem;

	/**
	 * PluginAbstract constructor.
	 */
	public function __construct() {
		$this->find_plugin_file();
		$this->set_container();

	}

	/**
	 *
	 */
	protected function find_plugin_file() {
		$dir  = dirname( ( new \ReflectionClass( $this ) )->getFileName() );
		$file = null;
		do {
			$last_dir = $dir;
			$dir      = dirname( $dir );
			$file     = $dir . DIRECTORY_SEPARATOR . $this->plugin->get_slug() . '.php';
		} while ( ! $this->get_wp_filesystem()->is_file( $file ) && $dir !== $last_dir );
		$this->plugin_file = $file;
	}

	/**
	 * @return \WP_Filesystem_Direct
	 */
	public function get_wp_filesystem( $args = [] ) {
		/** @var \WP_Filesystem_Direct $wp_filesystem */
		global $wp_filesystem;
		$original_wp_filesystem = $wp_filesystem;
		if ( null === $this->wp_filesystem ) {
			require_once ABSPATH . '/wp-admin/includes/file.php';
			add_filter( 'filesystem_method', [ $this, 'filesystem_method_override' ] );
			WP_Filesystem( $args );
			remove_filter( 'filesystem_method', [ $this, 'filesystem_method_override' ] );
			$this->wp_filesystem = $wp_filesystem;
			$wp_filesystem       = $original_wp_filesystem;
		}

		return $this->wp_filesystem;
	}

	/**
	 * @return void
	 */
	abstract public function activate();

	/**
	 * @return void
	 */
	abstract public function deactivate();

	/**
	 * @return void
	 */
	abstract public function uninstall();

	/**
	 * @return string
	 */
	public function get_plugin_file() {
		return $this->plugin_file;
	}

	/**
	 * @return Dice
	 */
	public function get_container() {
		return $this->container;
	}


	/**
	 * @throws \ComposePress\Core\Exception\ContainerInvalid
	 * @throws \ComposePress\Core\Exception\ContainerNotExists
	 */
	protected function set_container() {
		$slug      = str_replace( '-', '_', static::PLUGIN_SLUG );
		$container = "{$slug}_container";
		if ( ! function_exists( $container ) ) {
			throw new ContainerNotExists( sprintf( 'Container function %s does not exist.', $container ) );
		}
		$this->container = $container();
		if ( ! ( $this->container instanceof Dice ) ) {
			throw new ContainerInvalid( sprintf( 'Container function %s does not return a Dice instance.', $container ) );
		}
	}

	/**
	 * Plugin initialization
	 */
	public function init() {
		if ( ! $this->get_dependencies_exist() ) {
			return;
		}
		$this->setup_components();
	}

	/**
	 * @return bool
	 */
	protected function get_dependencies_exist() {
		return true;
	}

	/**
	 * @return string
	 */
	public function get_version() {
		return static::VERSION;
	}

	/**
	 * @return string
	 */
	public function get_safe_slug() {
		return strtolower( str_replace( '-', '_', $this->get_slug() ) );
	}

	/**
	 * @return string
	 */
	public function get_slug() {
		return static::PLUGIN_SLUG;
	}

	/**
	 * @param string $field
	 *
	 * @return string|array
	 */
	public function get_plugin_info( $field = null ) {
		$info = get_plugin_data( $this->plugin_file );
		if ( null !== $field && isset( $info[ $field ] ) ) {
			return $info[ $field ];
		}

		return $info;
	}

	/**
	 * @return string
	 */
	public function filesystem_method_override() {
		return 'direct';
	}

	/**
	 * @param $file
	 *
	 * @return string
	 */
	public function get_asset_url( $file ) {
		if ( $this->get_wp_filesystem()->is_file( $file ) ) {
			$file = str_replace( plugin_dir_path( $this->plugin_file ), '', $file );
		}

		return plugins_url( $file, __FILE__ );
	}
}
