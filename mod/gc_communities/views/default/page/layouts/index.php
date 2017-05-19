<?php
    $community_url = get_input('community_url');
    $community_en = get_input('community_en');
    $community_fr = get_input('community_fr');
    $community_animator = get_input('community_animator');
    $community_tags = get_input('community_tags');
    
    $title = ( get_current_language() == "fr" ) ? $community_fr : $community_en;

    $widget_based = elgg_get_plugin_setting('widget_based', 'gc_communities', false);

    if( $widget_based ){
        $newsfeed_limit = elgg_get_plugin_setting('newsfeed_limit', 'gc_communities', 10);
        $wire_limit = elgg_get_plugin_setting('wire_limit', 'gc_communities', 10);

        $exact_match = elgg_extract('exact_match', $vars, true);
        $show_access = elgg_extract('show_access', $vars, true);
        
        $context = elgg_get_context();
        $widget_types = elgg_get_widget_types($context, true);
        $widgets = $vars['widgets'];
    }
?>

<h1><?php echo $title; ?></h1>

<div class="row">
    <div class="col-md-8">
        <?php
            $object_types = array('object');
            $object_subtypes = array('blog', 'groupforumtopic', 'event_calendar', 'file');

            $options = array(
                'types' => $object_types,
                'subtypes' => $object_subtypes,
                'limit' => $newsfeed_limit,
                'full_view' => false,
                'list_type_toggle' => false,
                'pagination' => true
            );

            if( $community_tags ){
                $options['metadata_name'] = 'tags';
                $options['metadata_values'] = array_map('trim', explode(',', $community_tags));
            }

            echo '<div class="panel panel-default elgg-module-widget">
            <header class="panel-heading"><div class="clearfix"><h3 class="elgg-widget-title pull-left">' . elgg_echo('gc_communities:community_newsfeed') . '</h3></div></header>
            <div class="panel-body clearfix">
            <div class="elgg-widget-content">' . elgg_list_entities_from_metadata($options) . '</div>
            </div>
            </div>';
        ?>
    </div>
    <div class="col-md-4">
        <?php
            if( $widget_based ): ?>

            <div class="widget-area">
                <?php
                    $communities = json_decode(elgg_get_plugin_setting('communities', 'gc_communities'));
                    foreach( $communities as $community ){
                        if( $community->community_url == get_input('community_url') ){
                            if( $community->community_animator == elgg_get_logged_in_user_entity()->username || elgg_is_admin_logged_in() ){
                                if( elgg_can_edit_widget_layout($context) ){
                                    echo elgg_view('page/layouts/widgets/add_button', array(
                                        'context' => $context
                                    ));

                                    echo elgg_view('page/layouts/widgets/add_panel', array(
                                        'widgets' => $widgets,
                                        'context' => $context,
                                        'exact_match' => $exact_match
                                    ));
                                }
                            }
                        }
                    }
                ?>
                <div class="elgg-layout-widgets">
                    <div class="elgg-widgets" id="elgg-widget-col-1">
                        <?php
                            if( count($widgets) > 0 ){
                                foreach( $widgets as $widget ){
                                    if( array_key_exists($widget->handler, $widget_types) && $widget instanceof ElggWidget ){
                                        echo elgg_view_entity($widget, array('show_access' => $show_access));
                                    }
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>

            <?php endif;

            if( $community_animator ){
                echo gc_communities_animator_block($community_animator);
            }

            if( strpos($community_tags, ',') !== false ){
                $community_tags = array_map('trim', explode(',', $community_tags));
            }

            elgg_set_context('search');
            
            $dbprefix = elgg_get_config('dbprefix');
            $typeid = get_subtype_id('object', 'thewire');
            $query = "SELECT wi.guid FROM {$dbprefix}objects_entity wi LEFT JOIN {$dbprefix}entities en ON en.guid = wi.guid WHERE en.type = 'object' AND en.subtype = {$typeid} ";

            if( is_array($community_tags) ){
                $all_tags = implode("|", $community_tags);
                $query .= " AND wi.description REGEXP '{$all_tags}'";
            } else {
                $query .= " AND wi.description LIKE '%{$community_tags}%'";
            }
                        
            $wire_ids = array();
            $wires = get_data($query);
            foreach($wires as $wire){
                $wire_ids[] = $wire->guid;
            }

            $options = array(
                'type' => 'object',
                'subtype' => 'thewire',
                'limit' => $wire_limit,
                'full_view' => false,
                'list_type_toggle' => false,
                'pagination' => true,
                'guids' => $wire_ids
            );

            echo '<div class="panel panel-default elgg-module-widget">
            <header class="panel-heading"><div class="clearfix"><h3 class="elgg-widget-title pull-left">' . elgg_echo('gc_communities:community_wire') . '</h3></div></header>
            <div class="panel-body clearfix">
            <div class="elgg-widget-content">' . elgg_list_entities($options) . '</div>
            </div>
            </div>';
        ?>
    </div>
</div>
