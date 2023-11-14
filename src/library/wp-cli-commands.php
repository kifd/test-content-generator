<?php
/**
* Generate a test site full of random users, posts, comments, tags and images.
* 
* ## EXAMPLES
*
*   # Add 5 users who will be either an editor or an author.
*   $ wp test users --amount=5 --role_keys='["editor", "author"]'
*   Success: 5 test users have been successfully added.
* 
*   # Add 5 images 800x400 in dimension to the Media Library.
*   $ wp test images --amount=5 --image_width=800 --image_height=400
*   Success: Saved 5 images to the Media Library.
* 
*   # Add 5 comments made within the last 45 days on "Posts" or "TCG Custom Post Types".
*   $ wp test comments --amount=5 --days_from=45 --post_type_keys='["post", "tcg_custom_post_type"]'
*   Success: 5 test comments have been successfully generated.
* 
* 
*/
class TCG_CLI extends WP_CLI_Command {
    
    public function __construct() {
        load_plugin_textdomain('TestContentGenerator', false, __DIR__.'/languages');
    }
    
    
    /**
    * Add test posts to the site.
    * 
    * All options are optional - unpassed options will use the previously set value, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--amount=<integer>]
    * : The number of Lorem Ipsum posts to create.
    * 
    * [--post_type_keys=<array>]
    * : Post type keys to pick from. Pass as a JSON encoded quoted string.
    * 
    * [--days_from=<integer>]
    * : Days in the past to pick the published date from.
    * 
    * [--days_to=<integer>]
    * : Days in the future to pick the published date from.
    * 
    * [--[no-]featured_image]
    * : Associate an existing image from the Media Library as a post's Featured Image. Use --no-featured_image to disable.
    * 
    * [--extra_html=<array>]
    * : Add a mix of lipsum HTML, such as blockquotes and lists, in addition to the usual paragraphs.
    * 
    * [--save]
    * : Save the current arguments (and unpassed options) for future runs.
    * This is the default behaviour of the web interface.
    * 
    * ## EXAMPLES
    *
    *   # Add 3 posts of mixed HTML (<p>, <ul> and <blockquote>).
    *   $ wp test posts --amount=2 --extra_html=ul,blockquote
    * 
    *   # Add 2 posts of just plain paragraphs.
    *   $ wp test posts --amount=2 --extra_html=
    * 
    *   # Add 5 posts published within the last 60 days in either "Posts" or "TCG Custom Post Types".
    *   $ wp test posts --amount=5 --days_from=60 --post_type_keys='["post", "tcg_custom_post_type"]'
    *   Success: 5 test posts have been successfully generated.
    * 
    */
    public function posts(array $args, array $named) {
        self::run('posts', $named);
    }
    
    
    
    
    /**
    * Add test users to the site.
    * 
    * All options are optional - unpassed options will use the previously set values, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--amount=<integer>]
    * : The number of users to create.
    * 
    * [--role_keys=<array>]
    * : User role keys to pick from. Pass as a JSON encoded quoted string.
    * 
    * [--days_from=<integer>]
    * : Days in the past to pick from.
    * 
    * [--random_locale]
    * : Assign a random locale from your site's available languages, or leave as the site default.
    * 
    * [--save]
    * : Save the current arguments (and unpassed options) for future runs.
    * This is the default behaviour of the web interface.
    * 
    * ## EXAMPLES
    *
    *   # Add 1 user who registered within the last 30 days.
    *   $ wp test users --amount=1 --days_from=30
    *   Success: 1 test user has been successfully added.
    * 
    *   # Add 5 users who will be either an editor or an author.
    *   $ wp test users --amount=5 --role_keys='["editor", "author"]'
    *   Success: 5 test users have been successfully added.
    * 
    */
    public function users(array $args, array $named) {
        self::run('users', $named);
    }
    
    
    
    
    /**
    * Add test comments to the site.
    * 
    * All options are optional - unpassed options will use the previously set value, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--amount=<integer>]
    * : The number of comments to create.
    * 
    * [--post_type_keys=<array>]
    * : Only add comments to posts with one of these keys. Pass as a JSON encoded quoted string.
    * 
    * [--days_from=<integer>]
    * : Days in the past to pick from when making the comment. Doesn't check the date of the post.
    * 
    * [--save]
    * : Save the current arguments (and unpassed options) for future runs.
    * This is the default behaviour of the web interface.
    * 
    * ## EXAMPLES
    *
    *   # Add 5 comments made within the last 45 days on "Posts" or "TCG Custom Post Types".
    *   $ wp test comments --amount=5 --days_from=45 --post_type_keys='["post", "tcg_custom_post_type"]'
    *   Success: 5 test comments have been successfully generated.
    * 
    */
    public function comments(array $args, array $named) {
        self::run('comments', $named);
    }
    
    
    
    
    /**
    * Add test images to the site.
    * 
    * All options are optional - unpassed options will use the previously set value, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--amount=<integer>]
    * : The number of images to download from https://picsum.photos/.
    * 
    * [--image_width=<integer>]
    * : Pixel width of the downloaded images.
    * 
    * [--image_height=<integer>]
    * : Pixel height of the downloaded images.
    * 
    * [--save]
    * : Save the current arguments (and unpassed options) for future runs.
    * This is the default behaviour of the web interface.
    * 
    * ## EXAMPLES
    *
    *   # Add 5 images 800x400 in dimension to the Media Library.
    *   $ wp test images --amount=5 --image_width=800 --image_height=400
    *   Success: Saved 5 images to the Media Library.
    * 
    */
    public function images(array $args, array $named) {
        self::run('images', $named);
    }
    
    
    
    
    
    /**
    * Add test taxonomy terms to the site.
    * 
    * All options are optional - unpassed options will use the previously set value, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--amount=<integer>]
    * : The number of terms to create.
    * 
    * [--tax_keys=<array>]
    * : Only add terms to taxonomies with one of these keys. Pass as a JSON encoded quoted string.
    * 
    * [--save]
    * : Save the current arguments (and unpassed options) for future runs.
    * This is the default behaviour of the web interface.
    * 
    * ## EXAMPLES
    *
    *   # Add 20 terms, mixed between the "Category" and "Post Tag" taxonomies.
    *   $ wp test taxonomies --amount=20 --tax_keys='["category", "post_tag"]'
    *   Success: 20 example terms have been successfully added.
    * 
    */
    public function taxonomies(array $args, array $named) {
        self::run('taxonomies', $named);
    }
    
    
    
    
    
    /**
    * Enable an example custom post type and taxonomies.
    * 
    * All options are optional - unpassed options will use the previously set value, or the default if invalid or never set.
    *
    * ## OPTIONS
    * 
    * [--[no-]enable_cpt]
    * : Enable the "tcg_custom_post_type" custom Post Type. Use --no-enable_cpt to disable.
    * Will use "post_tag", "category", "tcg_custom_category" and "tcg_custom_tag" taxonomies.
    * 
    * [--[no-]enable_category]
    * : Enable the "tcg_custom_category" custom hierarchical taxonomy. Use --no-enable_category to disable.
    * Will be used by "post" and "tcg_custom_post_type" post types.
    * 
    * [--[no-]enable_tag]
    * : Enable the "tcg_custom_tag" custom tag taxonomy. Use --no-enable_tag to disable.
    * Will be used by "post" and "tcg_custom_post_type" post types.
    * 
    * 
    * 
    * ## EXAMPLES
    *
    *   # 
    *   $ wp test custom
    
    * 
    */
    public function custom(array $args, array $named) {
        $named['save'] = true; // always save the custom command, because it's just a set of on/off toggles
        self::run('custom', $named);
    }
    
    
    
    
    
    /**
    * Show the currently set options for the command.
    * 
    * ## OPTIONS
    * 
    * <command>
    * : String name of the command to show.
    * 
    * 
    * ## EXAMPLES
    *
    *   # Display the current settings for the Users command.
    *   $ wp test show users
    *   Success: TCG_Users is using these options: Array ( ... )
    * 
    * @alias options
    */
    public function show(array $args, array $named) {
        $instance = self::load($args[0]);
        if (! $instance) {
            WP_CLI::error(sprintf(__('Invalid command - could not find %s class file.', 'TestContentGenerator'), $args[0]));
        }
        $instance->show_options();
    }
    
    

    
    
    private static function run(string $classname, array $named) {
        $instance = self::load($classname);
        $named = wp_parse_args($named, $instance->options);
        $instance->run($named, (isset($named['save']) and $named['save'] == 1));
        WP_CLI::debug(sprintf(__('%s used these options: %s', 'TestContentGenerator'), $classname, print_r($instance->options, 1)));
    }
    
    
    private static function load(string $classname): object|false {
        $filename = __DIR__.'/tab_'.$classname.'.php';
        if (! file_exists($filename)) return false;
        
        require_once $filename;
        $class = 'TCG_'.ucfirst($classname);
        return (new $class());
    }
    
    
}


WP_CLI::add_command('test', 'TCG_CLI');


