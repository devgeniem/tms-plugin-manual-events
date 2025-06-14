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
 * Class PageCombinedEventsListGroup.
 *
 * @package TMS\Plugin\ManualEvents\Fields
 */
class PageCombinedEventsListGroup {

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
                ->set_key( 'fg_page_combined_events_list' );

            $rule_group = ( new RuleGroup() )
                ->add_rule( 'page_template', '==', \PageCombinedEventsList::TEMPLATE );

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
            'tab'                => 'Tapahtumat',
            'description'        => [
                'title'        => 'Kuvausteksti',
                'instructions' => '',
            ],
            'layout'             => [
                'title'        => 'Asettelu',
                'instructions' => '',
                'choices'      => [
                    'grid' => 'Ruudukko',
                    'list' => 'Lista',
                ],
            ],
            'disable_pagination' => [
                'title'        => 'Poista sivutus käytöstä',
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

        $search_fields = new EventsFields( 'Tapahtumahaku', $key );
        $search_fields->remove_field( 'title' );
        $search_fields->remove_field( 'start' );
        $search_fields->remove_field( 'end' );
        $search_fields->remove_field( 'starts_today' );
        $search_fields->remove_field( 'area' );
        $search_fields->remove_field( 'tag' );
        $search_fields->remove_field( 'target' );
        $search_fields->remove_field( 'text' );
        $search_fields->remove_field( 'page_size' );
        $search_fields->remove_field( 'all_events_link' );

        $layout_field = ( new Field\Radio( $strings['layout']['title'] ) )
            ->set_key( "{$key}_layout" )
            ->set_name( 'layout' )
            ->set_choices( $strings['layout']['choices'] )
            ->set_instructions( $strings['layout']['instructions'] );

        $disable_pagination_field = ( new Field\TrueFalse( $strings['disable_pagination']['title'] ) )
            ->set_key( "{$key}_disable_pagination" )
            ->set_name( 'disable_pagination' )
            ->use_ui()
            ->set_instructions( $strings['disable_pagination']['instructions'] );

        $fields = [
            $description_field,
            $layout_field,
            $disable_pagination_field,
        ];
        $fields = array_merge( $fields, $search_fields->get_fields() );

        $tab->add_fields( $fields );

        return $tab;
    }
}
