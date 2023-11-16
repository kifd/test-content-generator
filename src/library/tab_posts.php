<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Posts extends AbstractTCG {
    
    protected function set_defaults() {
        // get public post types, filter out attachments and then map down to type => display_name pairs
        $this->_post_type_keys = array_filter(
            get_post_types(array('public' => true), 'objects'),
            function($pt) { return (! in_array($pt->name, ['attachment'])); }
        );
        array_walk($this->_post_type_keys, function(&$item, $key) { $item = $item->labels->singular_name; });
        
        // the whole list of HTML tags that have some supporting function to make them
        $tags = array('b','i','ul','ol','blockquote','h1','h2','h3','h4','h5','h6');
        $this->_supported_tags = array_combine($tags, $tags);
        
        $this->defaults = [
            'amount'         => 1,
            'post_type_keys' => array('post'),
            'days_from'      => 60,
            'days_to'        => 0,
            'featured_image' => true,
            'extra_html'     => array('b','i','ul','ol','blockquote','h2','h3'),
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'run'));
        add_settings_section($this->ident.'_1', __('Generate Posts', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_amount', __('Number of Posts', 'TestContentGenerator'), array($this, 'amount'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_post_type_keys', __('In Post Types', 'TestContentGenerator'), array($this, 'post_type_keys'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_date_range', __('Date Range', 'TestContentGenerator'), array($this, 'date_range'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_featured_image', __('Featured Images', 'TestContentGenerator'), array($this, 'featured_image'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_extra_html', __('HTML Content', 'TestContentGenerator'), array($this, 'extra_html'), $this->ident, $this->ident.'_1');
    }   
    
    
    
    
    public function intro() {
        echo '<p class="description">'.__('Create test "Lorem Ipsum" posts across a period of time, each randomly tagged and categorised, with featured images and by different users.', 'TestContentGenerator').'</p>';
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
            __('From %s days ago to %s days in the future.', 'TestContentGenerator'),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[days_from]', $this->options['days_from']),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[days_to]', $this->options['days_to'])
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
    
    public function extra_html() {
        printf(
            '<select name="%s" multiple="multiple" style="%s" size="%d">%s</select><p class="description">%s</p>',
                $this->ident.'[extra_html][]',
                'min-width:10rem',
                min(max(1, sizeof($this->_supported_tags)), 8), // resize the select to fit the content (up to a point)
                $this->make_options($this->_supported_tags, $this->options['extra_html'], false),
                __('Post content can either be simple paragraphs, or a fuller mix of HTML elements including lists and blockquotes.', 'TestContentGenerator')
        );
    }
    
    
    
        
    protected function sanitise(array $input) {
        
        // create between 1-100 posts
        $amount = (isset($input['amount'])) ? max(1, min(100, (int) $input['amount'])) : $this->defaults['amount'];
        
        // what post types are we making?
        $post_type_keys = $this->read_array($input, 'post_type_keys', array_keys($this->_post_type_keys));
        
        // date range can stretch from 10 years in the past to 10 years in the future, more than enough to test your site
        $days_from = (isset($input['days_from'])) ? max(0, min(3650, (int) $input['days_from'])) : $this->defaults['days_from'];
        $days_to = (isset($input['days_to'])) ? max(0, min(3650, (int) $input['days_to'])) : $this->defaults['days_to'];
        
        // include a pretty picture if we can
        $featured_image = (bool) (isset($input['featured_image']) and $input['featured_image']);
        
        // are we making some mix of HTML instead of simple Lorem?
        $extra_html = $this->read_array($input, 'extra_html', $this->_supported_tags);
        
        
        // stick all our sanitised vars into an array
        $this->options = [
            'amount'         => $amount,
            'post_type_keys' => $post_type_keys,
            'days_from'      => $days_from,
            'days_to'        => $days_to,
            'featured_image' => $featured_image,
            'extra_html'     => $extra_html,
        ];
        
    }
    
    
    
    protected function create(array $options, object|null $progress = null) {
        
        require_once 'lipsum.php';
        
        // TODO: add options for these like normal - maybe in a separate general settings, esp if can switch lipsum to a different text?
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
        $term_bins = $this->get_taxonomies_by_type($options['post_type_keys']);
        
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
        for ($i = 0; $i < $options['amount']; $i++) {
            
            $post_type = $options['post_type_keys'][array_rand($options['post_type_keys'])];
            
            $date = rand(strtotime(sprintf('-%s days', $options['days_from'])), strtotime(sprintf('+%s days', $options['days_to'])));
            
            $new_post = array(
                'post_title' => Lipsum::title(min: $options['title_min_words'], max: $options['title_max_words']),
                'post_content' => $this->make_post_content($options),
                'post_status' => 'publish',
                'post_date' => gmdate('Y-m-d H:i:s', $date),
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
            if (is_object($progress)) $progress->tick();
        }
        if (is_object($progress)) $progress->finish();
        
        
        if ($count > 0) {
            $this->success(sprintf(
                _n(
                    '%d test post has been successfully generated.',
                    '%d test posts have been successfully generated.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
        }
    }
    
    
    
    
    private function get_taxonomies_by_type(array $post_type_keys): array {
        $result = [];
        
        // get the visible taxonomies first
        $taxonomies = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');
        foreach ($post_type_keys as $type) {
            
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
        
        if (empty($options['extra_html'])) {
            $result.= Lipsum::paragraphs(min: $options['body_min_paragraphs'], max: $options['body_max_paragraphs']);
        
        } else {
            
            // simplistic attempt at making a "realistic" looking fake post; how realistic really does depend on what your specific content would look like...
            
            $inline = array_filter($options['extra_html'], function ($value) { return (in_array($value, ['b', 'i'])); });
            $block = array_diff($options['extra_html'], $inline); shuffle($block);
            
            $paragraphs = [];
            $done_block_level = false;
            
            // always start with a short paragraph
            $paragraphs[]= Lipsum::paragraph(min_sentences: 2, max_sentences: 3);
            
            // loop over from 1 to penultimate
            for ($i = 1; $i < $options['body_max_paragraphs']-1; $i++) {
                
                if (! $done_block_level and ! empty($block) and rand(1,100) > 50) {
                    
                    $block_element = array_pop($block);
                    
                    $paragraphs[] = match ($block_element) {
                        'blockquote' =>
                            Lipsum::blockquote(),
                        'ul', 'ol' =>
                            Lipsum::ulol_list(max_items: 8, type: $block_element),
                        'h1', 'h2', 'h3', 'h4', 'h5', 'h6' => 
                            sprintf(
                                '<%s>%s</%s>', $block_element, Lipsum::sentence(min_words: 2, max_words: 5, capslock: true, punctuation: false), $block_element
                            ),
                        default =>
                            sprintf(__('Unsupported block element!', 'TestContentGenerator'))
                    };
                    
                    $done_block_level = true;
                    
                } else {
                    $bold = (in_array('b', $inline) and rand(1,100) > 50);
                    $italic = (in_array('i', $inline) and rand(1,100) > 50);
                    $paragraphs[]= Lipsum::paragraph(min_sentences: 2, max_sentences: 4, bold: $bold, italic: $italic);
                    $done_block_level = false;                    
                }
            }
            
            // and always end with another paragraph
            $paragraphs[]= Lipsum::paragraphs(max: 1);
            
            $result = implode("\n\n", $paragraphs);
        }
    
    
        return $result;
    }
    
    
    
    
    
}
