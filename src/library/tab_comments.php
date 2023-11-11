<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Comments extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'number_of_comments' => 1,
            'post_types'         => array('post'),
            'date_range_from'    => 90,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'validate'));
        add_settings_section($this->ident.'_1', __('Generate Comments', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_number_of_comments', __('Number of Comments', 'TestContentGenerator'), array($this, 'number_of_comments'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_post_types', __('In Post Types', 'TestContentGenerator'), array($this, 'post_types'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
    }
    
    
    
    public function intro() {
        echo '<p class="description">'.__('Adds comments from existing users to any existing posts.', 'TestContentGenerator').'</p>';
    }
    
    public function number_of_comments() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[number_of_comments]',
                $this->make_options([1, 5, 10, 20, 50, 100], $this->options['number_of_comments'])
        );
    }
    
    public function post_types() {
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select>',
                $this->ident.'[post_types][]',
                'min-width:10rem',
                min(max(1, sizeof($this->_post_types)), 8), // resize the select to fit the content (up to a point)
                $this->make_options($this->_post_types, $this->options['post_types'], false)
        );
    }
    
    public function date_range() {
        printf(
            __('From %s days ago to now.', 'TestContentGenerator'),
                sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[date_range_from]', $this->options['date_range_from'])
        );
    }
    
    
    
    protected function sanitise(array $input): array {
        
        // get public post types, filter out attachments *and pages* and then map down to type => display_name pairs
        $this->_post_types = array_filter(
            get_post_types(array('public' => true), 'objects'),
            function($pt) { return (! in_array($pt->name, ['attachment', 'page'])); }
        );
        array_walk($this->_post_types, function(&$item, $key) { $item = $item->labels->singular_name; });
        
        
        // create between 1-100 posts
        $number_of_comments = (isset($input['number_of_comments'])) ? max(1, min(100, (int) $input['number_of_comments'])) : $this->defaults['number_of_comments'];
        
        // post types to comment on
        $post_types = (isset($input['post_types']) and sizeof(array_intersect($input['post_types'], array_keys($this->_post_types))) == sizeof($input['post_types'])) ? $input['post_types'] : $this->defaults['post_types'];
        
        // date range can stretch from 10 years in the past to 10 years in the future, more than enough to test your site
        $date_range_from = (isset($input['date_range_from'])) ? max(0, min(3650, (int) $input['date_range_from'])) : $this->defaults['date_range_from'];
        
        // stick all our sanitised vars into an array
        $options = [
            'number_of_comments' => $number_of_comments,
            'post_types'         => $post_types,
            'date_range_from'    => $date_range_from,
        ];
        
        return $options;
    }
    
    
    
    
    protected function create(array $options) {
        
        require_once 'lipsum.php';
        
        // get all post ids available
        $posts = get_posts(array(
            'post_type'      => $options['post_types'],
            'post_status'    => 'publish', 
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        
        // get all users as typically every registered user can comment
        $users = get_users(array());
        
        $count = 0;
        
        // DEBUG: filter for wp_new_comment auto approval
        // add_filter('pre_comment_approved', function($approved, $commentdata) { return true; }, 10, 2);
        for ($i = 0; $i < $options['number_of_comments']; $i++) {
            
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
            
            $date = rand(strtotime(sprintf('-%s days', $options['date_range_from'])), time());
            
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
                add_settings_error('TCG_Plugin', 'tcg_error', __('Failed to insert comment. If this happens, uncomment the DEBUG lines to find out why.', 'TestContentGenerator'), 'error');
                return false;
            }
        
            $count++;
        }
        
        if ($count > 0) {
            add_settings_error('TCG_Plugin', 'tcg_okay',
                sprintf(
                    _n(
                        '%d test comment has been successfully generated.',
                        '%d test comments have been successfully generated.',
                        $count, 'TestContentGenerator'
                    ),
                    number_format_i18n($count)
                ), 'updated');
        }
        
    }
    
}

