<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Custom extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'enable_cpt'      => false,
            'enable_cat'      => false,
            'enable_tag'      => false,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'validate'));
        add_settings_section($this->ident.'_1', __('Custom Settings', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_enable_cpt', __('Custom Post Type', 'TestContentGenerator'), array($this, 'enable_cpt'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_enable_cat', __('Custom Category', 'TestContentGenerator'), array($this, 'enable_cat'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_enable_tag', __('Custom Tags', 'TestContentGenerator'), array($this, 'enable_tag'), $this->ident, $this->ident.'_1');
    }
    
    
    
    public function intro() {
        echo '<p class="description">'.__('Enable an additional custom post type and associated taxonomies.', 'TestContentGenerator').'</p>';
    }
    
    public function enable_cpt() {
        $associated = get_object_taxonomies('tcg_custom_post_type');
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[enable_cpt]', $this->ident.'[enable_cpt]', $this->ident.'[enable_cpt]',
                (($this->options['enable_cpt']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                (sizeof($associated)) ? sprintf(
                    __('Associated with %s taxonomies.', 'TestContentGenerator'),
                    $this->natural_language_join($associated)
                ) : '',
        );
    }
    
    public function enable_cat() {
        $associated = get_taxonomy('tcg_custom_category');
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[enable_cat]', $this->ident.'[enable_cat]', $this->ident.'[enable_cat]',
                (($this->options['enable_cat']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                (is_object($associated)) ? sprintf(
                    __('Associated with %s post types.', 'TestContentGenerator'),
                    $this->natural_language_join($associated->object_type)
                ) : '',
        );
    }
    public function enable_tag() {
        $associated = get_taxonomy('tcg_custom_tag');
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[enable_tag]', $this->ident.'[enable_tag]', $this->ident.'[enable_tag]',
                (($this->options['enable_tag']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                (is_object($associated)) ? sprintf(
                    __('Associated with %s post types.', 'TestContentGenerator'),
                    $this->natural_language_join($associated->object_type)
                ) : '',
        );
    }
    
    
    
    
    protected function sanitise(array|null $input = []): array {
        $enable_cpt = (isset($input['enable_cpt'])) ? (bool) $input['enable_cpt'] : $this->defaults['enable_cpt'];
        $enable_cat = (isset($input['enable_cat'])) ? (bool) $input['enable_cat'] : $this->defaults['enable_cat'];
        $enable_tag = (isset($input['enable_tag'])) ? (bool) $input['enable_tag'] : $this->defaults['enable_tag'];
        
        // stick all our sanitised vars into an array
        $options = [
            'enable_cpt'      => $enable_cpt,
            'enable_cat'      => $enable_cat,
            'enable_tag'      => $enable_tag,
        ];
        
        return $options;
    }
    
    
    
    protected function create(array $options) {
        $regen = false;
        
        if ($options['enable_cpt']) {
            self::register_cpt();
            $regen = true;
        }
        if ($options['enable_cat']) {
            self::register_cat();
            $regen = true;
        }
        if ($options['enable_tag']) {
            self::register_tag();
            $regen = true;
        }
        
        // if we add the cpt/tax we regenerate the permalinks now
        if ($regen) {
            flush_rewrite_rules();
        }
    }
    
    

    
    public static function register_cpt() {
        register_post_type('tcg_custom_post_type', array(
            'labels'            => array(
                                        'name'          => __('TCG Custom Post Types', 'TestContentGenerator'),
                                        'singular_name' => __('TCG Custom Post Type', 'TestContentGenerator'),
                                    ),
            'public'            => true,
            'hierarchical'      => false,
            'supports'          => array('title', 'editor', 'thumbnail', 'comments', 'author'),
            'has_archive'       => true,
            'menu_position'     => 20,
            'show_in_admin_bar' => true,
            'show_in_rest'      => true,
            'menu_icon'         => 'dashicons-admin-users',
            'rewrite'           => array('with_front' => true),
        ));
        
        // add the usual post_tags/category to our cpt as well
        register_taxonomy_for_object_type('post_tag', 'tcg_custom_post_type');
        register_taxonomy_for_object_type('category', 'tcg_custom_post_type');
        
        add_action('pre_get_posts', array(get_called_class(), 'add_cpt_to_loop_and_feed'));
        add_action('widget_posts_args', array(get_called_class(), 'filter_recent_posts_widget_parameters'));
    }
    
    public static function register_cat() {
        register_taxonomy('tcg_custom_category', 'post', array(
            'labels' => [
                'name'          => __('TCG Custom Categories', 'TestContentGenerator'),
                'singular_name' => __('TCG Custom Category', 'TestContentGenerator'),
            ],
            'hierarchical'      => true,
            'query_var'         => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_ui'           => true,
        ));

        // tie these taxonomies to the posts / our cpt
        register_taxonomy_for_object_type('tcg_custom_category', 'tcg_custom_post_type');
        register_taxonomy_for_object_type('tcg_custom_category', 'post');
    }
    
    public static function register_tag() {
        register_taxonomy('tcg_custom_tag', 'post', array(
            'labels' => [
                'name'          => __('TCG Custom Tags', 'TestContentGenerator'),
                'singular_name' => __('TCG Custom Tag', 'TestContentGenerator'),
            ],
            'hierarchical'      => false,
            'query_var'         => true,
            'public'            => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'show_ui'           => true,
        ));

        // tie these taxonomies to the posts / our cpt
        register_taxonomy_for_object_type('tcg_custom_tag', 'tcg_custom_post_type');
        register_taxonomy_for_object_type('tcg_custom_tag', 'post');
    }
    
    
    
    /**** front end loop *******************************/
    
    public static function add_cpt_to_loop_and_feed($query) {
        if (! is_admin() and $query->is_main_query()) {
            $post_type = $query->get('post_type');
            if ($post_type == '') {
                $post_type = (! $query->is_page) ? ['post'] : ['page'];
            } else if (is_string($post_type)) {
                $post_type = [$post_type];
            }
            $post_type = array_merge($post_type, ['tcg_custom_post_type']);
            $query->set('post_type', $post_type);
        }
        return $query;
    }
    
    // proper widgets only because gutenberg still doesn't support simple filters
    // https://core.trac.wordpress.org/ticket/54580
    // https://github.com/WordPress/gutenberg/pull/38283
    //      - incredible, there's a filter for recent COMMENTS yet nothing for recent POSTS
    // https://github.com/WordPress/gutenberg/issues/33990
    public static function filter_recent_posts_widget_parameters($params) {
        if (! isset($params['post_type'])) {
            $params['post_type'] = ['post'];
        } else if (is_string($params['post_type'])) {
            $params['post_type'] = [$params['post_type']];
        }
        
        if (is_array($params['post_type'])) {
            $params['post_type'] = array_merge($params['post_type'], ['tcg_custom_post_type']);
        }
        return $params;
    }
    
    
}

