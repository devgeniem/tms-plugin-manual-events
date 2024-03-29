<?php
/**
 * Copyright (c) 2023. Geniem Oy
 */

namespace TMS\Plugin\ManualEvents\Fields;

use Geniem\ACF\Exception;
use Geniem\ACF\Group;
use Geniem\ACF\RuleGroup;
use Geniem\ACF\Field;
use TMS\Theme\Base\ACF\Field\TextEditor;
use TMS\Theme\Base\ACF\Fields\EventsFields;
use TMS\Theme\Base\Logger;

/**
 * Class PageCombinedEventsSearchGroup.
 *
 * @package TMS\Plugin\ManualEvents\Fields
 */
class PageCombinedEventsSearchGroup {

    /**
     * ManualEventGroup constructor.
     */
    public function __construct() {
        add_action(
            'init',
            \Closure::fromCallable( [ $this, 'register_fields' ] )
        );
    }

    /**
     * Register fields
     */
    protected function register_fields() : void {
        try {
            $field_group = ( new Group( 'Sivun asetukset' ) )
                ->set_key( 'fg_page_combined_events_search' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'page_template', '==', \PageCombinedEventsSearch::TEMPLATE );

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
                        $this->get_page_fields( $field_group->get_key() ),
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
     * Get page fields
     *
     * @param string $key Field group key.
     *
     * @return Field\Tab
     * @throws Exception In case of invalid option.
     */
    protected function get_page_fields( string $key ) : Field\Tab {
        $strings = [
            'tab'         => 'Tapahtumat',
            'description' => [
                'title'        => 'Kuvausteksti',
                'instructions' => '',
            ],
        ];

        $tab = ( new Field\Tab( $strings['tab'] ) )
            ->set_placement( 'left' );

        $description_field = ( new TextEditor( $strings['description']['title'] ) )
            ->set_key( "{$key}_description" )
            ->set_name( 'description' )
            ->redipress_include_search()
            ->set_instructions( $strings['description']['instructions'] );

        $search_fields = ( new EventsFields( 'Tapahtumahaku', $key ) )
            ->remove_field( 'title' )
            ->remove_field( 'start' )
            ->remove_field( 'end' )
            ->remove_field( 'starts_today' )
            ->remove_field( 'area' )
            ->remove_field( 'tag' )
            ->remove_field( 'target' )
            ->remove_field( 'text' )
            ->remove_field( 'page_size' )
            ->remove_field( 'all_events_link' );

        $fields = [
            $description_field,
        ];
        $fields = array_merge( $fields, $search_fields->get_fields() );

        $tab->add_fields( $fields );

        return $tab;
    }
}
