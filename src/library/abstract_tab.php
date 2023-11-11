<?php

if (! defined('WPINC')) die;

abstract class AbstractTCG {

    protected string $ident;
    
    abstract protected function set_defaults();
    abstract protected function init_settings();
    
    abstract protected function sanitise(array $input): array;
    abstract protected function create(array $options);
    
    
    public function __construct() {
        $this->ident = get_class($this);
        
        $this->set_defaults();
        // NOTE: can't pass [] or $defaults as get_option's 2nd param as it returns the initial wp_option field as a string and I'm typing the santise() param as an array
        $options = get_option($this->ident); if (! $options) $options = $this->defaults;
        $this->options = $this->sanitise($options);
        $this->init_settings();
    }    
    
    
    public function validate(array|null $input = []): array {
        // detect multiple sanitising passes, for an 11 year old and counting bug: https://core.trac.wordpress.org/ticket/21989
        static $pass_count = 0; $pass_count++; if ($pass_count > 1) return [];
        
        // sanitise our form input
        $options = $this->sanitise($input);
        
        // pass it to the generator
        $this->create($options);
        
        // make sure we save it
        update_option($this->ident, $options);
        
        // and return it to WP
        return $options;
    }

    
    protected function make_options(array $options, string|array $current, bool $simple = true, string|null $option_key = null): string {
        $output = '';
        foreach ($options as $k => $v) {
            if ($simple) {
                $key = $text = $v;
                $match = ($current == $key);
            } else {
                $key = $k;
                $text = (! $option_key or gettype($v) != 'array') ? $v : $v[$option_key];
                $match = ((gettype($current) == 'array' and in_array($key, $current)) or ($key == $current));
            }
            
            $output.= sprintf('<option value="%s"%s>%s</option>', $key, ($match ? ' selected="selected"' : ''), $text);
        }
        return $output;
    }
    
    
    // https://gist.github.com/angry-dan/e01b8712d6538510dd9c
    protected function natural_language_join(array $list): string {
        $last = array_pop($list);
        return ($list) ? sprintf('%s %s %s', implode(', ', $list), __('and', 'TestContentGenerator'), $last) : $last;
    }
    
    
}
