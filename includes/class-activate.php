<?php 
if ( ! class_exists( 'CustomUpsell_Activate' ) ) {
	class CustomUpsell_Activate {
        /**
         * Constructor
         */

        public function __construct() {  
            $this->activate_plugin();
        }
		public  function activate_plugin() {
			global $wpdb;		   
            $plugin_name_db_version = '1.0';
            $table_name = $wpdb->prefix . "custom_upsell";

            $table_processing_offer = $wpdb->prefix . "custom_upsell_processing_offer";

            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id bigint(50) NOT NULL AUTO_INCREMENT,
                funnel_name varchar(256) NOT NULL,
                target_id bigint(50) NOT NULL,
                upsell_details Text NULL,
                status varchar(200) NOT NULL DEFAULT 'active',
                PRIMARY KEY (id)
            ) $charset_collate;";

            $sql1 = "CREATE TABLE IF NOT EXISTS $table_processing_offer (
                id bigint(50) NOT NULL AUTO_INCREMENT,
                funnel_id bigint(50) NOT NULL,
                session_id varchar(256) NOT NULL,
                offer_id bigint(50) NOT NULL,
                offer_product_id bigint(50) NOT NULL,
                offer_price DECIMAL (10,2) NULL,
                status TINYINT NOT NULL DEFAULT '0',
                processing TINYINT NOT NULL DEFAULT '0',
                date_time datetime default CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";


            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            dbDelta( $sql1 );

            
            
        }
		
		
	}
}
?>