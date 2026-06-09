<?php
/**
 * zib_shop_setup - Shop setup & admin hooks stub
 * Handles post type registration, admin columns, and meta boxes
 */

class zib_shop_setup
{
    public $shop = null;

    public function __construct($shop_instance = null)
    {
        $this->shop = $shop_instance;

        if (!$this->shop || !$this->shop->s) {
            return;
        }

        // Register post types and taxonomies
        add_action('init', array($this->shop, 'register_post_type'));
        add_action('init', array($this->shop, 'register_post_statuses'));
        add_action('init', array($this->shop, 'add_rewrite_rule'));
        add_filter('query_vars', array($this->shop, 'query_vars'));

        add_action('saved_term', array($this->shop, 'initialization_term_meta'), 10, 4);
        add_action('save_post', array($this->shop, 'initialization_posts_meta'), 10, 3);

        add_action('template_redirect', array($this->shop, 'template_redirect'));
        add_action('pre_get_posts', array($this->shop, 'main_post_query'));
        add_filter('redirect_canonical', array($this->shop, 'redirect_canonical'));
        add_filter('post_type_link', array($this->shop, 'post_type_link'), 10, 2);

        add_action('pre_get_posts', array($this->shop, 'admin_post_query'), 999);
        add_action('admin_menu', array($this->shop, 'admin_menu_separator'));

        // Admin columns
        add_filter('manage_shop_product_posts_columns', array($this->shop, 'product_columns'));
        add_action('manage_shop_product_posts_custom_column', array($this->shop, 'product_custom_column'), 10, 2);

        add_filter('manage_users_columns', array($this->shop, 'users_columns'), 11);
        add_filter('manage_users_custom_column', array($this->shop, 'users_custom_column'), 10, 3);

        // Taxonomy columns
        add_filter('manage_edit-shop_cat_columns', array($this->shop, 'cat_columns'));
        add_filter('manage_shop_cat_custom_column', array($this->shop, 'cat_custom_column'), 10, 3);

        add_filter('manage_edit-shop_tag_columns', array($this->shop, 'tag_columns'));
        add_filter('manage_shop_tag_custom_column', array($this->shop, 'tag_custom_column'), 10, 3);

        add_filter('manage_edit-shop_discount_columns', array($this->shop, 'discount_columns'));
        add_filter('manage_shop_discount_custom_column', array($this->shop, 'discount_custom_column'), 10, 3);

        // Comment columns for product reviews
        if (is_admin()) {
            add_filter('manage_edit-comments_columns', array($this->shop, 'comments_columns'));
            add_action('manage_comments_custom_column', array($this->shop, 'comments_custom_column'), 10, 2);
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
