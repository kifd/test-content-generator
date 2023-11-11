<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Posts extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'number_of_posts' => 1,
            'post_types'      => array('post'),
            'date_range_from' => 90,
            'date_range_to'   => 2,
            'featured_image'  => false,
            'html_content'    => false,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'validate'));
        add_settings_section($this->ident.'_1', __('Generate Posts', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_number_of_posts', __('Number of Posts', 'TestContentGenerator'), array($this, 'number_of_posts'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_post_types', __('In Post Types', 'TestContentGenerator'), array($this, 'post_types'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_featured_image', __('Featured Images', 'TestContentGenerator'), array($this, 'featured_image'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_html_content', __('HTML Content', 'TestContentGenerator'), array($this, 'html_content'), $this->ident, $this->ident.'_1');
    }   
    
    
    
    
    public function intro() {
        echo '<p class="description">'.__('Create test "Lorem Ipsum" posts across a period of time, each randomly tagged and categorised, with featured images and by different users.', 'TestContentGenerator').'</p>';
    }
    
    public function number_of_posts() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[number_of_posts]',
                $this->make_options([1, 5, 10, 20, 50, 100], $this->options['number_of_posts'])
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
            __('From %s days ago to %s days in the future.', 'TestContentGenerator'),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[date_range_from]', $this->options['date_range_from']),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[date_range_to]', $this->options['date_range_to'])
        );
    }
    
    
    public function featured_image() {
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[featured_image]', $this->ident.'[featured_image]', $this->ident.'[featured_image]',
                (($this->options['featured_image']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                __('Will assign a featured image to generated posts, assuming you\'ve already populated the Media Library.', 'TestContentGenerator')
        );
    }
    
    public function html_content() {
        printf(
            '<label for="%s"><input type="checkbox" value="1" name="%s" id="%s"%s>%s</label><p class="description">%s</p>',
                $this->ident.'[html_content]', $this->ident.'[html_content]', $this->ident.'[html_content]',
                (($this->options['html_content']) ? ' checked="checked"' : ''),
                __('Enabled', 'TestContentGenerator'),
                __('Post content can either be simple paragraphs, or a fuller mix of HTML elements including lists and blockquotes.', 'TestContentGenerator')
        );
    }
    
    
    
        
    protected function sanitise(array $input): array {
        
        // get public post types, filter out attachments and then map down to type => display_name pairs
        $this->_post_types = array_filter(
            get_post_types(array('public' => true), 'objects'),
            function($pt) { return (! in_array($pt->name, ['attachment'])); }
        );
        array_walk($this->_post_types, function(&$item, $key) { $item = $item->labels->singular_name; });
        
        
        // create between 1-100 posts
        $number_of_posts = (isset($input['number_of_posts'])) ? max(1, min(100, (int) $input['number_of_posts'])) : $this->defaults['number_of_posts'];
        
        // a slightly cumbersome validation check of post types...
        $post_types = (isset($input['post_types']) and sizeof(array_intersect($input['post_types'], array_keys($this->_post_types))) == sizeof($input['post_types'])) ? $input['post_types'] : $this->defaults['post_types'];
        
        // date range can stretch from 10 years in the past to 10 years in the future, more than enough to test your site
        $date_range_from = (isset($input['date_range_from'])) ? max(0, min(3650, (int) $input['date_range_from'])) : $this->defaults['date_range_from'];
        $date_range_to = (isset($input['date_range_to'])) ? max(0, min(3650, (int) $input['date_range_to'])) : $this->defaults['date_range_to'];
        
        // include a pretty picture if we can
        $featured_image = (isset($input['featured_image'])) ? (bool) $input['featured_image'] : $this->defaults['featured_image'];
        
        // are we making some mix of HTML instead of simple Lorem?
        $html_content = (isset($input['html_content'])) ? (bool) $input['html_content'] : $this->defaults['html_content'];
        
        
        // stick all our sanitised vars into an array
        $options = [
            'number_of_posts' => $number_of_posts,
            'post_types'      => $post_types,
            'date_range_from' => $date_range_from,
            'date_range_to'   => $date_range_to,
            'featured_image'  => $featured_image,
            'html_content'    => $html_content,
        ];
        
        return $options;
    }
    
    
    
    protected function create(array $options) {
        
        require_once 'lipsum.php';
        
        // TODO: add options for these like normal
        $options += [
            'title_min_words' => 3,
            'title_max_words' => 7,
            'body_min_paragraphs' => 4,
            'body_max_paragraphs' => 9,
            //'max_default_categories' => 1,
            'max_custom_categories' => 3,
            'max_tags' => 8,
        ];
        
        // precalculate all the terms we can randomly assign to multiple types of posts
        $term_bins = $this->get_taxonomies_by_type($options['post_types']);
        
        // get all image ids available (or set to empty if we don't want Featured Images)
        $images = ($options['featured_image']) ? get_posts(array(
            'post_type'      => 'attachment', 
            'post_mime_type' => 'image', 
            'post_status'    => 'inherit', 
            'posts_per_page' => -1,
            'fields'         => 'ids',
        )) : [];
        
        
        // get all possible authors (ie anyone who can create a post)
        // NOTE: no checking on custom edit_X capabilities, so this won't always be a true reflection of fancier custom post types
        $users = get_users(array('capability' => 'edit_posts', 'fields' => 'ID'));
        
        
        $count = 0;
        for ($i = 0; $i < $options['number_of_posts']; $i++) {
            
            $post_type = $options['post_types'][array_rand($options['post_types'])];
            
            $date = rand(strtotime(sprintf('-%s days', $options['date_range_from'])), strtotime(sprintf('+%s days', $options['date_range_to'])));
            
            $new_post = array(
                'post_title' => Lipsum::title(min: $options['title_min_words'], max: $options['title_max_words']),
                'post_content' => $this->make_post_content($options),
                'post_status' => 'publish',
                'post_date' => date('Y-m-d H:i:s', $date),
                'post_author' => $users[array_rand($users)],
                'post_type' => $post_type,
            );
            $post_id = wp_insert_post($new_post);
            
            // add a featured image to the post
            if (sizeof($images)) {
                $image_id = $images[rand(0, sizeof($images)-1)];
                set_post_thumbnail($post_id, $image_id);
            }
            
            $wp_category_terms = $term_bins[$post_type]['wp_category_terms'];
            $category_terms = $term_bins[$post_type]['category_terms'];
            $tag_terms = $term_bins[$post_type]['tag_terms'];
            
            // set a normal WP category if we can (really just to overwrite "Uncategorized" if we can)
            if (sizeof($wp_category_terms)) {
                $term = $wp_category_terms[array_rand($wp_category_terms)];
                wp_set_object_terms($post_id, $term->term_id, 'category', false);
            }
            
            // and a few custom categories
            if (sizeof($category_terms)) {
                shuffle($category_terms);
                $picked = array_slice($category_terms, 0, rand(1, min(sizeof($category_terms), $options['max_custom_categories'])));
                // can't simply use eg $term_ids = array_column($picked, 'term_id');
                // because we may be picking from multiple taxonomies at once and wp_set_object_terms doesn't accept an array of taxonomies, only an array of term_ids
                foreach ($picked as $term) {
                    wp_set_object_terms($post_id, $term->term_id, $term->taxonomy, true);
                }
            }
            // and then a bunch more of tags
            if (sizeof($tag_terms)) {
                shuffle($tag_terms);
                $picked = array_slice($tag_terms, 0, rand(1, min(sizeof($tag_terms), $options['max_tags'])));
                // again, can't simply use an array of term_ids for the same reason as above
                foreach ($picked as $term) {
                    wp_set_object_terms($post_id, $term->term_id, $term->taxonomy, true);
                }
            }
            
            $count++;
        }
        
        
        if ($count > 0) {
            add_settings_error('TCG_Plugin', 'tcg_okay',
                sprintf(
                    _n(
                        '%d test post has been successfully generated.',
                        '%d test posts have been successfully generated.',
                        $count, 'TestContentGenerator'
                    ),
                    number_format_i18n($count)
                ), 'updated');
        }
    }
    
    
    
    
    private function get_taxonomies_by_type(array $post_types): array {
        $result = [];
        
        // get the visible taxonomies first
        $taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');
        foreach ($post_types as $type) {
            
            // now we filter them to match just our chosen post type
            $_type_taxonomies = array_filter($taxonomies, function($tax) use ($type) {
                return (in_array($type, $tax->object_type));
            });
            
            // I usually pick one or two categories for a post and a bunch more of tags, so split the types up here
            $_type_categories = array_filter($_type_taxonomies, function($tax) { return ($tax->hierarchical); });
            $_type_tags = array_diff_key($_type_taxonomies, $_type_categories);
            
            // get all the terms for each of those taxonomies (excluding "Uncategorized" which is usually going to be id 1)
            $_category_terms = get_terms(['taxonomy' => array_column($_type_categories, 'name'), 'hide_empty' => false, 'exclude' => 1]);
            
            // see if we use the normal WP category taxonomy for this post type
            $_wp_category_terms = array_filter($_category_terms, function($term) { return ($term->taxonomy == 'category'); });
            $_category_terms = array_diff_key($_category_terms, $_wp_category_terms);
            
            // and all the types of tags can just be lumped together
            $_tag_terms = get_terms(['taxonomy' => array_column($_type_tags, 'name'), 'hide_empty' => false]);
            
            $result[$type] = [
                'wp_category_terms' => $_wp_category_terms,
                'category_terms' => $_category_terms,
                'tag_terms' => $_tag_terms,
            ];
            
        }
        
        return $result;
    }
    
    
    
    
    private function make_post_content(array $options): string {
        $result = '';
        
        if (! $options['html_content']) {
            $result.= Lipsum::paragraphs(min: $options['body_min_paragraphs'], max: $options['body_max_paragraphs']);
        
        } else {
            
            $done_block_quote = $done_lists = $done_anything = false;
            
            $paragraphs = [];
            for ($i = 1; $i < $options['body_max_paragraphs']; $i++) {
                $chance = rand(1,100);
                
                switch (true) {
                    
                    // always start with a short paragraph
                    case (! count($paragraphs)):
                        $paragraphs[]= Lipsum::paragraph(min_sentences: 1, max_sentences: 2);
                        break;
                        
                    case ($chance > 90 and (! $done_anything and ! $done_block_quote)):
                        $paragraphs[]= Lipsum::blockquote();
                        $done_block_quote = true;
                        $done_anything = true;
                        break;
                        
                    case ($chance > 70 and (! $done_anything and ! $done_lists)):
                        $paragraphs[]= Lipsum::ulol_list(max_items: 6, type: (rand(1,100) > 50) ? 'ol' : 'ul');
                        $done_lists = true;
                        $done_anything = true;
                        break;
                        
                    default:
                        $emphasize = (rand(1,100) > 50) ? true : false; // allow the Lipsum to be able to emphasize some words in this paragraph
                        $paragraphs[]= Lipsum::paragraph(min_sentences: 2, max_sentences: 4, bold: $emphasize);
                        $done_anything = false;
                    
                }
                
                if ($chance > 95 and count($paragraphs) < $options['body_max_paragraphs']) {
                    $paragraphs[]= '<h2>'.Lipsum::sentence(min_words: 2, max_words: 4, capslock: true, punctuation: false).'</h2>';
                }
                
            }
            $paragraphs[]= Lipsum::paragraphs(max: 1);
            
            $result = implode("\n\n", $paragraphs);
            
        }
    
    
        return $result;
    }
    
    
    
    
    
}
