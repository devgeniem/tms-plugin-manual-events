<?php
/**
 *  Copyright (c) 2023. Geniem Oy
 */

namespace TMS\Plugin\ManualEvents\Taxonomy;

use TMS\Plugin\ManualEvents\PostType\ManualEvent;

/**
 * Class ManualEventCategory
 *
 * @package TMS\Plugin\ManualEvents\Taxonomy
 */
class ManualEventCategory {

    /**
     * This defines the slug of this taxonomy.
     */
    const SLUG = 'manual-event-category';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', \Closure::fromCallable( [ $this, 'register' ] ), 15 );
    }

    /**
     * This registers the post type.
     *
     * @return void
     */
    private function register() {
        $labels = [
            'name'                       => 'Tapahtumakategoriat',
            'singular_name'              => 'Tapahtumakategoria',
            'menu_name'                  => 'Tapahtumakategoriat',
            'all_items'                  => 'Kaikki tapahtumakategoriat',
            'new_item_name'              => 'Lisää uusi tapahtumakategoria',
            'add_new_item'               => 'Lisää uusi tapahtumakategoria',
            'edit_item'                  => 'Muokkaa tapahtumakategoriaa',
            'update_item'                => 'Päivitä tapahtumakategoria',
            'view_item'                  => 'Näytä tapahtumakategoria',
            'separate_items_with_commas' => 'Erottele kategoriat pilkulla',
            'add_or_remove_items'        => 'Lisää tai poista kategoria',
            'choose_from_most_used'      => 'Suositut kategoriat',
            'popular_items'              => 'Suositut kategoriat',
            'search_items'               => 'Etsi kategoria',
            'not_found'                  => 'Ei tuloksia',
            'no_terms'                   => 'Ei tuloksia',
            'items_list'                 => 'Tapahtumakategoriat',
            'items_list_navigation'      => 'Tapahtumakategoriat',
        ];

        $filter_prefix = 'tms/taxonomy/' . static::SLUG;

        $labels = \apply_filters(
            $filter_prefix . '/labels',
            $labels
        );

        $capabilities = \apply_filters(
            $filter_prefix . '/capabilities',
            [
                'manage_terms' => 'manage_manual_event_categories',
                'edit_terms'   => 'edit_manual_event_categories',
                'delete_terms' => 'delete_manual_event_categories',
                'assign_terms' => 'assign_manual_event_categories',
            ]
        );

        $args = [
            'labels'            => $labels,
            'capabilities'      => $capabilities,
            'hierarchical'      => true,
            'public'            => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud'     => false,
            'show_in_rest'      => true,
        ];

        $args = \apply_filters( $filter_prefix . '/args', $args );
        $slug = \apply_filters( $filter_prefix . '/slug', static::SLUG );

        \register_taxonomy( $slug, [ ManualEvent::SLUG ], $args );
    }
}
