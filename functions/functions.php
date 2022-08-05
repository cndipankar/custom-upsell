<?php
add_action( 'wp_ajax_nopriv_delete_upsell', 'delete_upsell' );
add_action( 'wp_ajax_delete_upsell', 'delete_upsell' );
function delete_upsell(){
    $ca_id = $_POST['ca_id'];
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_upsell';
    $wpdb->delete( $table_name, array('id' => $ca_id) );
}

add_action( 'wp_ajax_nopriv_delete_offer', 'delete_offer' );
add_action( 'wp_ajax_delete_offer', 'delete_offer' );
function delete_offer(){
	global $wpdb;
   $table_name = $wpdb->prefix . 'custom_upsell';
     $funnel_id = $_POST['funnel_id'];
     $offer_id = $_POST['offer_id'];
     if(!empty($funnel_id) && !empty($offer_id))
     {
     	$upsell_sql = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_upsell where id='$funnel_id'" );
        $upsell_details = json_decode($upsell_sql[0]->upsell_details,true);

        $id = array_column($upsell_details, 'id');
        //print_r($id);
        //print_r($upsell_details);

        foreach($upsell_details as $key => $item){
       	  //echo $key;
          if($item['id']==$offer_id)
          {
            unset($upsell_details[$key]);
          }
       		
		 }
       $newArray = array_values($upsell_details);
       $wpdb->update($table_name, array('upsell_details' => json_encode($newArray)), array('id' => $funnel_id));
       echo json_encode(array("code" => 200));
     }
    
    //$table_name = $wpdb->prefix . 'custom_upsell';
    //$wpdb->delete( $table_name, array('id' => $ca_id) );
    die();
}


function custom_redirects() {
    global $woocommerce;
    global $wpdb;
    $order_id = '';
    if ( is_wc_endpoint_url( 'order-received' ) ) {

         global $wp;
         //Get Order ID

         $order_id =  intval( str_replace( 'checkout/order-received/', '', $wp->request ) );

      }
      $wp_capabilities['zenagent'] = '';  
      if(is_user_logged_in()){
            $wp_capabilities = get_user_meta(get_current_user_id(),'wp_capabilities',true);
            //print_r($wp_capabilities); 
       } 
          
     
    if ( is_page('checkout') && !($order_id) && empty($wp_capabilities['zenagent'])) {

      
         $items = $woocommerce->cart->get_cart();
         $currentDate = date("Y-m-d");
         $yesterday = date("Y-m-d", time() - 86400);
        

         $del_table = $wpdb->prefix . "custom_upsell_processing_offer";

          $deleteQ = "DELETE FROM {$del_table} WHERE `date_time`<'".$currentDate."'";
          $wpdb->query($deleteQ); 
         
      
         $cart_item_product = array();
         $cart_item_variation = array();
        foreach($items as $item => $values) { 
            $product_id =  $values['product_id']; 
            $variation_id = $values['variation_id'];
            $cart_item_product[]=$product_id;
            $cart_item_variation[]=$variation_id;
            
        } 
         
       
        $array_product = implode("','",$cart_item_product);
        $array_variation = implode("','",$cart_item_variation);
        
         
         $session_id =  WC()->session->get_customer_id();
         
         
          $sql = "SELECT * FROM {$wpdb->prefix}custom_upsell WHERE status='active' AND target_id IN ('".$array_product."') OR target_id IN ('".$array_variation."')";
         $query = $wpdb->get_results($sql);
         $offer_details_session = array();
         $i = 0;
          
         if($query)
         {
           
            foreach($query as $row)
            {
               
                  foreach(json_decode($row->upsell_details) as $offer_data)
                  {

                     

                          $sqlCheck = "SELECT COUNT(*) countdata FROM {$wpdb->prefix}custom_upsell_processing_offer WHERE session_id='".$session_id."' AND offer_id ='".$offer_data->id."' AND funnel_id = '".$row->id."' ";
                         
                        $queryCheck = $wpdb->get_results($sqlCheck);
                        
                        if(!$queryCheck[0]->countdata)
                        {
                          
                            $custom_upsell_processing_offer = $wpdb->prefix.'custom_upsell_processing_offer';
                           $sqlIns=$wpdb->insert($custom_upsell_processing_offer, array(
                           'funnel_id' => $row->id,
                           'session_id' => $session_id,
                           'offer_id' => $offer_data->id,
                           'offer_product_id' => $offer_data->offerProduct,
                           'offer_price' => $offer_data->offerPrice,
                           'status' => 0,
                           
                           ));

                           
                              foreach($items as $itemnew => $valuesnew) {
                              $product_in_cart = $valuesnew['product_id'];
                              $var_product_in_cart = $valuesnew['variation_id'];

                              if ( $product_in_cart == $offer_data->offerProduct || $var_product_in_cart == $offer_data->offerProduct) :
                              $wpdb->update($custom_upsell_processing_offer, array('status' => 1), array('funnel_id' => $row->id,'session_id' => $session_id,'status' => 0));
                              //,'offer_product_id'=>$offer_data->offerProduct
                              endif;
                           }

                        }
                       
                        
                  } 
                 

                   $sqlCheckTotalUpsell = "SELECT COUNT(*) countdata FROM {$wpdb->prefix}custom_upsell_processing_offer WHERE session_id='".$session_id."' AND status=1 AND funnel_id = '".$row->id."' ";
                  $queryCheckTotalUpsell = $wpdb->get_results($sqlCheckTotalUpsell);
              

                   $totalUpsellDetails = count(json_decode($row->upsell_details));

                 // echo $queryCheckTotalUpsell[0]->countdata;
                  //die();

                  if($queryCheckTotalUpsell[0]->countdata==$totalUpsellDetails)
                  {
                     
                  }
                  else
                  {
                     
                     $page = get_page_by_path( 'offer-page' );
                     wp_redirect(get_permalink($page->ID),301);

                     
                    
                  }

            }
             
         }
         
    }
    
 
}


add_action( 'template_redirect', 'custom_redirects' );




add_action( 'woocommerce_before_calculate_totals', 'rudr_custom_price_refresh' );

function rudr_custom_price_refresh( $cart_object ) {

   foreach ( $cart_object->get_cart() as $item ) {

      if( array_key_exists( 'misha_custom_price', $item ) ) {
         $item[ 'data' ]->set_price( $item[ 'misha_custom_price' ] );
      }
      
   }
   
}

function upsell_order_function( $atts , $content ="") {
   wp_enqueue_style( 'upsell_front', CustomUpsell_PLUGIN_URL . 'public/css/upsell-front.css', false, '1.0.0' );
   global $wpdb;
   //$session_id = wp_get_session_token();
   
     
   $session_id = WC()->session->get_customer_id();
     
    $sql = "SELECT * FROM {$wpdb->prefix}custom_upsell_processing_offer WHERE session_id='".$session_id."' AND status = 0 Order by id asc limit 1";
    $query = $wpdb->get_results($sql);
    //print_r($query);
    //echo count($query);

    $offer_product_id = '';
    $offer_price = 0;
    $processing_id = '';
    $title = '';
    $product_image = '';
    if(count($query)>0)
    {
       $offer_product_id = $query[0]->offer_product_id;
       $main_price = get_post_meta( $offer_product_id, '_regular_price', true);
       //$offer_price = $query[0]->offer_price;
       $processing_id = $query[0]->id;

       $offer_id = $query[0]->offer_id;
       $funnel_id = $query[0]->funnel_id;

       $sqlFunnel = "SELECT upsell_details FROM {$wpdb->prefix}custom_upsell WHERE id='".$funnel_id."'";
       $queryFunnel = $wpdb->get_results($sqlFunnel);

       //print_r($queryFunnel[0]->upsell_details);
       $upselldetails_offer = !empty($queryFunnel[0]->upsell_details)?json_decode($queryFunnel[0]->upsell_details,true):NULL;
       $offer_prdname="";
       if(!empty($upselldetails_offer)):
       foreach($upselldetails_offer as $value)
       {
         
         if($value['id']==$offer_id)
         {
            //echo 'price='. $value['offerPrice'];
            if(!empty($value['offerPrice']))
            {
               $offer_price = $value['offerPrice'];
            }
            else
            {
               $offer_price = $main_price;
            }
            
         }
         if($value['id']==$offer_id)
         {
            //echo 'price='. $value['offerPrice'];
            if(!empty($value['offerPrdName']))
            {
               $offer_prdname = $value['offerPrdName'];
            }
            
         }
       }
      endif;
      global $product;
       $product = wc_get_product( $offer_product_id );
       $title = !empty($offer_prdname)?$offer_prdname:$product->get_name();
       $product_image = $product->get_image();
       //WF_Base::instance()->set_id($product->id);
      
      $pdp_data = WF_Base::instance()->pdp_data($product->id);
   //echo '<pre>';print_r($pdp_data);die;
      $head_text = $pdp_data['heading_text'];
      $head = $head_text ? '<h1 class="fp_head">'.$head_text.'</h1>' : '';
      $enable_tc = $pdp_data['enable_tc'][0];
      $tc_text = $pdp_data['tc_text'];
      //echo $product->id;die;
//echo '<pre>';print_r($product);die;
      // $product = new WC_Product;
       //$product->set_id($offer_product_id);
       

       //  echo '<div class="product-gallery large col">';
    		/**
    		 * woocommerce_before_single_product_summary hook
    		 *
    		 * @hooked woocommerce_show_product_sale_flash - 10
    		 * @hooked woocommerce_show_product_images - 20
    		 */
    		//do_action( 'woocommerce_before_single_product_summary' );
   //echo '</div>';
    	

    //	echo '<div class="product-info summary col-fit col entry-summary">';

    		
    			/**
    			 * woocommerce_single_product_summary hook
    			 *
    			 * @hooked woocommerce_template_single_title - 5
    			 * @hooked woocommerce_template_single_rating - 10
    			 * @hooked woocommerce_template_single_price - 10
    			 * @hooked woocommerce_template_single_excerpt - 20
    			 * @hooked woocommerce_template_single_add_to_cart - 30
    			 * @hooked woocommerce_template_single_meta - 40
    			 * @hooked woocommerce_template_single_sharing - 50
    			 */
    			//do_action( 'woocommerce_single_product_summary' );
    		

    //echo '	</div>';


       echo '<section class="upsell-main-wrap">
      <div class="container">
         <div class="row">
            <div class="col small-12 large-12">
               <div class="col-innerx">
                  '.$head.'
               </div>
            </div>
         </div>
         <div class="inner-wrap image-fix">
            <div class="row">
               <div class="col small-12 large-6">';
               do_action( 'woocommerce_before_single_product_summary' );
               echo '</div>
               <div class="col small-12 large-6 upsell-details up-d2">
               <h4 class="mb-0">'.$title.'</h4>';
               //<h4 class="mb-25"><div class="mwb_upsell_offer_product_price">$'.$offer_price.'</h4>
               echo '<p>'.get_the_excerpt($offer_product_id ).'</p><span class="dev"></span>';
               remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_title', 5);
               remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);
               //remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
               remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50);
               
               

               if($enable_tc == 'yes'){
                  $notrhanks_class = 'leftside';
                  echo '<div id="pdp_tc">
                     <div class="radio-wr">
                        <label class="labelMadel">
                           <input value="1" type="checkbox" name="offer-checkboxUltra" id="offer-details-checkbox-pdp" class="offerCheck" data-group="1">'.$tc_text.'  
                        </label>
                     </div>
                  <div style="clear: both;"></div>
                  </div>'; ?>
                  
                  <script>
                     setTimeout(() => {
                        jQuery(document).find( ".single_add_to_cart_button" ).attr('disabled' , true  );
                        jQuery('#offer-details-checkbox-pdp').on('click' , function(){
                           if(jQuery(this).is(":checked")){
                              jQuery(document).find( ".single_add_to_cart_button" ).removeAttr('disabled' );
                           }else{
                              jQuery(document).find( ".single_add_to_cart_button" ).attr('disabled' ,true );
                           }
                        });
                     }, 500 );

                    
                     
                     
                  </script>
                  <?php 
               }else{
                  $notrhanks_class = '';
               }
               
               ?>
               <script>
                   setTimeout(() => {
                        jQuery(document).find('.product_title').hide();
                     }, 2500);
               </script>
               <?php

               do_action( 'woocommerce_single_product_summary' );
               echo '<input type="button" name="no_thanks" id="no_thanks" value="No Thanks" class="btn-link-up2 '.$notrhanks_class.'" onclick="get_buy_now(`'.$offer_price.'`,'.$offer_product_id.','.$processing_id.',`no`)">';
               ?>
            
               <?php

               // echo '<form method="post">
               //       <input type="hidden" name="price" id="price" value="'.$offer_price.'">
               //       <input type="hidden" name="product_id" id="product_id" value="'.$offer_product_id.'">
               //       <input type="hidden" name="processing_id" id="processing_id" value="'.$processing_id.'">
               //       <div class="btn-div"> 
               //          <input type="button" id="buy_now" name="buy_submit" value="ADD TO CART" class="button" onclick="get_buy_now('.$offer_price.','.$offer_product_id.','.$processing_id.',`add`)">
               //          <input type="button" name="no_thanks" id="no_thanks" value="No Thanks" class="btn-link-up2" onclick="get_buy_now('.$offer_price.','.$offer_product_id.','.$processing_id.',`no`)">
               //           <span class="loader_btn01" id="loader_btn01" style="display:none;"><img src="'.get_stylesheet_directory_uri()."/images/loading-buffering.gif".'"></span>
               //       </div>
               //    </form>';

                  echo  '</div>
            </div>
         </div>
      </div>
      </section>';

    }
    else
    {
       $string = wc_get_cart_url();
      //wp_redirect(get_permalink($string));
      ?>
       <script type="text/javascript">window.location.href='<?php echo $string; ?>'</script>
      <?php
    }


    //print_r($query[0]->offer_product_id);
    

      ?>
      <script type="text/javascript">
         function get_buy_now(price=0,product_id=0,processing_id=0,status)
         {
            jQuery('#buy_now').prop('disabled', true);
            jQuery('#no_thanks').prop('disabled', true);

            //jQuery('#buy_now').hide();
            //jQuery('#no_thanks').hide();
            jQuery('#loader_btn01').show();
           
            jQuery.ajax({
               url: '<?php echo admin_url('admin-ajax.php'); ?>',
               type: 'POST',
               dataType: 'json',
               data: {
                   action: 'ajaxAddToCart',
                   product_id,
                   price,
                   processing_id,
                   status
               },
               success: function(res) {
                 //jQuery('#loader_btn01').hide();
                 
                 if(res.code===200)
                 {
                  //alert(res.url);
                   window.location.href=res.url;
                 }
               }
           });
         }
      </script>
      <?php
    
  // $content.=$sql;
   echo $content;                  
                           
        
}
add_shortcode('upsell-order', 'upsell_order_function',1);


add_action('wp_ajax_ajaxAddToCart', 'ajaxAddToCart' );
add_action('wp_ajax_nopriv_ajaxAddToCart', 'ajaxAddToCart' );
function ajaxAddToCart(){
   global $wpdb;
   $product_id = trim($_POST['product_id']);
   $price = trim($_POST['price']);
   $processing_id = trim($_POST['processing_id']);

   $status = trim($_POST['status']);
   
   $checkout_url = wc_get_checkout_url();
   $result = array();
   if(!empty($product_id) && !empty($price)){
     
     if($status=='add')
     {
      $addcart = WC()->cart->add_to_cart($product_id, 1, 0, array(), array( 'misha_custom_price' => $price) );
     }
     

     $execut= $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}custom_upsell_processing_offer SET status = %d WHERE id = %d", "1", $processing_id ) );  
      $result['code'] = 200;
      $result['url'] = $checkout_url;
      
   }elseif($status=='no'){
      $execut= $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}custom_upsell_processing_offer SET status = %d WHERE id = %d", "1", $processing_id ) );
      $result['code'] = 200;
      $result['url'] = $checkout_url;
   }else{
      $result['code'] = 500;
      
   }
   
   echo  json_encode($result);
   exit();
}

add_action('woocommerce_thankyou', 'enroll_student', 10, 1);
function enroll_student( $order_id ) {
   global $wpdb;
    $session_id =  WC()->session->get_customer_id();
   $table_name = $wpdb->prefix . 'custom_upsell_processing_offer';
   $wpdb->delete( $table_name, array('session_id' => $session_id) );
   
}

/*function wf_shop_no_thanks_button() {
   if(is_product()){
      echo '<input type="button" name="no_thanks" id="no_thanks" value="No Thanks" class="btn-link-up2" onclick="get_buy_now('.$offer_price.','.$offer_product_id.','.$processing_id.',`no`)">';
   }
   
}*/
//add_action( 'woocommerce_after_shop_loop_item', 'wf_shop_no_thanks_button', 20 );
//add_action( 'woocommerce_after_add_to_cart_button', 'wf_shop_no_thanks_button', 20 );


add_action( 'woocommerce_add_to_cart', 'wf_upsell_processing_offer' );
function wf_upsell_processing_offer(){
   global $wpdb;
   $productID = get_the_ID();
//echo $productID;die;
   $session_id = WC()->session->get_customer_id();
   $sql = "SELECT * FROM {$wpdb->prefix}custom_upsell_processing_offer WHERE session_id='".$session_id."' AND status = 0 Order by id asc limit 1";
   $query = $wpdb->get_results($sql);
  // print_r($query);die;
   $processing_id = '';
   if(count($query)>0){
       $offer_product_id = $query[0]->offer_product_id;
       $processing_id = $query[0]->id;
       $execut= $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}custom_upsell_processing_offer SET status = %d WHERE id = %d", "1", $processing_id ) );
   }
}

add_action( 'woocommerce_update_cart_action_cart_updated', 'wf_on_action_cart_updated', 20, 1 );
function wf_on_action_cart_updated( $cart_updated ){
   $session_id =  WC()->session->get_customer_id();
   if($cart_updated){
      
   
      if ( WC()->cart->is_empty() ) {
         global $wpdb;
         
      // print_r($session_id);die;
         $table_name = $wpdb->prefix . 'custom_upsell_processing_offer';
         $wpdb->delete( $table_name, array('session_id' => $session_id) );
      }
   }
}

// add_action('init' , 'fff');
// function fff(){
//    $session_id =  WC()->session->get_customer_id();
//    echo $session_id;die;
// }