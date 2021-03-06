<?php
namespace Rarst\wps;

use Pimple\Container;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * Main plugin's class.
 */
class Plugin extends Container {

	/**
	 * @param array $values
	 */
	public function __construct( $values = array() ) {

		$defaults = array();

		$defaults['tables'] = array(
			'$wp'       => function () {
				global $wp;

				if ( ! $wp instanceof \WP ) {
					return array();
				}

				$output = get_object_vars( $wp );
				unset( $output['private_query_vars'] );
				unset( $output['public_query_vars'] );

				return array_filter( $output );
			},
			'$wp_query' => function () {
				global $wp_query;

				if ( ! $wp_query instanceof \WP_Query ) {
					return array();
				}

				$output               = get_object_vars( $wp_query );
				$output['query_vars'] = array_filter( $output['query_vars'] );
				unset( $output['posts'] );
				unset( $output['post'] );

				return array_filter( $output );
			},
			'$post'     => function () {
				$post = get_post();

				if ( ! $post instanceof \WP_Post ) {
					return array();
				}

				return get_object_vars( $post );
			},
		);

		$defaults['handler.pretty'] = function ( $plugin ) {
			$handler = new PrettyPageHandler();

			foreach ( $plugin['tables'] as $name => $callback ) {
				$handler->addDataTableCallback( $name, $callback );
			}

			// requires Remote Call plugin
			$handler->addEditor( 'phpstorm', 'http://localhost:8091?message=%file:%line' );

			return $handler;
		};

		$defaults['handler.json'] = function () {
			$handler = new Admin_Ajax_Handler();
			$handler->addTraceToOutput( true );
			$handler->onlyForAjaxRequests( true );

			return $handler;
		};

		$defaults['run'] = function ( $plugin ) {
			$run = new Run();
			$run->pushHandler( $plugin['handler.pretty'] );
			$run->pushHandler( $plugin['handler.json'] );

			return $run;
		};

		parent::__construct( array_merge( $defaults, $values ) );
	}

	/**
	 * @return bool
	 */
	public function is_debug() {

		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * @return bool
	 */
	public function is_debug_display() {

		return defined( 'WP_DEBUG_DISPLAY' ) && false !== WP_DEBUG_DISPLAY;
	}

	public function run() {

		if ( ! $this->is_debug() || ! $this->is_debug_display() ) {
			return;
		}

		/** @var Run $run */
		$run = $this['run'];
		$run->register();
		ob_start(); // or we are going to be spitting out WP markup before whoops
	}
} 