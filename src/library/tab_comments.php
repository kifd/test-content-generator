<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Comments extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'amount'         => 1,
            'post_type_keys' => array('post'),
            'days_from'      => 60,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'run'));
        add_settings_section($this->ident.'_1', __('Generate Comments', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_amount', __('Number of Comments', 'TestContentGenerator'), array($this, 'amount'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_post_type_keys', __('In Post Types', 'TestContentGenerator'), array($this, 'post_type_keys'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
    }
    
    
    
    public function intro() {
        echo '<p class="description">'.__('Adds comments from existing users to any existing posts.', 'TestContentGenerator').'</p>';
    }
    
    public function amount() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[amount]',
                $this->make_options([1, 5, 10, 20, 50, 100], $this->options['amount'])
        );
    }
    
    public function post_type_keys() {
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select>',
                $this->ident.'[post_type_keys][]',
                'min-width:10rem',
                min(max(1, sizeof($this->_post_type_keys)), 8), // resize the select to fit the content (up to a point)
                $this->make_options($this->_post_type_keys, $this->options['post_type_keys'], false)
        );
    }
    
    public function date_range() {
        printf(
            __('From %s days ago to now.', 'TestContentGenerator'),
                sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[days_from]', $this->options['days_from'])
        );
    }
    
    
    
    protected function sanitise(array $input) {
        
        // get public post types, filter out attachments *and pages* and then map down to type => display_name pairs
        $this->_post_type_keys = array_filter(
            get_post_types(array('public' => true), 'objects'),
            function($pt) { return (! in_array($pt->name, ['attachment', 'page'])); }
        );
        array_walk($this->_post_type_keys, function(&$item, $key) { $item = $item->labels->singular_name; });
        
        
        // create between 1-100 posts
        $amount = (isset($input['amount'])) ? max(1, min(100, (int) $input['amount'])) : $this->defaults['amount'];
        
        // post types to comment on
        // extra check needed because wp cli can't pass arrays - https://github.com/wp-cli/wp-cli/issues/4616
        if (isset($input['post_type_keys']) and gettype($input['post_type_keys']) == 'string') {
            $input['post_type_keys'] = json_decode($input['post_type_keys'], true);
        }
        $post_type_keys = (isset($input['post_type_keys']) and sizeof(array_intersect($input['post_type_keys'], array_keys($this->_post_type_keys))) == sizeof($input['post_type_keys'])) ? $input['post_type_keys'] : $this->defaults['post_type_keys'];
        
        // date range can stretch from 10 years in the past to 10 years in the future, more than enough to test your site
        $days_from = (isset($input['days_from'])) ? max(0, min(3650, (int) $input['days_from'])) : $this->defaults['days_from'];
        
        // stick all our sanitised vars into an array
        $this->options = [
            'amount'         => $amount,
            'post_type_keys' => $post_type_keys,
            'days_from'      => $days_from,
        ];
        
    }
    
    
    protected function create(array $options, object|null $progress = null) {
        
        require_once 'lipsum.php';
        
        // get all post ids available
        $posts = get_posts(array(
            'post_type'      => $options['post_type_keys'],
            'post_status'    => 'publish', 
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        
        // get all users as typically every registered user can comment
        $users = get_users(array());
        
        $count = 0;
        
        // DEBUG: filter for wp_new_comment auto approval
        // add_filter('pre_comment_approved', function($approved, $commentdata) { return true; }, 10, 2);
        for ($i = 0; $i < $options['amount']; $i++) {
            
            $post_id = $posts[array_rand($posts)];
            
            // 65% chance to try replying to an existing comment
            $parent_id = 0;
            if (rand(1,100) > 65) {
                $comments = get_comments(array(
                    'post_id' => $post_id,
                    'fields'  => 'ids',
                ));
                if (sizeof($comments)) {
                    $parent_id = $comments[array_rand($comments)];
                }
            }
            
            $user = $users[array_rand($users)];
            
            // TODO: worth bothering to check comment not older than post?
            $date = rand(strtotime(sprintf('-%s days', $options['days_from'])), time());
            
            $comment_data = array(
                'comment_author' => $user->display_name,
                'comment_author_email' => $user->user_email,
                'comment_author_url' => '',
                'comment_content' => Lipsum::paragraphs(min: 1, max: 2),
                'comment_date' => date('Y-m-d H:i:s', $date),
                'comment_post_ID' => $post_id,
                'comment_approved' => true,
                'comment_parent' => $parent_id,
                'user_id' => $user->ID,
            );
            
            // DEBUG: use this instead of directly calling wp_insert_comment if you need a wp_error returned instead of just false
            // $response = wp_new_comment($comment_data, true);
            // if (is_wp_error($response)) {
            //     add_settings_error('TCG_Plugin', 'tcg_error', $response->error, 'error');
            if (! wp_insert_comment($comment_data)) {
                $this->error(__('Failed to insert comment. If this happens, uncomment the DEBUG lines to find out why.', 'TestContentGenerator'));
                return false;
            }
        
            $count++;   
            if (is_object($progress)) $progress->tick();
        }
        if (is_object($progress)) $progress->finish();
        
        
        if ($count > 0) {
            $this->success(sprintf(
                _n(
                    '%d test comment has been successfully generated.',
                    '%d test comments have been successfully generated.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
        }
        
    }
    
}

