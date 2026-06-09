<?php
/**
 * BBS Admin class stub
 * Replaces encrypted admin management class
 */

class zib_bbs_admin
{
    public $bbs = null;

    public function __construct($bbs_instance = null)
    {
        $this->bbs = $bbs_instance;

        if ($this->bbs && is_admin()) {
            $this->setup();
        }
    }

    public function setup()
    {
        // Admin columns and filters for BBS post types
        add_filter('manage_forum_post_posts_columns', array($this, 'posts_columns'));
        add_action('manage_forum_post_posts_custom_column', array($this, 'posts_custom_column'), 10, 2);
        add_filter('manage_edit-forum_post_sortable_columns', array($this, 'sortable_columns'));

        add_filter('manage_plate_posts_columns', array($this, 'plate_columns'));
        add_action('manage_plate_posts_custom_column', array($this, 'plate_custom_column'), 10, 2);

        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'), 10, 2);

        add_action('bulk_edit_custom_box', array($this->bbs, 'bulk_edit_custom_box'), 10, 2);
        add_action('save_post', array($this->bbs, 'bulk_save_post'), 10, 3);
    }

    // Forum post columns
    public function posts_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['plate_id'] = '版块';
                $new_columns['bbs_type'] = '类型';
            }
        }
        $new_columns['views']    = '浏览';
        $new_columns['likes']    = '赞';
        $new_columns['topping']  = '置顶';
        return $new_columns;
    }

    public function posts_custom_column($column_name, $post_id)
    {
        switch ($column_name) {
            case 'plate_id':
                $plate_id = get_post_meta($post_id, 'plate_id', true);
                if ($plate_id) {
                    $plate = get_post($plate_id);
                    echo $plate ? esc_html($plate->post_title) : $plate_id;
                }
                break;
            case 'bbs_type':
                echo get_post_meta($post_id, 'bbs_type', true) ?: '-';
                break;
            case 'views':
                echo (int) get_post_meta($post_id, 'views', true);
                break;
            case 'likes':
                echo (int) get_post_meta($post_id, 'like', true);
                break;
            case 'topping':
                $topping = (int) get_post_meta($post_id, 'topping', true);
                echo $topping ? '<span style="color:green;">是</span>' : '-';
                break;
        }
    }

    public function sortable_columns($columns)
    {
        $columns['views']   = 'views';
        $columns['likes']   = 'like';
        $columns['topping'] = 'topping';
        return $columns;
    }

    // Plate columns
    public function plate_columns($columns)
    {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
        }
        $new_columns['posts_count'] = '帖子数';
        $new_columns['views']       = '浏览';
        $new_columns['follow_count'] = '关注';
        return $new_columns;
    }

    public function plate_custom_column($column_name, $post_id)
    {
        switch ($column_name) {
            case 'posts_count':
                echo (int) get_post_meta($post_id, 'posts_count', true);
                break;
            case 'views':
                echo (int) get_post_meta($post_id, 'views', true);
                break;
            case 'follow_count':
                echo (int) get_post_meta($post_id, 'follow_count', true);
                break;
        }
    }

    // Meta boxes
    public function add_meta_boxes()
    {
        add_meta_box('bbs_post_settings', '帖子设置', array($this, 'render_post_meta_box'), 'forum_post', 'side', 'high');
        add_meta_box('plate_settings', '版块设置', array($this, 'render_plate_meta_box'), 'plate', 'side', 'high');
    }

    public function render_post_meta_box($post)
    {
        wp_nonce_field('bbs_meta_box_nonce', 'bbs_meta_nonce');
        $plate_id = get_post_meta($post->ID, 'plate_id', true);
        $topping  = get_post_meta($post->ID, 'topping', true);
        $bbs_type = get_post_meta($post->ID, 'bbs_type', true);
        echo '<p><label>版块ID: </label><input type="number" name="plate_id" value="' . esc_attr($plate_id) . '" /></p>';
        echo '<p><label>置顶: </label><input type="number" name="topping" value="' . esc_attr($topping) . '" min="0" /></p>';
        echo '<p><label>类型: </label><input type="text" name="bbs_type" value="' . esc_attr($bbs_type) . '" /></p>';
    }

    public function render_plate_meta_box($post)
    {
        wp_nonce_field('bbs_meta_box_nonce', 'bbs_meta_nonce');
        $plate_type = get_post_meta($post->ID, 'plate_type', true);
        echo '<p><label>版块类型: </label><input type="text" name="plate_type" value="' . esc_attr($plate_type) . '" /></p>';
    }

    public function save_meta_boxes($post_id, $post)
    {
        if (!isset($_POST['bbs_meta_nonce']) || !wp_verify_nonce($_POST['bbs_meta_nonce'], 'bbs_meta_box_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if ($post->post_type === 'forum_post') {
            $fields = array('plate_id', 'topping', 'bbs_type');
            foreach ($fields as $field) {
                if (isset($_POST[$field])) {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }

        if ($post->post_type === 'plate') {
            if (isset($_POST['plate_type'])) {
                update_post_meta($post_id, 'plate_type', sanitize_text_field($_POST['plate_type']));
            }
        }
    }

    // Fallback for any missing method calls
    public function __call($name, $arguments)
    {
        return true;
    }

    public static function __callStatic($name, $arguments)
    {
        return true;
    }
}
