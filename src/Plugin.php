<?php
/**
 * Copyright (c) 2023 Geniem Oy.
 */

namespace TMS\Plugin\ManualEvents;

use Geniem\ACF\Field;
use TMS\Plugin\ManualEvents\Fields\PageCombinedEventsListGroup;
use TMS\Plugin\ManualEvents\PostType\ManualEvent;
use TMS\Plugin\ManualEvents\Taxonomy\ManualEventCategory;

/**
 * Class Plugin
 *
 * @package TMS\Plugin\ManualEvents
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

        $this->hooks();
    }

    /**
     * Add plugin hooks and filters.
     */
    protected function hooks() {
        add_action( 'init', \Closure::fromCallable( [ $this, 'load_localization' ] ), 0 );
        add_action( 'init', \Closure::fromCallable( [ $this, 'init_classes' ] ), 0 );
        add_filter( 'pll_get_post_types', \Closure::fromCallable( [ $this, 'add_cpt_to_polylang' ] ), 10, 2 );
        add_filter( 'pll_get_taxonomies', \Closure::fromCallable( [ $this, 'add_tax_to_polylang' ] ), 10, 2 );
        add_filter( 'dustpress/models', \Closure::fromCallable( [ $this, 'dustpress_models' ] ) );
        add_filter( 'dustpress/partials', \Closure::fromCallable( [ $this, 'dustpress_partials' ] ) );
        add_filter( 'page_template', \Closure::fromCallable( [ $this, 'register_page_template_path' ] ) );
        add_filter( 'theme_page_templates', \Closure::fromCallable( [ $this, 'register_page_template' ] ) );

        add_filter(
            'tms/acf/group/fg_page_components/rules',
            \Closure::fromCallable( [ $this, 'alter_component_rules' ] )
        );

        add_filter( 'tms/theme/gutenberg/excluded_templates', [ $this, 'excluded_templates' ] );

        add_filter( 'tms/acf/layout/_events/fields', [ $this, 'layout_events_fields' ], 10, 2 );

        add_filter(
            'tms/theme/layout_events/events',
            \Closure::fromCallable( [ $this, 'layout_events_events' ] ),
            10,
            2
        );
    }

    /**
     * Load plugin localization
     */
    public function load_localization() {
        load_plugin_textdomain(
            'tms-plugin-manual-events',
            false,
            dirname( plugin_basename( __DIR__ ) ) . '/languages/'
        );
    }

    /**
     * Init classes
     */
    protected function init_classes() {
        ( new ManualEvent() );
        ( new ManualEventCategory() );
        ( new PageCombinedEventsListGroup() );
    }

    /**
     * Add plugin post types to Polylang
     *
     * @param array $post_types Registered post types.
     *
     * @return array
     */
    protected function add_cpt_to_polylang( $post_types ) {
        $post_types[ ManualEvent::SLUG ] = ManualEvent::SLUG;

        return $post_types;
    }

    /**
     * This adds the taxonomies that are not public to Polylang translation.
     *
     * @param array   $tax_types   The taxonomy type array.
     * @param boolean $is_settings A not used boolean flag to see if we're in settings.
     *
     * @return array The modified tax_types -array.
     */
    protected function add_tax_to_polylang( $tax_types, $is_settings ) : array { // phpcs:ignore
        $tax_types[ ManualEventCategory::SLUG ] = ManualEventCategory::SLUG;

        return $tax_types;
    }

    /**
     * Add this plugin's models directory to DustPress.
     *
     * @param array $models The original array.
     *
     * @return array
     */
    protected function dustpress_models( array $models = [] ) : array {
        $models[] = $this->plugin_path . '/src/Models/';

        return $models;
    }

    /**
     * Add this plugin's partials directory to DustPress.
     *
     * @param array $partials The original array.
     *
     * @return array
     */
    protected function dustpress_partials( array $partials = [] ) : array {
        $partials[] = $this->plugin_path . '/src/Partials/';

        return $partials;
    }

    /**
     * Register page-combined-events-list.php template path.
     *
     * @param string $template Page template name.
     *
     * @return string
     */
    private function register_page_template_path( string $template ) : string {
        if ( get_page_template_slug() === 'page-combined-events-list.php' ) {
            $template = $this->plugin_path . '/src/Models/page-combined-events-list.php';
        }

        return $template;
    }

    /**
     * Register page-combined-events-list.php making it accessible via page template picker.
     *
     * @param array $templates Page template choices.
     *
     * @return array
     */
    private function register_page_template( $templates ) : array {
        $templates['page-combined-events-list.php'] = __( 'Tapahtumalistaus (yhdistetty)' );

        return $templates;
    }

    /**
     * Hide components from PageCombinedEventsList template.
     *
     * @param array $rules ACF group rules.
     *
     * @return array
     */
    public function alter_component_rules( array $rules ) : array {
        $rules[] = [
            'param'    => 'page_template',
            'operator' => '!=',
            'value'    => \PageCombinedEventsList::TEMPLATE,
        ];

        return $rules;
    }

    /**
     * Exclude Gutenberg from PageCombinedEventsList template.
     *
     * @param array $templates The templates array.
     *
     * @return array
     */
    public function excluded_templates( array $templates ) : array {
        $templates[] = \PageCombinedEventsList::TEMPLATE;

        return $templates;
    }

    /**
     * Filter events layout fields.
     * Add manual event categories field.
     *
     * @param array  $fields The fields.
     * @param string $key The key.
     * @return array
     */
    public function layout_events_fields( $fields, $key ) {
        $manual_event_categories = ( new Field\Taxonomy( 'Tapahtumakategoriat' ) )
            ->set_key( "{$key}_manual_event_categories" )
            ->set_name( 'manual_event_categories' )
            ->set_instructions( 'Koskee vain manuaalisia tapahtumia.' )
            ->set_taxonomy( ManualEventCategory::SLUG )
            ->set_field_type( 'multi_select' );

        $fields[] = $manual_event_categories;

        return $fields;
    }

    /**
     * Filter events for events highlight layout.
     * Add manual events to the list, sort by start date and return correct amount.
     *
     * @param ?array $events The events.
     * @param array  $layout Layout options.
     * @return array
     */
    public function layout_events_events( $events, $layout ) {
        if ( empty( $events ) ) {
            $events = [];
        }

        $curdate      = date( 'Y-m-d' );
        $start_date   = $layout['starts_today'] ? $curdate : $layout['start'];
        $start_date   = $start_date ?: $curdate;
        $end_date     = $layout['end'];
        $count        = $layout['page_size'] ?: 10;
        $categories   = $layout['manual_event_categories'] ?? [];
        $search_query = $layout['text'] ?? '';
        $args         = [
            'post_type'      => PostType\ManualEvent::SLUG,
            'posts_per_page' => $count,
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'key'     => 'start_datetime',
                    'value'   => $start_date,
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
            ],
        ];

        if ( ! empty( $end_date ) ) {
            $args['meta_query'][] = [
                'key'     => 'end_datetime',
                'value'   => $end_date,
                'compare' => '<=',
                'type'    => 'DATE',
            ];
        }

        if ( ! empty( $categories ) ) {
            $args['tax_query'] = [
                [
                    'taxonomy' => Taxonomy\ManualEventCategory::SLUG,
                    'field'    => 'term_id',
                    'terms'    => $categories,
                ],
            ];
        }

        if ( ! empty( $search_query ) ) {
            $args['s'] = $search_query;
        }

        $query = new \WP_Query( $args );

        // Return original events if no manual events found.
        if ( empty( $query->posts ) ) {
            return $events;
        }

        // Normalize the manual events.
        $manual_events = array_map( function ( $e ) {
            $id           = $e->ID;
            $event        = (object) get_fields( $id );
            $event->id    = $id;
            $event->title = get_the_title( $id );
            $event->url   = get_permalink( $id );
            $event->image = has_post_thumbnail( $id ) ? get_the_post_thumbnail_url( $id, 'medium_large' ) : null;

            return ManualEvent::normalize_event( $event );
        }, $query->posts );

        // Merge manual events with original events.
        $events = array_merge( $events, $manual_events );

        // Sort events by start datetime objects.
        usort( $events, function( $a, $b ) {
            return $a['start_date_raw'] <=> $b['start_date_raw'];
        } );

        // Return correct amount of events.
        return array_slice( $events, 0, $count );
    }
}
