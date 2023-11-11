<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Users extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'number_of_users'   => 1,
            'registration_from' => 60,
            'available_roles'   => array('editor', 'author', 'contributor', 'subscriber'),
            'random_locale'     => false,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'validate'));
        add_settings_section($this->ident.'_1', __('Generate Users', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_number_of_users', __('Number of Users', 'TestContentGenerator'), array($this, 'number_of_users'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_available_roles', __('Available Roles', 'TestContentGenerator'), array($this, 'available_roles'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_random_locale', __('Random Locale', 'TestContentGenerator'), array($this, 'random_locale'), $this->ident, $this->ident.'_1');
    }

    
    
    public function intro() {
        echo '<p class="">'.__('Add a number of example users to WordPress, registering on a random date as one of the available roles and locales.', 'TestContentGenerator').'</p>';
    }
    
    public function number_of_users() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[number_of_users]',
                $this->make_options([1, 5, 10, 20], $this->options['number_of_users'])
        );
    }
    
    public function date_range() {
        printf(
            __('From %s days ago to now.', 'TestContentGenerator'),
                sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[registration_from]', $this->options['registration_from'])
        );
    }
    
    public function available_roles() {
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select>',
                $this->ident.'[available_roles][]',
                'min-width:10rem',
                min(max(1, sizeof($this->all_roles)), 8), // resize the select to fit the content (up to a point)
                $this->make_options($this->all_roles, $this->options['available_roles'], false, 'name')
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
    
    
    
    protected function sanitise(array $input): array {
        
        // add between 1-20 users at a time
        $number_of_users = (isset($input['number_of_users'])) ? max(1, min(20, (int) $input['number_of_users'])) : $this->defaults['number_of_users'];
        
        // allow test users to be registered up to 10 years in the past, more than enough to test your site
        $registration_from = (isset($input['registration_from'])) ? max(0, min(3650, (int) $input['registration_from'])) : $this->defaults['registration_from'];
        
        // a slightly cumbersome validation check of roles...
        $this->all_roles = get_editable_roles();
        $available_roles = (isset($input['available_roles']) and sizeof(array_intersect($input['available_roles'], array_keys($this->all_roles))) == sizeof($input['available_roles'])) ? $input['available_roles'] : $this->defaults['available_roles'];
        
        // not that it should make a difference for single language sites
        $random_locale = (isset($input['random_locale'])) ? (bool) $input['random_locale'] : $this->defaults['random_locale'];
        
        // stick all our sanitised vars into an array
        $options = [
            'number_of_users'   => $number_of_users,
            'registration_from' => $registration_from,
            'available_roles'   => $available_roles,
            'random_locale'     => $random_locale,
        ];
        
        return $options;
    }
    
    
    
    protected function create(array $options) {
        
        require_once 'lipsum.php';
        
        //$roles = array_filter(array_keys(get_editable_roles()), function($k) { return ($k != 'administrator'); });
        $roles = $options['available_roles'];
        
        $locales = get_available_languages();
        
        $count = 0;
        for ($i = 0; $i < $options['number_of_users']; $i++) {
            
            $first_name = Lipsum::word(capitalise: true, min_length: 4, max_length: 8);
            $last_name = Lipsum::word(capitalise: true, min_length: 7);

            $registration_date = rand(strtotime(sprintf('-%s days', $options['registration_from'])), time());
            
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
                'user_registered'      => date('Y-m-d H:i:s', $registration_date),
                'role'                 => $roles[array_rand($roles)],
                'locale'               => ($options['random_locale']) ? $locales[array_rand($locales)] : '',
                // 'meta_input'
            );
            
            wp_insert_user($userdata);
            
            $count++;
        }
        
        
        if ($count > 0) {
            add_settings_error('TCG_Plugin', 'tcg_okay',
                sprintf(
                    _n(
                        '%d test user has been successfully added.',
                        '%d test users have been successfully added.',
                        $count, 'TestContentGenerator'
                    ),
                    number_format_i18n($count)
                ), 'updated');
        }
    }
    
}

