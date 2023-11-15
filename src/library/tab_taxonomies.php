<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Taxonomies extends AbstractTCG {
    
    
    protected function set_defaults() {
        $this->_active_taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');
        $this->defaults = [
            'amount'   => 1,
            'tax_keys' => array_column($this->_active_taxonomies, 'name'),
        ];
    }
    
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'run'));
        add_settings_section($this->ident.'_1', __('Taxonomies', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        
        add_settings_field('tcg_amount', __('Number of Terms', 'TestContentGenerator'), array($this, 'amount'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_tax_keys', __('In Taxonomies', 'TestContentGenerator'), array($this, 'tax_keys'), $this->ident, $this->ident.'_1');
    }
    
    
    public function intro() {
        echo '<p class="description">'.__('Add random terms to any of your taxonomies.', 'TestContentGenerator').'</p>';
    }
    
    
    public function amount() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[amount]',
                $this->make_options([1, 5, 10, 20, 50, 100], $this->options['amount'])
        );
    }
    
    public function tax_keys() {
        $options = '';
        foreach ($this->_active_taxonomies as $key => $v) {
            $options.= sprintf('<option value="%s"%s>%s</option>', $key, ((in_array($key, $this->options['tax_keys'])) ? ' selected="selected"' : ''), $v->labels->singular_name);
        }
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select>',
                $this->ident.'[tax_keys][]',
                'min-width:10rem',
                min(max(1, sizeof($this->_active_taxonomies)), 8), // resize the select to fit the content (up to a point)
                $options
        );
    }

    
    
    
    protected function sanitise(array|null $input = []) {
        
        // create between 1-100 terms
        $amount = (isset($input['amount'])) ? max(1, min(100, (int) $input['amount'])) : $this->defaults['amount'];
        
        // what taxonomies can we add terms to?
        $tax_keys = $this->read_array($input, 'tax_keys', array_keys($this->_active_taxonomies));
        
        
        // stick all our sanitised vars into an array
        $this->options = [
            'amount'   => $amount,
            'tax_keys' => $tax_keys,
        ];
        
    }
    
    
    
    protected function create(array $options, object|null $progress = null) {
        
        require_once 'lipsum.php';
        
        $failed = [];
        $count = 0;
        for ($i = 0; $i < $options['amount']; $i++) {
        
            $key = $options['tax_keys'][array_rand($options['tax_keys'])];
            $tax = $this->_active_taxonomies[$key];
            
            $description = sprintf(__('%s Term %d', 'TestContentGenerator'), $tax->labels->singular_name, $i+1);
            $parent_id = 0;
            
            if ($tax->hierarchical) {
                $term = Lipsum::word(capitalise: true, min_length: 5);
                if (rand(1,100) > 66) { // 33% chance to be a child category
                    $terms = get_terms(array('taxonomy' => $tax->name, 'hide_empty' => false, 'exclude' => 1, 'fields' => 'ids'));
                    $parent_id = (sizeof($terms)) ? $terms[array_rand($terms)] : 0;
                }
                
            } else {
                $term = Lipsum::word(capitalise: true, min_length: 3, max_length: 6);
            }
                
            $term_insert_id = wp_insert_term($term, $tax->name, array('description' => $description, 'parent' => $parent_id));
            if (is_wp_error($term_insert_id)) {
                $failed[] = $term;
            } else {
                $count++;
            }
            
            if (is_object($progress)) $progress->tick();
        }
        if (is_object($progress)) $progress->finish();
        
        
        
        if (sizeof($failed)) {
            $this->warning(sprintf(
                _n(
                    'Couldn\'t insert %s, probably because it already existed in that taxonomy.',
                    'Couldn\'t insert %s, probably because they already existed in that taxonomy.',
                    sizeof($failed), 'TestContentGenerator'
                ),
                $this->natural_language_join($failed)
            ));
        }
        if ($count > 0) {
            $this->success(sprintf(
                _n(
                    '%d example term has been successfully added.',
                    '%d example terms have been successfully added.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
        }
        
    }
    
    
}

