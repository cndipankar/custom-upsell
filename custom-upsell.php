<?php
/**
 * Plugin Name: Custom Upsell Plugin
 * Version: 2.0.0
 * Description: CustomUpsell plugin
 * Author: Jhantu
 * Modified By: Dipankar - 7th July, 2022
 */

if ( ! class_exists( 'CustomUpsellPlugin' ) ) {
    define( 'CustomUpsell_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
    define('CustomUpsell_PLUGIN_URL',plugin_dir_url(__FILE__));
    class CustomUpsellPlugin {
        /**
         * Constructor
         */

        public function __construct() {  
            $this->setup_actions(); 
            $this->init_hooks();

            $admin = new CustomUpsell_Admin();
        }
        
        /**
         * Setting up Hooks
         */
        public function setup_actions() {
            //Main plugin hooks
            register_activation_hook( __FILE__, array( 'CustomUpsellPlugin', 'activate' ) );
            register_deactivation_hook( __FILE__, array( 'CustomUpsellPlugin', 'deactivate' ) );
        }
        
        /**
         * Activate callback
         */
        public static function activate(){
            require_once(CustomUpsell_PLUGIN_PATH. 'includes/class-activate.php');
            $activate = new CustomUpsell_Activate();
        }
        
        /**
         * Deactivate callback
         */
        public static function deactivate() {

            /*global $wpdb;
            $table_name = $wpdb->prefix . "custom_upsell_processing_offer";
            $sql = "DROP TABLE IF EXISTS $table_name";
            $wpdb->query($sql);

            $table_name_offer = $wpdb->prefix . "custom_upsell_processing_offer";
            $del_offer = "DROP TABLE IF EXISTS $table_name_offer";
            $wpdb->query($del_offer);*/
        }
        public function init_hooks() {
            require_once(CustomUpsell_PLUGIN_PATH. 'functions/functions.php');
            require_once(CustomUpsell_PLUGIN_PATH. 'admin/class_admin.php');
        }

    }
    $wp_plugin_template = new CustomUpsellPlugin();
    
}