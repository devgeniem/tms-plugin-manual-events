<?php
/**
 * Copyright (c) 2023 Geniem Oy.
 */

namespace Tms\Plugin\ManualEvents;

/**
 * Class Plugin
 *
 * @package Tms\Plugin\ManualEvents
 */
final class Plugin {

    /**
     * Holds the singleton.
     *
     * @var Plugin
     */
    protected static $instance;

    /**
     * Current plugin version.
     *
     * @var string
     */
    protected $version = '';

    /**
     * Get the instance.
     *
     * @return Plugin
     */
    public static function get_instance() : Plugin {
        return self::$instance;
    }

    /**
     * The plugin directory path.
     *
     * @var string
     */
    protected $plugin_path = '';

    /**
     * The plugin root uri without trailing slash.
     *
     * @var string
     */
    protected $plugin_uri = '';

    /**
     * Get the version.
     *
     * @return string
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Get the plugin directory path.
     *
     * @return string
     */
    public function get_plugin_path() : string {
        return $this->plugin_path;
    }

    /**
     * Get the plugin directory uri.
     *
     * @return string
     */
    public function get_plugin_uri() : string {
        return $this->plugin_uri;
    }

    /**
     * Initialize the plugin by creating the singleton.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    public static function init( $version = '', $plugin_path = '' ) {
        if ( empty( self::$instance ) ) {
            self::$instance = new self( $version, $plugin_path );
            self::$instance->hooks();
        }
    }

    /**
     * Get the plugin instance.
     *
     * @return Plugin
     */
    public static function plugin() {
        return self::$instance;
    }

    /**
     * Initialize the plugin functionalities.
     *
     * @param string $version     The current plugin version.
     * @param string $plugin_path The plugin path.
     */
    protected function __construct( $version = '', $plugin_path = '' ) {
        $this->version     = $version;
        $this->plugin_path = $plugin_path;
        $this->plugin_uri  = plugin_dir_url( $plugin_path ) . basename( $this->plugin_path );
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() {
    }
}
