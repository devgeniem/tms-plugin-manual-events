<?php
/**
 *  Copyright (c) 2023. Geniem Oy
 */

namespace TMS\Plugin\ManualEvents\PostType;

use Geniem\ACF\Field;
use Geniem\ACF\Group;
use Geniem\ACF\Exception;
use Geniem\ACF\RuleGroup;
use TMS\Theme\Base\Logger;
use Geniem\ACF\ConditionalLogicGroup;
use TMS\Plugin\ManualEvents\Taxonomy\ManualEventCategory;

/**
 * Class ManualEvent
 *
 * @package TMS\Plugin\ManualEvents\PostType
 */
class ManualEvent {

    /**
     * This defines the slug of this post type.
     */
    public const SLUG = 'manual-event-cpt';

    /**
     * This defines what is shown in the url. This can
     * be different than the slug which is used to register the post type.
     *
     * @var string
     */
    private $url_slug = 'manual-event';

    /**
     * Define the CPT description
     *
     * @var string
     */
    private $description = '';

    /**
     * This is used to position the post type menu in admin.
     *
     * @var int
     */
    private $menu_order = 41;

    /**
     * This defines the CPT icon.
     *
     * @var string
     */
    private $icon = 'dashicons-heart';

    /**
     * Constructor
     */
    public function __construct() {
        // Make possible description text translatable.
        $this->description = _x( 'manual-event', 'theme CPT', 'tms-plugin-manual-events' );

        add_action( 'init', \Closure::fromCallable( [ $this, 'register' ] ), 100, 0 );
        add_action( 'acf/init', \Closure::fromCallable( [ $this, 'fields' ] ), 50, 0 );
    }

    /**
     * Add hooks and filters from this controller
     *
     * @return void
     */
    public function hooks() : void {
    }

    /**
     * This registers the post type.
     *
     * @return void
     */
    private function register() {
        $labels = [
            'name'                  => 'Manuaaliset tapahtumat',
            'singular_name'         => 'Manuaalinen tapahtuma',
            'menu_name'             => 'Manuaaliset tapahtumat',
            'name_admin_bar'        => 'Manuaaliset tapahtumat',
            'archives'              => 'Arkistot',
            'attributes'            => 'Ominaisuudet',
            'parent_item_colon'     => 'Vanhempi:',
            'all_items'             => 'Kaikki',
            'add_new_item'          => 'Lisää uusi',
            'add_new'               => 'Lisää uusi',
            'new_item'              => 'Uusi',
            'edit_item'             => 'Muokkaa',
            'update_item'           => 'Päivitä',
            'view_item'             => 'Näytä',
            'view_items'            => 'Näytä kaikki',
            'search_items'          => 'Etsi',
            'not_found'             => 'Ei löytynyt',
            'not_found_in_trash'    => 'Ei löytynyt roskakorista',
            'featured_image'        => 'Kuva',
            'set_featured_image'    => 'Aseta kuva',
            'remove_featured_image' => 'Poista kuva',
            'use_featured_image'    => 'Käytä kuvana',
            'insert_into_item'      => 'Aseta julkaisuun',
            'uploaded_to_this_item' => 'Lisätty tähän julkaisuun',
            'items_list'            => 'Listaus',
            'items_list_navigation' => 'Listauksen navigaatio',
            'filter_items_list'     => 'Suodata listaa',
        ];

        $rewrite = [
            'slug'       => $this->url_slug,
            'with_front' => true,
            'pages'      => true,
            'feeds'      => true,
        ];

        $args = [
            'label'               => $labels['name'],
            'description'         => '',
            'labels'              => $labels,
            'supports'            => [ 'title', 'thumbnail', 'revisions' ],
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => $this->menu_order,
            'menu_icon'           => $this->icon,
            'show_in_admin_bar'   => true,
            'show_in_nav_menus'   => true,
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => $rewrite,
            'capability_type'     => 'manual_event',
            'map_meta_cap'        => true,
            'show_in_rest'        => true,
        ];

        $args = apply_filters(
            'tms/post_type/' . static::SLUG . '/args',
            $args
        );

        register_post_type( static::SLUG, $args );
    }

    /**
     * Register fields
     */
    protected function fields() {
        try {
            $group_title = _x( 'Tiedot', 'theme ACF', 'tms-theme-amuri' );

            $field_group = ( new Group( $group_title ) )
                ->set_key( 'fg_manual_event_fields' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'post_type', '==', static::SLUG );

            $field_group
                ->add_rule_group( $rule_group )
                ->set_position( 'normal' )
                ->set_hidden_elements(
                    [
                        'discussion',
                        'comments',
                        'format',
                        'send-trackbacks',
                    ]
                );

            $field_group->add_fields(
                apply_filters(
                    'tms/acf/group/' . $field_group->get_key() . '/fields',
                    [
                        $this->get_event_tab( $field_group->get_key() ),
                    ]
                )
            );

            $field_group = apply_filters(
                'tms/acf/group/' . $field_group->get_key(),
                $field_group
            );

            $field_group->register();
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTraceAsString() );
        }
    }

    /**
     * Get event tab
     *
     * @param string $key Field group key.
     *
     * @return Field\Tab
     * @throws Exception In case of invalid option.
     */
    protected function get_event_tab( string $key ) : ?Field\Tab {
        $strings = [
            'tab'                => 'Tapahtuma',
            'short_description'  => [
                'label'        => 'Lyhyt kuvaus',
                'instructions' => '',
            ],
            'description'        => [
                'label'        => 'Kuvaus',
                'instructions' => '',
            ],
            'start_datetime'     => [
                'label'        => 'Aloitusajankohta',
                'instructions' => '',
            ],
            'end_datetime'       => [
                'label'        => 'Päättymisajankohta',
                'instructions' => '',
            ],
            'recurring_event'    => [
                'label'        => 'Toistuva tapahtuma',
                'instructions' => '',
            ],
            'recurring_dates'    => [
                'label'        => 'Toistuvan tapahtuman ajankohdat',
                'button_label' => 'Lisää ajankohta',
                'instructions' => '',
            ],
            'location'           => [
                'label'       => 'Sijainti',
                'name'        => [
                    'label'        => 'Sijainti',
                    'instructions' => '',
                ],
                'description' => [
                    'label'        => 'Sijainnin kuvaus',
                    'instructions' => '',
                ],
                'extra_info'  => [
                    'label'        => 'Sijainnin lisätiedot',
                    'instructions' => '',
                ],
                'info_url'    => [
                    'label'        => 'Sijainnin lisätietolinkki',
                    'instructions' => '',
                ],
            ],
            'price'              => [
                'label'       => 'Hinta',
                'is_free'     => [
                    'label'        => 'Ilmainen tapahtuma?',
                    'instructions' => '',
                ],
                'price'       => [
                    'label'        => 'Hinta',
                    'instructions' => '',
                ],
                'description' => [
                    'label'        => 'Hinnan kuvaus',
                    'instructions' => '',
                ],
                'info_url'    => [
                    'label'        => 'Hinnan lisätietolinkki',
                    'instructions' => '',
                ],
            ],
            'provider'           => [
                'label' => 'Järjestäjä',
                'name'  => [
                    'label'        => 'Järjestäjä',
                    'instructions' => '',
                ],
                'email' => [
                    'label'        => 'Sähköposti',
                    'instructions' => '',
                ],
                'phone' => [
                    'label'        => 'Puhelin',
                    'instructions' => '',
                ],
                'link'  => [
                    'label'        => 'WWW-osoite',
                    'instructions' => '',
                ],
            ],
            'is_virtual_event'   => [
                'label'        => 'Virtuaalitapahtuma?',
                'instructions' => '',
            ],
            'virtual_event_link' => [
                'label'        => 'Virtuaalitapahtuman linkki',
                'instructions' => '',
            ],
        ];

        try {
            $tab = ( new Field\Tab( $strings['tab'] ) )
                ->set_placement( 'left' );

            $description = ( new Field\Wysiwyg( $strings['description']['label'] ) )
                ->set_key( "{$key}_description" )
                ->set_name( 'description' )
                ->set_instructions( $strings['description']['instructions'] )
                ->disable_media_upload()
                ->redipress_include_search( 'wp_strip_all_tags' );

            $short_description = ( new Field\Textarea( $strings['short_description']['label'] ) )
                ->set_key( "{$key}_short_description" )
                ->set_name( 'short_description' )
                ->set_instructions( $strings['short_description']['instructions'] )
                ->redipress_include_search();

            $start_datetime = ( new Field\DateTimePicker( $strings['start_datetime']['label'] ) )
                ->set_key( "{$key}_start_datetime" )
                ->set_name( 'start_datetime' )
                ->set_instructions( $strings['start_datetime']['instructions'] )
                ->set_display_format( 'j.n.Y H:i' )
                ->set_return_format( 'Y-m-d H:i:s' )
                ->redipress_add_queryable( 'start_datetime' );

            $end_datetime = ( new Field\DateTimePicker( $strings['end_datetime']['label'] ) )
                ->set_key( "{$key}_end_datetime" )
                ->set_name( 'end_datetime' )
                ->set_instructions( $strings['end_datetime']['instructions'] )
                ->set_display_format( 'j.n.Y H:i' )
                ->set_return_format( 'Y-m-d H:i:s' )
                ->redipress_add_queryable( 'end_datetime' );

            $recurring_event = ( new Field\TrueFalse( $strings['recurring_event']['label'] ) )
                ->set_key( "{$key}_recurring_event" )
                ->set_name( 'recurring_event' )
                ->set_instructions( $strings['recurring_event']['instructions'] )
                ->use_ui();

            $recurring_dates = ( new Field\Repeater( $strings['recurring_dates']['label'] ) )
                ->set_key( "{$key}_dates" )
                ->set_name( 'dates' )
                ->set_instructions( $strings['recurring_dates']['instructions'] )
                ->set_button_label( $strings['recurring_dates']['button_label'] );

            $recurring_start_datetime = ( new Field\DateTimePicker( $strings['start_datetime']['label'] ) )
                ->set_key( "{$key}_start" )
                ->set_name( 'start' )
                ->set_instructions( $strings['start_datetime']['instructions'] )
                ->set_display_format( 'j.n.Y H:i' )
                ->set_return_format( 'Y-m-d H:i:s' );

            $recurring_end_datetime = ( new Field\DateTimePicker( $strings['end_datetime']['label'] ) )
                ->set_key( "{$key}_end" )
                ->set_name( 'end' )
                ->set_instructions( $strings['end_datetime']['instructions'] )
                ->set_display_format( 'j.n.Y H:i' )
                ->set_return_format( 'Y-m-d H:i:s' );

            $location_name = ( new Field\Text( $strings['location']['name']['label'] ) )
                ->set_key( "{$key}_location_name" )
                ->set_name( 'location_name' )
                ->set_instructions( $strings['location']['name']['instructions'] )
                ->redipress_include_search();

            $location_description = ( new Field\Textarea( $strings['location']['description']['label'] ) )
                ->set_key( "{$key}_location_description" )
                ->set_name( 'location_description' )
                ->set_instructions( $strings['location']['description']['instructions'] )
                ->redipress_include_search();

            $location_extra_info = ( new Field\Textarea( $strings['location']['extra_info']['label'] ) )
                ->set_key( "{$key}_location_extra_info" )
                ->set_name( 'location_extra_info' )
                ->set_instructions( $strings['location']['extra_info']['instructions'] )
                ->redipress_include_search();

            $location_info_url = ( new Field\Link( $strings['location']['info_url']['label'] ) )
                ->set_key( "{$key}_location_info_url" )
                ->set_name( 'location_info_url' )
                ->set_instructions( $strings['location']['info_url']['instructions'] );

            $price_is_free = ( new Field\TrueFalse( $strings['price']['is_free']['label'] ) )
                ->set_key( "{$key}_price_is_free" )
                ->set_name( 'price_is_free' )
                ->set_instructions( $strings['price']['is_free']['instructions'] )
                ->use_ui();

            $price_price = ( new Field\Text( $strings['price']['price']['label'] ) )
                ->set_key( "{$key}_price_price" )
                ->set_name( 'price_price' )
                ->set_instructions( $strings['price']['price']['instructions'] );

            $price_description = ( new Field\Textarea( $strings['price']['description']['label'] ) )
                ->set_key( "{$key}_price_description" )
                ->set_name( 'price_description' )
                ->set_instructions( $strings['price']['description']['instructions'] )
                ->redipress_include_search();

            $price_info_url = ( new Field\Link( $strings['price']['info_url']['label'] ) )
                ->set_key( "{$key}_price_info_url" )
                ->set_name( 'price_info_url' )
                ->set_instructions( $strings['price']['info_url']['instructions'] );

            $provider_name = ( new Field\Text( $strings['provider']['name']['label'] ) )
                ->set_key( "{$key}_provider_name" )
                ->set_name( 'provider_name' )
                ->set_instructions( $strings['provider']['name']['instructions'] )
                ->redipress_include_search();

            $provider_email = ( new Field\Email( $strings['provider']['email']['label'] ) )
                ->set_key( "{$key}_provider_email" )
                ->set_name( 'provider_email' )
                ->set_instructions( $strings['provider']['email']['instructions'] );

            $provider_phone = ( new Field\Text( $strings['provider']['phone']['label'] ) )
                ->set_key( "{$key}_provider_phone" )
                ->set_name( 'provider_phone' )
                ->set_instructions( $strings['provider']['phone']['instructions'] );

            $provider_link = ( new Field\Link( $strings['provider']['link']['label'] ) )
                ->set_key( "{$key}_provider_link" )
                ->set_name( 'provider_link' )
                ->set_instructions( $strings['provider']['link']['instructions'] );

            $is_virtual_event = ( new Field\TrueFalse( $strings['is_virtual_event']['label'] ) )
                ->set_key( "{$key}_is_virtual_event" )
                ->set_name( 'is_virtual_event' )
                ->set_instructions( $strings['is_virtual_event']['instructions'] )
                ->use_ui();

            $virtual_event_link = ( new Field\Link( $strings['virtual_event_link']['label'] ) )
                ->set_key( "{$key}_virtual_event_link" )
                ->set_name( 'virtual_event_link' )
                ->set_instructions( $strings['virtual_event_link']['instructions'] );

            $location_group = ( new Field\Group( $strings['location']['label'] ) )
                ->set_key( "{$key}_location" )
                ->set_name( 'location' )
                ->add_fields( [
                    $location_name,
                    $location_description,
                    $location_extra_info,
                    $location_info_url,
                ] );

            $price_group = ( new Field\Group( $strings['price']['label'] ) )
                ->set_key( "{$key}_price" )
                ->set_name( 'price' )
                ->add_fields( [
                    $price_price,
                    $price_description,
                    $price_info_url,
                ] );

            $provider_group = ( new Field\Group( $strings['provider']['label'] ) )
                ->set_key( "{$key}_provider" )
                ->set_name( 'provider' )
                ->add_fields( [
                    $provider_name,
                    $provider_email,
                    $provider_phone,
                    $provider_link,
                ] );

            $rule_group_has_price        = ( new ConditionalLogicGroup() )
                ->add_rule( $price_is_free->get_key(), '!=', '1' );
            $rule_group_is_virtual_event = ( new ConditionalLogicGroup() )
                ->add_rule( $is_virtual_event->get_key(), '==', '1' );
            $rule_group_is_not_recurring     = ( new ConditionalLogicGroup() )
                ->add_rule( $recurring_event->get_key(), '==', '0' );
            $rule_group_is_recurring     = ( new ConditionalLogicGroup() )
                ->add_rule( $recurring_event->get_key(), '==', '1' );

            $price_group->add_conditional_logic( $rule_group_has_price );
            $virtual_event_link->add_conditional_logic( $rule_group_is_virtual_event );
            $start_datetime->add_conditional_logic( $rule_group_is_not_recurring );
            $end_datetime->add_conditional_logic( $rule_group_is_not_recurring );
            $recurring_dates->add_conditional_logic( $rule_group_is_recurring );
            $recurring_dates->add_fields( [ $recurring_start_datetime, $recurring_end_datetime ] );

            $tab->add_fields( [
                $description,
                $short_description,
                $recurring_event,
                $start_datetime,
                $end_datetime,
                $recurring_dates,
                $location_group,
                $price_is_free,
                $price_group,
                $provider_group,
                $is_virtual_event,
                $virtual_event_link,
            ] );

            return $tab;
        }
        catch ( \Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }

        return null;
    }

    /**
     * Normalize event data with data from LinkedEvents.
     *
     * @param object $event The event object.
     *
     * @return array
     */
    public static function normalize_event( $event ) { // phpcs:disable Generic.Metrics.CyclomaticComplexity
        $normalized_event = [
            'name'               => $event->title ?? '',
            'short_description'  => $event->short_description ?? '',
            'description'        => $event->description ?? '',
            'date'               => static::get_event_date( $event ),
            'time'               => static::get_event_time( $event ),
            // Include raw dates for possible sorting.
            'start_date_raw'     => static::get_as_datetime( $event->start_datetime ),
            'end_date_raw'       => static::get_as_datetime( $event->end_datetime ),
            'image'              => $event->image ?? '',
            'url'                => $event->url ?? '',
            'is_virtual_event'   => $event->is_virtualevent ?? false,
            'virtual_event_link' => $event->virtual_event_link ?? '',
            'date_title'         => __( 'Dates', 'tms-theme-base' ),
            'time_title'         => __( 'Time', 'tms-theme-base' ),
            'location_title'     => __( 'Location', 'tms-theme-base' ),
            'price_title'        => __( 'Price', 'tms-theme-base' ),
            'provider_title'     => __( 'Organizer', 'tms-theme-base' ),
            'recurring'          => ! empty( $event->dates ) ? count( $event->dates ) > 1 : null,
            'dates'              => static::get_event_dates( $event ),
        ];

        if ( ! empty( $event->location ) ) {
            $normalized_event['location'] = [
                'name'        => $event->location['location_name'],
                'description' => $event->location['location_description'],
                'extra_info'  => $event->location['location_extra_info'],
                'info_url'    => [
                    'title' => $event->location['location_info_url']['title'] ?? '',
                    'url'   => $event->location['location_info_url']['url'] ?? '',
                ],
            ];
        }

        if ( ! empty( $event->price ) ) {
            $normalized_event['price'] = [
                [
                    'price'       => $event->price_is_free
                        ? __( 'Free', 'tms-theme-base' )
                        : $event->price['price_price'],
                    'description' => $event->price['price_description'],
                    'info_url'    => [
                        'title' => $event->price['price_info_url']['title'] ?? '',
                        'url'   => $event->price['price_info_url']['url'] ?? '',
                    ],
                ],
            ];
        }

        if ( ! empty( $event->provider ) ) {
            $normalized_event['provider'] = [
                'name'  => $event->provider['provider_name'],
                'email' => $event->provider['provider_email'],
                'phone' => $event->provider['provider_phone'],
                'link'  => [
                    'title' => $event->provider['provider_link']['title'] ?? '',
                    'url'   => $event->provider['provider_link']['url'] ?? '',
                ],
            ];
        }

        $normalized_event['keywords'] = wp_get_post_terms(
            $event->id,
            ManualEventCategory::SLUG,
            [ 'fields' => 'id=>name' ]
        );

        if ( ! empty( $normalized_event['keywords'] ) ) {
            // Get primary keyword from TSF, fallback to first keyword.
            $primary_keyword_id                  = function_exists( 'the_seo_framework' )
                ? the_seo_framework()->get_primary_term_id( $event->id, ManualEventCategory::SLUG )
                : array_key_first( $normalized_event['keywords'] );
            $normalized_event['primary_keyword'] = $normalized_event['keywords'][ $primary_keyword_id ];
        }

        return $normalized_event;
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
    }

    /**
     * Get event date
     *
     * @param object $event Event object.
     *
     * @return string|null
     */
    protected static function get_event_date( $event ) {
        if ( empty( $event->start_datetime ) ) {
            return null;
        }

        $start_time  = static::get_as_datetime( $event->start_datetime );
        $end_time    = static::get_as_datetime( $event->end_datetime );
        $date_format = get_option( 'date_format' );

        if ( $start_time && $end_time && $start_time->diff( $end_time )->days >= 1 ) {
            return sprintf(
                '%s - %s',
                $start_time->format( $date_format ),
                $end_time->format( $date_format )
            );
        }

        return $start_time->format( $date_format );
    }

    /**
     * Get event time
     *
     * @param object $event Event object.
     *
     * @return string|null
     */
    protected static function get_event_time( $event ) {
        if ( empty( $event->start_datetime ) ) {
            return null;
        }

        $start_time  = static::get_as_datetime( $event->start_datetime );
        $end_time    = static::get_as_datetime( $event->end_datetime );
        $time_format = 'H.i';

        if ( $start_time && $end_time ) {
            return sprintf(
                '%s - %s',
                $start_time->format( $time_format ),
                $end_time->format( $time_format )
            );
        }

        return $start_time->format( $time_format );
    }

    /**
     * Get string as date time.
     *
     * @param string $value Date time string.
     *
     * @return \DateTime|null
     */
    protected static function get_as_datetime( $value ) {
        try {
            // Manual event dates are set in Helsinki timezone, so let's enforce that for sorting purposes.
            $dt = new \DateTime( $value, new \DateTimeZone( 'Europe/Helsinki' ) );

            return $dt;
        }
        catch ( \Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }

        return null;
    }

    /**
     * Get event dates info
     *
     * @param object $event Event object.
     *
     * @return array
     */
    public static function get_event_dates( $event ) {
        $dates = [];

        if ( empty( $event->dates ) ) {
            return $dates;
        }

        foreach ( $event->dates as $date ) {
            $dates[] = [
                'date' => self::compare_dates( $date['start'], $date['end'] ),
            ];
        }

        return $dates;
    }

    /**
     * Get event date
     *
     * @param string $start Event startdate.
     * @param string $end Event enddate.
     *
     * @return string|null
     */
    public static function compare_dates( $start, $end ) {
        if ( empty( $start ) ) {
            return null;
        }

        $start_time  = static::get_as_datetime( $start );
        $end_time    = static::get_as_datetime( $end );
        $date_format = 'j.n.Y H.i';

        if ( $start_time && $end_time && $start_time->diff( $end_time )->days >= 1 ) {
            return sprintf(
                '%s - %s',
                $start_time->format( $date_format ),
                $end_time->format( $date_format )
            );
        }

        return sprintf(
            '%s - %s',
            $start_time->format( 'j.n.Y H.i' ),
            $end_time->format( 'H.i' )
        );
    }
}
