<?php

if (! defined('WPINC')) die;

require_once 'abstract_tab.php';

class TCG_Images extends AbstractTCG {
    
    protected function set_defaults() {
        $this->defaults = [
            'amount'       => 5,
            'image_width'  => 800,
            'image_height' => 400,
        ];
    }
    
    protected function init_settings() {
        register_setting($this->ident, $this->ident, array($this, 'run'));
        add_settings_section($this->ident.'_1', __('Download Images', 'TestContentGenerator'), array($this, 'intro'), $this->ident);
        add_settings_field('tcg_amount', __('Number of Images', 'TestContentGenerator'), array($this, 'amount'), $this->ident, $this->ident.'_1');
        add_settings_field('tcg_image_dimensions', __('Image Dimensions', 'TestContentGenerator'), array($this, 'image_dimensions'), $this->ident, $this->ident.'_1');
    }   

    
    
    public function intro() {
        echo '<p class="description">'.__('Download example images from https://picsum.photos and save them to your Media Library.', 'TestContentGenerator').'</p>';
    }
    
    public function amount() {
        printf(
            '<select name="%s">%s</select>',
                $this->ident.'[amount]',
                $this->make_options([1, 5, 10, 20], $this->options['amount'])
        );
    }
    
    public function image_dimensions() {
        printf(__('%s pixels wide by %s pixels high.', 'TestContentGenerator'),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[image_width]', $this->options['image_width']),
               sprintf('<input type="text" class="small-text" name="%s" value="%d">', $this->ident.'[image_height]', $this->options['image_height'])
        );
    }
    
    
    
    protected function sanitise(array $input) {
        
        // add between 1-20 images at a time
        $amount = (isset($input['amount'])) ? max(1, min(20, (int) $input['amount'])) : 1;
        
        // image dimensions 100-3000
        $image_width = (isset($input['image_width'])) ? max(100, min(3000, (int) $input['image_width'])) : 300;
        $image_height = (isset($input['image_height'])) ? max(100, min(3000, (int) $input['image_height'])) : 200;
        
        // stick all our sanitised vars into an array
        $this->options = [
            'amount'       => $amount,
            'image_width'  => $image_width,
            'image_height' => $image_height,
        ];
        
    }

    
    
    protected function create(array $options, object|null $progress = null) {
        
        // get all possible authors (ie anyone who can upload files)
        $this->users = get_users(array('capability' => 'upload_files', 'fields' => 'ID'));
        
        
        $count = 0;
        for ($i=0; $i<$options['amount']; $i++) {
            if (is_object($progress)) $progress->tick(); // tick at the start of this create() because there's too long a pause otherwise
            if (! $this->save_image_from_picsum(width: $options['image_width'], height: $options['image_height'])) {
                break;
            }
            $count++;
            if ($count < $options['amount']) sleep(1); // don't download too much too quickly
        }
        if (is_object($progress)) $progress->finish();
        
        
        if ($count < $options['amount']) {
            $count = $options['amount'] - $count;
            $this->error(sprintf(
                _n(
                    'Failed to save %d image to the Media Library.',
                    'Failed to save %d images to the Media Library.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
            
        } else {
            $this->success(sprintf(
                _n(
                    'Saved %d image to the Media Library.',
                    'Saved %d images to the Media Library.',
                    $count, 'TestContentGenerator'
                ),
                number_format_i18n($count)
            ));
        }
    }
    
    
    
    private function save_image_from_picsum(int $width = 300, int $height = 200): bool {
        
        $base_url = 'https://picsum.photos/%d/%d';
        $url = sprintf($base_url, $width, $height);
        
        // request the image from the remote server
        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            $this->warning($response);
            return false;
        }
        
        // make sure it's something WP recognises and get the first extension we can
        $mime_type = wp_remote_retrieve_header($response, 'content-type');
        $extension = array_search($mime_type, wp_get_mime_types());
        if (! $extension) {
            $this->warning(sprintf(__('Unrecognised mime type "%s" returned by %s', 'TestContentGenerator'), $mime_type, $url));
            return false;
        }
        $extension = explode("|", $extension)[0];
        
        // construct a vaguely unique filename for it
        $picsum_id = wp_remote_retrieve_header($response, 'picsum-id');
        $filename = sprintf('picsum_%d_%dx%d.%s', $picsum_id, $width, $height, $extension);
        
        // and save it to our WP site
        $uploaded = wp_upload_bits($filename, null, wp_remote_retrieve_body($response));
        if (is_wp_error($uploaded)) {
            $this->warning($uploaded);
            return false;
        }
        
        // now we can add it as an attachment to the media library
        $attachment = array(
            'guid'           => $uploaded['url'],
            'file'           => $uploaded['file'],
            'post_mime_type' => $mime_type,
            'post_title'     => $filename,
            'post_content'   => __('Test Image Content', 'TestContentGenerator'),
            'post_status'    => 'inherit',
            'post_author'    => $this->users[array_rand($this->users)],
        );
        $attachment_id = wp_insert_attachment($attachment);

        // generate the metadata for the attachment, and update the database record for it
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        
        return true;
    }
    
}

