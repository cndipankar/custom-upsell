<?php
if ( ! class_exists( 'CustomUpsell_Admin' ) ) {
    class CustomUpsell_Admin {
        /**
         * Constructor
         */

        public function __construct() {  
            
           // $affiliate_type_admin = new Affiliate_Type_Admin();
            //$affiliate_type_admin->load_affiliate_type_hook();
            add_action('admin_menu',array( $this,'menufunc')); 
            add_action( 'admin_enqueue_scripts', array($this,'load_admin_styles' ));
        }
        
        public function load_admin_styles(){
            wp_enqueue_style( 'cafunnel-admin.css', CustomUpsell_PLUGIN_URL . 'public/css/cafunnel-admin.css', false, '1.0.0' );
        }
        
        
       public function menufunc(){
            add_menu_page(__('Custom Upsell'), __('Custom Upsell','CustomUpsell.com'), 'manage_options', 'customupsell', array(&$this, 'custom_upsell_page'));
            //add_submenu_page('customupsell', __('Settings','CustomUpsell.com'), __('Settings','meuser.com'), 'manage_options', 'customupsell_settings', array(&$this, 'customupsell_settings'));
           
            $addpage = add_submenu_page( 
                '', 
                'Add Upsell Page', 
                'Add Upsell Page', 
                'manage_options', 
                'addupsellpage', 
                array( $this,'ca_admin_page_add_content')
            );
            add_action('load-'. $addpage, array( $this,'ca_load_admin_page_menu') );
            $mypage = add_submenu_page( 
                '', 
                'Edit Upsell Page', 
                'Edit Upsell Page', 
                'manage_options', 
                'editupsellpage', 
                array( $this,'ca_admin_page_edit_content')
            );
            add_action('load-'. $mypage, array( $this,'ca_load_admin_page_menu') );
            
        }

        public function ca_load_admin_page_menu(){

        }

        function custom_upsell_page()
        {
            global $wpdb;
            $upsell_query = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_upsell WHERE status='active'");
            ?>
            <div class="custom-funnels-header">
                <h1>Custom Upsell</h1><a href="<?php echo admin_url('/admin.php'); ?>?page=addupsellpage" class="add-n-btn">Add New</a>
            </div>
            <div class="custom-table-wrap"> 
             <table>
              <tr>
                <th>Funnel Name</th>
                <th>Target Product(s) </th>
                <th>Offers</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
              <?php
              if(count($upsell_query)>0)
              {
                  foreach($upsell_query as $row)
                  {
                    $target_id = $row->target_id;
                    $offer_details = json_decode($row->upsell_details);
                    //print_r($offer_details);
                    $offer_count = count($offer_details);
                  ?>
                      <tr>
                        <td><?php echo $row->funnel_name; ?></td>
                        <td>
                            <?php
                            
                                //echo $target_val;
                                $product = wc_get_product( $row->target_id );
                                echo $product->get_title()."(#".$row->target_id.")"."<br>";
                            
                            ?>
                        </td>
                        <td>
                            Offers Count - <?php echo $offer_count; ?><br>
                            <?php
                            foreach($offer_details as $offer_val)
                             {
                               
                                $offer_product = wc_get_product( $offer_val->offerProduct );
                                $offer_product_title = $offer_product->get_title()."(#".$offer_val->offerProduct.")";
                                echo $all_offer_product = 'offer #'.$offer_val->id."->".$offer_product_title."<br>";
                             }
                             ?>
                         </td>
                        <td class="cf-status"><?php echo $row->status; ?></td>
                        <td>
                            <a href="<?php echo admin_url('/admin.php'); ?>?page=editupsellpage&funnel_id=<?php echo $row->id; ?>" class="cf-edit">Edit</a>
                            <a href="javascript:void(0)" class="delete-aff" data-caid="<?php echo $row->id; ?>">Delete</a>
                         </td>
                      </tr>
              <?php
                }
            }
            else
            {
                ?>
                 <tr>
                    <td colspan="5">
                        <?php echo "No record found!"; ?>
                    </td>
                </tr>
                <?php
            }
           ?>
            </table>
        </div>
            <script type="text/javascript">
            jQuery('.delete-aff').on('click',function() {
                 if(confirm("Are you sure you want to delete this?")){

                        jQuery.ajax({
                            type : "POST",
                            dataType : "json",
                            url : "<?php echo admin_url('admin-ajax.php'); ?>",
                            data : {action: "delete_upsell","ca_id":jQuery(this).attr('data-caid')},
                            success: function(response) {
                                
                                location.reload();
                                
                            }
                        });
                    }
                    else{
                        return false;
                        }
                });
            </script>
            <?php


        }

        function ca_admin_page_edit_content()
        {
            global $wpdb;
              $funnel_id = $_GET['funnel_id'];
           $upsell_sql = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}custom_upsell where id='$funnel_id'" );
           
           $funnel_name = $upsell_sql[0]->funnel_name;
           $target_id = $upsell_sql[0]->target_id;
           $upsell_details = json_decode($upsell_sql[0]->upsell_details);

           $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                //'product_cat'    => 'hoodies'
            );
            $loop = new WP_Query( $args );

            if(isset($_POST['update_custom_upsell']))
            {
                
                $funnel_name = $_POST['funnel_name'];
                $target_product = $_POST['target_product'];
               
                //$offer_details = array();
                if(isset($_POST['offer_product']))
                {
                    //echo $_POST['offer_price'][0];
                    foreach($_POST['offer_product'] as $k=>$val)
                    {
                        
                       
                       $offer_product = $val;
                       $offer_price = $_POST['offer_price'][$k];
                       $offer_prd_name = $_POST['offer_prd_name'][$k];
                      
                        $id = $k+1;
                        $offer_details[] = array('id' => $id, 'offerProduct' => $offer_product, 'offerPrdName' =>$offer_prd_name , 'offerPrice' => $offer_price);
                    }

                    //echo '<pre>'; print_r($offer_details);

                    global $wpdb;
                    $tablename = $wpdb->prefix.'custom_upsell';

                   
                    $wpdb->update($tablename, array('funnel_name' =>$funnel_name,'target_id' => $target_product,'upsell_details' => json_encode($offer_details)), array('id' => $funnel_id));
                }
                
                 echo "<h3 style='color: green;'>Data updated Successfully</h3>";
                ?>
                    <script type="text/javascript">
                         setTimeout(function() {
                           window.location.reload();
                        }, 2500);
                        
                    </script>
                <?php
            }
           ?>
            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('.js-example-basic-multiple').select2();
                    jQuery('.offer-product').select2();
                });
            </script>
            <form method="post" class="add-cf-section">
                <div class="custom-funnels-header">
                    <h1>Edit Custom Funnel</h1>
                    <a href="<?php echo admin_url('/admin.php'); ?>?page=customupsell" class="add-n-btn">Back</a> 
                </div> 
 
                <div class="row mt-3">
                    <div class="col-lg-3"> 
                        <label>Funnel Name</label>
                        <input type="text" name="funnel_name" id="funnel_name" value="<?php echo $funnel_name; ?>" required>
                    </div>
                    <div class="col-lg-3">
                        <label>Target Product</label>
                        <select class="js-example-basic-multiple" name="target_product"  required>
                        <?php
                        while ( $loop->have_posts() ) : $loop->the_post();
                            $product = wc_get_product(get_the_Id());
                        ?>   
                        <option value="<?php echo get_the_Id(); ?>" <?php if ($target_id==get_the_Id()){ echo 'selected'; } ?>><?php echo get_the_title(); ?></option>
                        <?php 
                            if( $product->is_type( 'variable' ) ){
                                    $children_products = $product->get_children();
                                    if($children_products){
                                        foreach ($children_products as $children_product) {
                                            ?>
                                            <option value="<?php echo $children_product; ?>"<?php if ($target_id==$children_product){ echo 'selected'; } ?>>-<?php echo get_the_title($children_product); ?></option>
                                        <?php
                                            // code...
                                        }
                                    }

                             } 
                         endwhile;    
                        ?>
                            
                        </select>
                    </div> 
                </div>  

                <hr>
                 
                <?php
                $i=1;
                $ids = array_column($upsell_details, 'id');
                //print_r($ids);
                 foreach($upsell_details as $val)
                 {
                     
                ?> 
                    <div class="custom-funnels-header">
                        <h1> Offer# <?php echo $val->id; ?></h1>
                    </div> 

                    <div>
                        <div id="inputFormRow" class="row mt-2">
                            <div class="col-lg-3">
                                <label>Offer Product</label>
                                <select class="js-example-basic-multiple" name="offer_product[]" required>
                                 <option value="">Select</option>   
                                 <?php
                                 while ( $loop->have_posts() ) : $loop->the_post();
                                    $product = wc_get_product(get_the_Id());
                                 ?>   
                                  <option value="<?php echo get_the_Id(); ?>"<?php if(get_the_Id()==$val->offerProduct){ echo 'selected'; } ?>><?php echo get_the_title(); ?></option>
                                 <?php 
                                if( $product->is_type( 'variable' ) ){
                                        $children_products = $product->get_children();
                                        if($children_products){
                                            foreach ($children_products as $children_product) {
                                                ?>
                                                <option value="<?php echo $children_product; ?>"<?php if ($val->offerProduct==$children_product){ echo 'selected'; } ?>>-<?php echo get_the_title($children_product); ?></option>
                                            <?php
                                                // code...
                                            }
                                        }

                                 } ?>
                                <?php
                                
                                endwhile;
                                ?>     
                                </select>
                                
                            </div> 

                            <div class="col-lg-3">
                                <label>Product Name (Upsell Page)</label>
                                <input type="text" name="offer_prd_name[]" id="offer_prd_name" value="<?php echo isset($val->offerPrdName)?$val->offerPrdName:NULL; ?>">
                            </div>
                            <div class="col-lg-3">
                                <label>Offer Price</label>
                                <input type="text" name="offer_price[]" id="offer_price" value="<?php echo $val->offerPrice; ?>">
                            </div>

                            <div class="col-lg-3 input-group-append">
                                <label>&nbsp;</label><a href="javascript:void(0)" onclick="delete_offer('<?php echo $funnel_id; ?>','<?php echo $val->id ?>')" class="btn btn-danger">Delete</a>
                            </div>

                        </div>
                <?php
                $i++;
                }
                ?>
                 <div id="newRow"></div>

                 <div class="row mt-3 ">
                    <div class="col-lg-12">  
                        <button id="addRow" type="button" class="btn btn-info2">Add New Offer</button>
                        <input type="submit" name="update_custom_upsell" value="Save Changes">
                    </div>
                </div>
              
            </form> 
            <script type="text/javascript">
                function delete_offer(funnel_id,offer_id)
                {
                   if(confirm("Are you sure you want to delete this?")){
                        jQuery.ajax({
                                type : "POST",
                                dataType : "json",
                                url : "<?php echo admin_url('admin-ajax.php'); ?>",
                                data : {action: "delete_offer",
                                "funnel_id":funnel_id,
                                "offer_id":offer_id

                                },
                                success: function(response) {
                                    
                                    location.reload();
                                    
                                }
                            });
                    }
                    else
                    {
                      return false;
                    }    
                }
        // add row
            jQuery("#addRow").click(function () {

                var html = '';
                html += '<div id="inputFormRow" class="row mt-2"><div class="col-lg-3"><label>Offer Product</label><select class="offer-product" name="offer_product[]"><option value="">Select</option>';
                <?php
                  while ( $loop->have_posts() ) : $loop->the_post();
                    $product = wc_get_product(get_the_Id());
                ?>
                  html+='<option value="<?php echo get_the_Id(); ?>"><?php echo get_the_title(); ?></option>';   
                <?php 
                    if( $product->is_type( 'variable' ) ){
                            $children_products = $product->get_children();
                            if($children_products){
                                foreach ($children_products as $children_product) {
                                    ?>
                                    html+='<option value="<?php echo $children_product; ?>">-<?php echo get_the_title($children_product); ?></option>';
                                <?php
                                    // code...
                                }
                            }

                     } ?>
                    <?php
                    
                    endwhile;
                    ?>     
                                 
                 html+='</select></div><div class="col-lg-3"><label>Product Name (Upsell Page)</label><input type="text" name="offer_prd_name[]" id="offer_prd_name" value=""></div><div class="col-lg-3"><label>Offer Price</label><input type="text" name="offer_price[]" id="offer_price"></div><div class="col-lg-3 input-group-append"><label>&nbsp;</label><button id="removeRow" type="button" class="btn btn-danger">Remove</button></div></div>';
               

                jQuery('#newRow').append(html);
                jQuery('.offer-product').select2();
            });

            // remove row
            jQuery(document).on('click', '#removeRow', function () {
                jQuery(this).closest('#inputFormRow').remove();
            });

    </script>

        <?php
        }

        function ca_admin_page_add_content()
        {
            global $wpdb;
           
            if(isset($_POST['add_custom_upsell']))
            {
                
                $funnel_name = $_POST['funnel_name'];
                
                 $target_product = $_POST['target_product'];
                
               
                //$offer_details = array();
                if(isset($_POST['offer_product']))
                {

                    $sqlCheckFunnel = "SELECT count(*) countdata FROM {$wpdb->prefix}custom_upsell WHERE funnel_name = '".$funnel_name."'";
                    $queryCheckFunnel = $wpdb->get_results($sqlCheckFunnel);
                    //echo $_POST['offer_price'][0];
                    if($queryCheckFunnel[0]->countdata==0)
                    {

                        foreach($_POST['offer_product'] as $k=>$val)
                        {
                            
                           
                           $offer_product = $val;
                           $offer_price = $_POST['offer_price'][$k];
                           $offer_prd_name = $_POST['offer_prd_name'][$k];                           

                            $id = $k+1;
                            $offer_details[] = array('id' => $id, 'offerProduct' => $offer_product, 'offerPrdName' =>$offer_prd_name, 'offerPrice' => $offer_price);
                        }

                        
                        $tablename = $wpdb->prefix.'custom_upsell';

                        
                            $sqlIns=$wpdb->insert($tablename, array(
                            'funnel_name' => $funnel_name,
                            'target_id' => $target_product,
                            'upsell_details' => json_encode($offer_details),
                            ));                 
                        
                    
                         echo "<h3 class='cf-sucess' style='color: green;'>Data Insert Successfully</h3>";
                    }
                    else{
                         echo "<h3 class='cf-alert' style='color: #842029;'>This funnel has already exist!</h3>";
                    }    
                   
                }

               
                
            }

                $args = array(
                'post_type'      => 'product',
                'posts_per_page' => -1,
                 'post_status' => 'publish'
            );
            $loop = new WP_Query( $args );
        
            ?>
            <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
            <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script type="text/javascript">
                jQuery(document).ready(function() {
                    jQuery('.js-example-basic-multiple').select2();
                    jQuery('.offer-product').select2();
                });
            </script>
            <form method="post" class="add-cf-section">
                <div class="custom-funnels-header">
                    <h1>Add Custom Funnel</h1><a href="<?php echo admin_url('/admin.php'); ?>?page=customupsell" class="add-n-btn">List</a>
                </div> 
                
                <div class="row mt-3">
                    <div class="col-lg-3">
                        <label>Funnel Name</label>
                        <input type="text" name="funnel_name" id="funnel_name" required>
                    </div>
                    <div class="col-lg-3">
                        <label>Target Product</label>
                        <select class="js-example-basic-multiple" name="target_product" required>
                            <option value="">Select</option>
                            <?php
                            $product_id = '';
                            $product_title = '';
                            while ( $loop->have_posts() ) : $loop->the_post();
                                //echo get_the_Id();
                             $product = wc_get_product(get_the_Id());
                             
                            
                            ?>   
                            <option value="<?php echo get_the_Id(); ?>"><?php echo get_the_title(); ?></option>
                            <?php 
                            if( $product->is_type( 'variable' ) ){
                                    $children_products = $product->get_children();
                                    if($children_products){
                                        foreach ($children_products as $children_product) {
                                            ?>
                                            <option value="<?php echo $children_product; ?>">-<?php echo get_the_title($children_product); ?></option>
                                        <?php
                                            // code...
                                        }
                                    }

                             } ?>
                            <?php
                            
                            endwhile;
                            ?>   
                        </select>
                    </div>
                </div>

                <hr>

                <div class="custom-funnels-header">
                    <h1>Funnel Offers</h1>
                </div> 
                
                
                <div class="row mt-3">
                    <div id="inputFormRow" class="col-lg-3"> 
                        <label>Offer Product</label>
                        <select class="js-example-basic-multiple" name="offer_product[]" required>
                            <option value="">Select</option>   
                            <?php
                            while ( $loop->have_posts() ) : $loop->the_post();
                            $product = wc_get_product(get_the_Id());
                            ?>   
                            <option value="<?php echo get_the_Id(); ?>"><?php echo get_the_title(); ?></option>
                            <?php 
                            if( $product->is_type( 'variable' ) ){
                                    $children_products = $product->get_children();
                                    if($children_products){
                                        foreach ($children_products as $children_product) {
                                            ?>
                                            <option value="<?php echo $children_product; ?>">-<?php echo get_the_title($children_product); ?></option>
                                        <?php
                                            // code...
                                        }
                                    }

                             } ?>
                            <?php
                            
                            endwhile;
                            ?>     
                        </select>  
                    </div>
                    <div class="col-lg-3">
                        <label>Product Name (Upsell Page)</label>
                        <input type="text" name="offer_prd_name[]" id="offer_prd_name" value="">
                    </div>
                    <div class="col-lg-3">
                        <label>Offer Price</label>
                        <input type="text" name="offer_price[]" id="offer_price">
                    </div> 
                    
                    <div class="col-lg-3"> 
                        <label>&nbsp;</label>
                        <button id="addRow" type="button" class="btn btn-info">Add New Offer</button>
                    </div> 

                </div>    

                <div id="newRow"></div>

                <div class="row mt-3 ">
                    <div class="col-lg-12"> 
                        <input type="submit" name="add_custom_upsell" value="Save Changes" class="m-0"> 
                    </div>
                </div>
            </form>    
            <script type="text/javascript">
                // add row
                jQuery("#addRow").click(function () {

                    var html = '';
                    html += '<div id="inputFormRow" class="row mt-2"><div class="col-lg-3"><label>Offer Product</label><select class="offer-product" name="offer_product[]"><option value="">Select</option>';
                    <?php
                      while ( $loop->have_posts() ) : $loop->the_post();
                        $product = wc_get_product(get_the_Id());
                    ?>
                      html+='<option value="<?php echo get_the_Id(); ?>"><?php echo get_the_title(); ?></option>';   
                    <?php 
                            if( $product->is_type( 'variable' ) ){
                                    $children_products = $product->get_children();
                                    if($children_products){
                                        foreach ($children_products as $children_product) {
                                            ?>
                                            html+='<option value="<?php echo $children_product; ?>">-<?php echo get_the_title($children_product); ?></option>';
                                        <?php
                                            // code...
                                        }
                                    }

                             } ?>
                            <?php
                            
                            endwhile;
                            ?>        
                                     
                     html+='</select></div><div class="col-lg-3"><label>Product Name (Upsell Page)</label><input type="text" name="offer_prd_name[]" id="offer_prd_name" value=""></div><div class="col-lg-3"><label>Offer Price</label><input type="text" name="offer_price[]" id="offer_price"></div><div class="col-lg-3 input-group-append"><label>&nbsp;</label><button id="removeRow" type="button" class="btn btn-danger">Remove</button></div></div>';
                   

                    jQuery('#newRow').append(html);
                    jQuery('.offer-product').select2();
                });

                // remove row
                jQuery(document).on('click', '#removeRow', function () {
                    jQuery(this).closest('#inputFormRow').remove();
                });

            </script>

            <?php
        }
    
  }
}