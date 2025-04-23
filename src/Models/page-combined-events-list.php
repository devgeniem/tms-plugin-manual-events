<?php

use TMS\Plugin\ManualEvents\PostType;
use TMS\Theme\Base\Traits\Pagination;
use TMS\Theme\Base\Logger;
use TMS\Theme\Base\Formatters\EventzFormatter;

/**
 * Copyright (c) 2023. Geniem Oy
 * Template Name: Tapahtumalistaus (yhdistetty)
 */

/**
 * The PageCombinedEventsList class.
 */
class PageCombinedEventsList extends PageEventsSearch {

    use Pagination;

    /**
     * Template
     */
    const TEMPLATE = 'page-combined-events-list.php';

    /**
     * Maximum events per page.
     */
    const MAX_EVENTS_PER_PAGE = '999';

    /**
     * Return form fields.
     *
     * @return array
     */
    public function form() {
        return [];
    }

    /**
     * Description text
     */
    public function description(): ?string {
        return get_field( 'description' );
    }

    /**
     * Get no results text
     *
     * @return string
     */
    public function no_results(): string {
        return __( 'No results', 'tms-theme-base' );
    }

    /**
     * Is grid view
     *
     * @return bool
     */
    public function is_grid(): bool {
        $is_grid = \get_field( 'layout' ) ?? 'grid';

        return $is_grid === 'grid';
    }

    /**
     * Get item partial
     *
     * @return string
     */
    public function item_partial(): string {
        $part = $this->is_grid() ? 'grid' : 'list';

        return 'views/page-events-calendar/page-events-calendar-item-' . $part;
    }

    /**
     * Get events
     */
    public function events(): ?array {
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
    protected function get_events(): array {

        $paged              = \get_query_var( 'paged', 1 );
        $skip               = 0;
        $disable_pagination = \get_field( 'disable_pagination' );
        $events_per_page    = $disable_pagination === true ? self::MAX_EVENTS_PER_PAGE : \get_option( 'posts_per_page' );

        if ( $paged > 1 ) {
            $skip = ( $paged - 1 ) * $events_per_page;
        }

        $params = [
            'category_id' => \get_field( 'category' ),
            'page_size'   => 200, // Use an arbitrary limit as a sanity check.
            'show_images' => \get_field( 'show_images' ),
            'start'       => date( 'Y-m-d' ),
            'areas'       => '',
            'tags'        => '',
            'targets'     => '',
            'sort'        => '',
            'q'           => '',
            'page'        => 1,
        ];

        $formatter = new EventzFormatter();
        $params    = $formatter->format_query_params( $params );

        $cache_group = 'page-combined-events-list';
        $cache_key   = md5( \wp_json_encode( $params ) );
        $response    = \wp_cache_get( $cache_key, $cache_group );

        if ( empty( $response ) ) {
            $response           = $this->do_get_events( $params );
            $response['events'] = array_merge(
                $response['events'],
                $this->get_manual_events(),
                $this->get_recurring_manual_events()
            );

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

        if ( ! empty( $response['events'] ) ) {
            $this->set_pagination_data( count( $response['events'] ) );
            $response['events'] = array_slice( $response['events'], $skip, $events_per_page );
        }

        return $response;
    }

    /**
     * Get manual events.
     *
     * @return array
     */
    protected function get_manual_events(): array {
        $args = [
            'post_type'      => PostType\ManualEvent::SLUG,
            'posts_per_page' => 200, // phpcs:ignore
            'meta_query'     => [
                'relation'               => 'AND',
                'end_date_clause'        => [
                    [
                        'key'     => 'end_datetime',
                        'value'   => date( 'Y-m-d' ),
                        'compare' => '>=',
                        'type'    => 'DATE',
                    ],
                ],
                'recurring_event_clause' => [
                    [
                        'key'   => 'recurring_event',
                        'value' => 0,
                    ],
                ],
            ],
        ];

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

    /**
     * Get recurring manual events.
     *
     * @return array
     */
    protected function get_recurring_manual_events(): array {
        $args = [
            'post_type'      => PostType\ManualEvent::SLUG,
            'posts_per_page' => 200, // phpcs:ignore
            'meta_query'     => [
                [
                    'key'   => 'recurring_event',
                    'value' => 1,
                ],
            ],
        ];

        $query = new \WP_Query( $args );

        if ( empty( $query->posts ) ) {
            return [];
        }

        // Loop through events
        $recurring_events = array_map( function ( $e ) {
            $id       = $e->ID;
            $event    = (object) \get_fields( $id );
            $time_now = \current_datetime();
            $timezone = new DateTimeZone( 'Europe/Helsinki' );

            foreach ( $event->dates as $date ) {
                $event_start = new DateTime( $date['start'], $timezone );
                $event_end   = new DateTime( $date['end'], $timezone );

                // Return only ongoing or next upcoming event
                if ( ( $time_now > $event_start && $time_now < $event_end ) || $time_now < $event_start ) {
                    $event->id             = $id;
                    $event->title          = \get_the_title( $id );
                    $event->url            = \get_permalink( $id );
                    $event->image          = \has_post_thumbnail( $id ) ? \get_the_post_thumbnail_url( $id, 'medium_large' ) : null; // phpcs:ignore
                    $event->start_datetime = $date['start'];
                    $event->end_datetime   = $date['end'];

                    return PostType\ManualEvent::normalize_event( $event );
                }
            }
        }, $query->posts );

        return array_filter( $recurring_events );
    }

    /**
     * Set pagination data
     *
     * @param int $event_count Event count.
     *
     * @return void
     */
    protected function set_pagination_data( int $event_count ): void {
        $disable_pagination = \get_field( 'disable_pagination' );
        $events_per_page    = $disable_pagination === true ? self::MAX_EVENTS_PER_PAGE : \get_option( 'posts_per_page' );

        $per_page = $events_per_page;
        $paged    = \get_query_var( 'paged' ) ? \get_query_var( 'paged' ) : 1;

        $this->pagination           = new stdClass();
        $this->pagination->page     = $paged;
        $this->pagination->per_page = $per_page;
        $this->pagination->items    = $event_count;
        $this->pagination->max_page = (int) ceil( $event_count / $per_page );
    }
}
