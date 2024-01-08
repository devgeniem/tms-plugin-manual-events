<?php

use TMS\Plugin\ManualEvents\PostType;
use TMS\Theme\Base\Logger;
use TMS\Theme\Base\Formatters\EventzFormatter;
use TMS\Theme\Base\Traits;

/**
 * Copyright (c) 2023. Geniem Oy
 * Template Name: Tapahtumahaku (yhdistetty)
 */

/**
 * The PageCombinedEventsSearch class.
 */
class PageCombinedEventsSearch extends PageEventsSearch {

    use Traits\Pagination;

    /**
     * Template
     */
    const TEMPLATE = 'page-combined-events-search.php';

    /**
     * Events search query var names.
     */
    const EVENT_SEARCH_TEXT = 'event_search_text';

    const EVENT_SEARCH_START_DATE = 'event_search_start_date';

    const EVENT_SEARCH_END_DATE = 'event_search_end_date';

    /**
     * Pagination data.
     *
     * @var object
     */
    protected object $pagination;

    /**
     * Return form fields.
     *
     * @return array
     */
    public function form() {
        return [
            'search_term'      => trim( \get_query_var( self::EVENT_SEARCH_TEXT ) ),
            'form_start_date'  => \get_query_var( self::EVENT_SEARCH_START_DATE ),
            'form_end_date'    => \get_query_var( self::EVENT_SEARCH_END_DATE ),
            'seach_term_label' => __( 'Search term', 'tms-theme-base' ),
            'time_frame_label' => __( 'Events from', 'tms-theme-base' ),
            'start_date_label' => __( 'Start date', 'tms-theme-base' ),
            'end_date_label'   => __( 'End date', 'tms-theme-base' ),
            'action'           => \get_the_permalink(),
        ];
    }

    /**
     * Description text
     */
    public function description() : ?string {
        return \get_field( 'description' );
    }

    /**
     * Get no results text
     *
     * @return string
     */
    public function no_results() : ?string {
        return empty( \get_query_var( self::EVENT_SEARCH_TEXT ) )
            ? __( 'No search term given', 'tms-theme-base' )
            : __( 'No results', 'tms-theme-base' );
    }

    /**
     * Get events
     */
    public function events() : ?array {
        try {
            $response = $this->get_events();

            return $response['events'] ?? [];
        }
        catch ( Exception $e ) {
            ( new Logger() )->error( $e->getMessage(), $e->getTrace() );
        }

        return null;
    }

    /**
     * Get events.
     *
     * @return array
     */
    protected function get_events() : array {
        $event_search_text = \get_query_var( self::EVENT_SEARCH_TEXT );
        $start_date        = \get_query_var( self::EVENT_SEARCH_START_DATE );
        $start_date        = ! empty( $start_date ) ? $start_date : date( 'Y-m-d' );

        // Start date can not be in the past.
        $today = date( 'Y-m-d' );
        if ( $start_date < $today ) {
            $start_date = $today;
        }

        $end_date = \get_query_var( self::EVENT_SEARCH_END_DATE );
        $end_date = ! empty( $end_date ) ? $end_date : date( 'Y-m-d', strtotime( '+1 year' ) );

        $paged = \get_query_var( 'paged', 1 );
        $skip  = 0;

        if ( $paged > 1 ) {
            $skip = ( $paged - 1 ) * \get_option( 'posts_per_page' );
        }

        $params = [
            'q'           => $event_search_text,
            'start'       => $start_date,
            'end'         => $end_date,
            'sort'        => 'startDate',
            'size'        => \get_option( 'posts_per_page' ),
            'skip'        => $skip,
            'category_id' => \get_field( 'category' ) ?? [],
            'page_size'   => 200, // Use an arbitrary limit as a sanity check.
            'show_images' => \get_field( 'show_images' ),
            'areas'       => '',
            'tags'        => '',
            'targets'     => '',
            'page'        => 1,
        ];

        $formatter = new EventzFormatter();
        $params    = $formatter->format_query_params( $params );

        $cache_group = 'page-combined-events-search';
        $cache_key   = md5( \wp_json_encode( $params ) );
        $response    = \wp_cache_get( $cache_key, $cache_group );

        if ( empty( $response ) ) {
            $response           = $this->do_get_events( $params );
            $response['events'] = array_merge( $response['events'], $this->get_manual_events( $params ) );

            // Sort events by start datetime objects.
            usort( $response['events'], function( $a, $b ) {
                return $a['start_date_raw'] <=> $b['start_date_raw'];
            } );

            if ( ! empty( $response ) ) {
                \wp_cache_set(
                    $cache_key,
                    $response,
                    $cache_group,
                    MINUTE_IN_SECONDS * 15
                );
            }
        }

        return $response;
    }

    /**
     * Get manual events.
     *
     * @return array
     */
    protected function get_manual_events( $params ) : array {
        $args = [
            'post_type'      => PostType\ManualEvent::SLUG,
            'posts_per_page' => 200, // phpcs:ignore
            's'              => $params['q'] ?? '',
            'meta_query'     => [
                [
                    'key'     => 'end_datetime',
                    'value'   => [
                        $params['start'],
                        $params['end'],
                    ],
                    'compare' => 'BETWEEN',
                    'type'    => 'DATE'
                ],
            ]
        ];

        // If start_date is selected
        if ( \get_query_var( self::EVENT_SEARCH_START_DATE ) ) {
            $args['meta_query'] =
            [
                'relation' => 'AND',
                [
                    'key'     => 'start_datetime',
                    'value'   => $params['start'],
                    'compare' => '>=',
                    'type'    => 'DATE',
                ],
                [
                    'key'     => 'end_datetime',
                    'value'   => $params['end'],
                    'compare' => '<=',
                    'type'    => 'DATE',
                ],
            ];
        }

        $query = new \WP_Query( $args );

        if ( empty( $query->posts ) ) {
            return [];
        }

        $events = array_map( function ( $e ) {
            $id           = $e->ID;
            $event        = (object) \get_fields( $id );
            $event->id    = $id;
            $event->title = \get_the_title( $id );
            $event->url   = \get_permalink( $id );
            $event->image = \has_post_thumbnail( $id ) ? \get_the_post_thumbnail_url( $id, 'medium_large' ) : null;

            return PostType\ManualEvent::normalize_event( $event );
        }, $query->posts );

        return $events;
    }
}
