# Test Content Generator

Contributors:      keith_wp  
Author Link:       https://drakard.com/  
Requires at least: 4.0 or higher  
Tested up to:      6.4  
Requires PHP:      8.0  
Stable tag:        0.4  
License:           BSD-3-Clause  
License URI:       https://directory.fsf.org/wiki/License:BSD-3-Clause  
Tags:              lorem ipsum, test content, cli, test posts, test images, test users, test comments

Quickly generate a test site full of random users, posts, comments, tags and images.


## Description

From a fresh install of WordPress, use either [WP-CLI](https://wp-cli.org/) or the admin page in Tools->**Content Generator** to add test users, populate the Media Library with example images, an additional custom post type and custom taxonomies, generate as many test "_Lorem Ipsum_" posts as you want, and then add comments to those posts.

Each of those "_Lorem Ipsum_" posts will be randomly tagged and categorised, and can also be assigned one of the test images as its Featured Image, as well as a variety of HTML content in addition to the usual paragraphs.

This plugin is aimed at those of us who need to test plugins and themes on a regular basis, but it would serve for anyone to get a feel of how their site will look when it's full of content.


### Workflow

All the parts of this plugin are optional, and each can be repeated as much as you want, depending on which parts of your site you want to test.

However, no individual step will "backfill" preceeding ones automatically, so if you have an empty site and you want (eg.) your test _Posts_ to appear to have been written by different people, then you need to add the test _Users_ first.

ie.

* Enable **Custom** (Post Types & Categories & Tags) before adding terms from your **Taxonomies**,
* Add **Users** before downloading **Images** before generating **Posts** before adding **Comments**.


### WP CLI Integration

Using the plugin defaults, you can populate a test site with just the following commands:

1. `wp plugin install test-content-generator --activate`\
    Download and activate the plugin.
2. `wp test users --amount=20`\
    Add 20 random Editor/Author/Contributor/Subscribers users who have registered within the last 60 days.
3. `wp test taxonomies --amount=50`\
    Add 50 taxonomy terms split between Categories and Post Tags.
4. `wp test images --amount=10`\
    Add 10 images of size 800x400 uploaded by any Editor/Author.
5. `wp test posts --amount=40`\
    Add 40 Posts written within the last 60 days by any Editor/Author/Contributor, with a Featured Image and both categorised and tagged.
6. `wp test comments --amount=100`\
    Add 100 comments to your Posts written within the last 60 days by any of the registered users.
    

See `wp help test` for more details.


## Screenshots

1. Generating new test _Posts_.
2. An example of the output using the *Polite* theme.
3. Create any role of user in the _Users_ screen.
4. Adding _Images_ to your Media Library.
5. The _Custom Post Type_ and _Custom Taxonomies_ can be enabled separately.
6. Add test terms to all of your _Taxonomies_.
7. Fill out your frontend by adding _Comments_ to your content. 



## Changelog

### 0.4
* Added WP-CLI integration

### 0.3
* Tested against 6.4 and 6.3.2
* Added ability to fill media library from https://picsum.photos/
* Added featured images, user creation, comment creation
* Added a better settings page
* Initial public release

### 0.2
* Added the lipsum routines to generate posts and terms

### 0.1
* Original plugin - a test custom post type and taxonomies
