<?php

if (! defined('WPINC')) die;

abstract class AbstractTCG {

    protected string $ident;
    
    abstract protected function set_defaults();
    abstract protected function sanitise(array $input);
    abstract protected function init_settings();
    
    abstract protected function create(array $options, object|null $progress = null);
    
    
    public function __construct() {
        $this->ident = get_class($this);
        $this->cli = (defined('WP_CLI') and WP_CLI);
        
        $this->set_defaults();
        
        $this->sanitise(wp_parse_args(get_option($this->ident, $this->defaults), $this->defaults));
        
        $this->init_settings();
        
        add_filter("sanitize_option_{$this->ident}", array(get_called_class(), 'ignore_sanitize_filter'), 10, 3);
    }    
    
    // needed because if this callback is missing, WP wipes the already sanitised value when calling update_option() from the CLI
    // maybe related to https://wordpress.stackexchange.com/a/298847 ?
    public static function ignore_sanitize_filter($value, $option, $original_value) {
        return (defined('WP_CLI') and WP_CLI) ? $original_value : $value;
    }
    
    
    
    
    public function run(array|null $input = [], bool $save = false): array {
        // detect multiple sanitising passes, for an 11 year old and counting bug: https://core.trac.wordpress.org/ticket/21989
        static $pass_count = 0; $pass_count++; if ($pass_count > 1) return [];
        
        // sanitise our form/cli input
        $this->sanitise($input);
                
        // save it if desired
        if (! $this->cli or $save) update_option($this->ident, $this->options);
        
        // make a progress bar if cli
        $progress = ($this->cli and isset($this->options['amount'])) ? \WP_CLI\Utils\make_progress_bar(
            sprintf(__('%s: running', 'TestContentGenerator'), $this->ident), $this->options['amount'], 100
        ) : null;
        
        // pass it to the generator
        $this->create($this->options, $progress);
        
        // and return it to WP
        return $this->options;
    }
    
    
    
    
    
    // general cli commands across the tab classes
    
    public function show_options() {
        if ($this->cli) {
            WP_CLI::line(sprintf(
                '%s is using these options: %s',
                    get_called_class(),
                    print_r($this->options, 1)
            ));
        }
    }
    
    
    // general utility functions across the tab classes
    
    protected function success(string $message) {
        add_settings_error('TCG_Plugin', 'tcg_okay', $message, 'updated');
        if ($this->cli) WP_CLI::success($message);
    }
    protected function warning(WP_Error|string $message) {
        if (is_a($message, 'WP_Error')) $message = json_encode($message, JSON_PRETTY_PRINT);
        add_settings_error('TCG_Plugin', 'tcg_error', $message, 'error');
        if ($this->cli) WP_CLI::warning($message);
    }
    protected function error(WP_Error|string $message) {
        if (is_a($message, 'WP_Error')) $message = json_encode($message, JSON_PRETTY_PRINT);
        add_settings_error('TCG_Plugin', 'tcg_error', $message, 'error');
        if ($this->cli) WP_CLI::error($message);
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
