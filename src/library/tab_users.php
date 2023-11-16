<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Users extends AbstractTCG {
    
    protected function set_defaults() {
        $this->_all_user_roles = get_editable_roles();
        $this->defaults = [
            'amount'        => 1,
            'days_from'     => 60,
            'role_keys'     => array('editor', 'author', 'contributor', 'subscriber'),
            'random_locale' => false,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'run'));
        add_settings_section($this->ident.'_1', __('Generate Users', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_amount', __('Number of Users', 'TestContentGenerator'), array($this, 'amount'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_role_keys', __('Available Roles', 'TestContentGenerator'), array($this, 'role_keys'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_random_locale', __('Random Locale', 'TestContentGenerator'), array($this, 'random_locale'), $this->ident, $this->ident.'_1');
    }

    
    
    public function intro() {
        echo '<p class="">'.__('Add a number of example users to WordPress, registering on a random date as one of the available roles and locales.', 'TestContentGenerator').'</p>';
    }
    
    public function amount() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[amount]',
                $this->make_options([1, 5, 10, 20], $this->options['amount'])
        );
    }
    
    public function date_range() {
        printf(
            __('From %s days ago to now.', 'TestContentGenerator'),
                sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[days_from]', $this->options['days_from'])
        );
    }
    
    public function role_keys() {
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select>',
                $this->ident.'[role_keys][]',
                'min-width:10rem',
                min(max(1, sizeof($this->_all_user_roles)), 8), // resize the select to fit the content (up to a point)
                $this->make_options($this->_all_user_roles, $this->options['role_keys'], false, 'name')
        );
    }
    
    public function random_locale() {
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[random_locale]', $this->ident.'[random_locale]', $this->ident.'[random_locale]',
                (($this->options['random_locale']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                __('Either assign each user a random locale from the available site languages, or leave them as the site default.', 'TestContentGenerator')
        );
    }
    
    
    
    protected function sanitise(array $input) {
        
        // add between 1-20 users at a time
        $amount = (isset($input['amount'])) ? max(1, min(20, (int) $input['amount'])) : $this->defaults['amount'];
        
        // allow test users to be registered up to 10 years in the past, more than enough to test your site
        $days_from = (isset($input['days_from'])) ? max(0, min(3650, (int) $input['days_from'])) : $this->defaults['days_from'];
        
        // which roles can we create users as?
        $role_keys = $this->read_array($input, 'role_keys', array_keys($this->_all_user_roles));
        
        // not that it should make a difference for single language sites
        $random_locale = (isset($input['random_locale'])) ? (bool) $input['random_locale'] : $this->defaults['random_locale'];
        
        // stick all our sanitised vars into an array
        $this->options = [
            'amount'        => $amount,
            'days_from'     => $days_from,
            'role_keys'     => $role_keys,
            'random_locale' => $random_locale,
        ];
        
    }
    
    
    
    protected function create(array $options, object|null $progress = null) {
        
        require_once 'lipsum.php';
        
        $roles = $options['role_keys'];
        
        $locales = get_available_languages();
        
        $count = 0;
        for ($i = 0; $i < $options['amount']; $i++) {
            
            $first_name = Lipsum::word(capitalise: true, min_length: 4, max_length: 8);
            $last_name = Lipsum::word(capitalise: true, min_length: 7);

            $registration_date = rand(strtotime(sprintf('-%s days', $options['days_from'])), time());
            
            $userdata = array(
                'user_pass'            => '',
                'user_login'           => strtolower(mb_substr($first_name,0,1).$last_name),
                'user_email'           => strtolower(sprintf('%s.%s@not.a.real.email', $first_name, $last_name)),
                'display_name'         => sprintf('%s %s', $first_name, $last_name),
                'first_name'           => $first_name,
                'last_name'            => $last_name,
                'description'          => __('Not a real biography, because I am just an example user.', 'TestContentGenerator'),
                'rich_editing'         => 'false',
                'show_admin_bar_front' => 'false',
                'syntax_highlighting'  => 'false', // NOTE: these are string literals
                'comment_shortcuts'    => 'false',
                'use_ssl'              => true,    // NOTE: but this one is a real bool
                'user_registered'      => gmdate('Y-m-d H:i:s', $registration_date),
                'role'                 => $roles[array_rand($roles)],
                'locale'               => ($options['random_locale']) ? $locales[array_rand($locales)] : '',
                // 'meta_input'
            );
            
            wp_insert_user($userdata);
            
            $count++;
            
            if (is_object($progress)) $progress->tick();
        }
        if (is_object($progress)) $progress->finish();
        
        
        if ($count > 0) {
            $this->success(sprintf(
                _n(
                    '%d test user has been successfully added.',
                    '%d test users have been successfully added.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
        }
    }
    
}

