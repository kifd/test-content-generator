<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Taxonomies extends AbstractTCG {
    
    protected function set_defaults() {
        $this->active_taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');
        
        $this->defaults = [];
        foreach ($this->active_taxonomies as $key => $tax) {
            $this->defaults['number_of_'.$key] = 0;
        }
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'validate'));
        add_settings_section($this->ident.'_1', __('Taxonomies', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        
        foreach ($this->active_taxonomies as $key => $tax) {
            add_settings_field('tcg_'.$key, sprintf(__('Number of %s to add', 'TestContentGenerator'), $tax->labels->name), array($this, 'number_of_terms'), $this->ident, $this->ident.'_1', array($key, $tax));
        }    
    }
    
    
    public function intro() {
        echo '<p class="description">'.__('Add random terms to any of your taxonomies.', 'TestContentGenerator').'</p>';
    }
    
    
    public function number_of_terms(array $args) {
        $key = $args[0];
        $tax = $args[1];
        
        // just to make it a bit nicer, a reminder of where the taxonomy is used
        $builtin = (($tax->_builtin) ? __('default WP', 'TestContentGenerator') : __('custom', 'TestContentGenerator'));
        $associated = array_map(function($item) { return get_post_type_object($item)->labels->name; }, $tax->object_type);
        
        printf(
            '<select name="%s">%s</select><p class="description">%s</p>',
                $this->ident.'[number_of_'.$key.']',
                $this->make_options([0, 1, 5, 10, 20], $this->options['number_of_'.$key]),
                sprintf(
                    __('"%s" is a %s taxonomy associated with %s.', 'TestContentGenerator'),
                    $tax->labels->singular_name,
                    $builtin, $this->natural_language_join($associated)
                )
        );
    }
    
    
    
    protected function sanitise(array|null $input = []): array {
        
        $options = [];
        foreach ($this->active_taxonomies as $key => $tax) {
            $opt = 'number_of_'.$key;
            // add between 0-20 extra terms at a time
            $options[$opt] = (isset($input[$opt])) ? max(0, min(20, (int) $input[$opt])) : $this->defaults[$opt];
        }
        
        return $options;
    }
    
    
    
    protected function create(array $options) {
        
        require_once 'lipsum.php';
        
        $failed = [];
        $count = 0;
        foreach ($options as $key => $amount) {
            
            if ($amount > 0) {
                
                $key = str_replace('number_of_', '', $key);
                $tax = $this->active_taxonomies[$key];
                
                $term_ids = [];
                for ($i = 1; $i <= $amount; $i++) {
                    
                    $description = sprintf(__('Custom %s %d', 'TestContentGenerator'), $tax->labels->singular_name, $i);
                    
                    if ($tax->hierarchical) {
                        $term = Lipsum::word(capitalise: true, min_length: 5);
                        // dividing by 2 gives us 3 top level parents, and 2 children per node thereafter
                        $parent = max(floor($i / 2) -1, 0);
                        $parent_id = ($parent > 0) ? $term_ids[$parent-1] : 0; // -1 because arrays start at 0
                        
                    } else {
                        $term = Lipsum::word(capitalise: true, min_length: 3, max_length: 6);
                        $parent_id = 0;
                    }
                    
                    $term_insert_id = wp_insert_term($term, $tax->name, array('description' => $description, 'parent' => $parent_id));
                    if (is_wp_error($term_insert_id)) {
                        $failed[] = $term;
                    } else {
                        $term_ids[] = $term_insert_id['term_id'];
                    }
                }
            
                $count+= sizeof($term_ids);
            }
        }
        
        if (sizeof($failed)) {
            add_settings_error('TCG_Plugin', 'tcg_error',
                sprintf(
                    _n(
                        'Couldn\'t insert %s, probably because it already existed in that taxonomy.',
                        'Couldn\'t insert %s, probably because they already existed in that taxonomy.',
                        sizeof($failed), 'TestContentGenerator'
                    ),
                    $this->natural_language_join($failed)
                ), 'error');
        }
        
        if ($count > 0) {
            add_settings_error('TCG_Plugin', 'tcg_okay',
                sprintf(
                    _n(
                        '%d example term has been successfully added.',
                        '%d example terms have been successfully added.',
                        $count, 'TestContentGenerator'
                    ),
                    number_format_i18n($count)
                ), 'updated');
        }
        
    }
    
    
}

