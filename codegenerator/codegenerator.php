<?php
/**
 * Plugin Name: Code Generator
 * Plugin URI: http://URI_Of_Page_Describing_Plugin_and_Updates
 * Description: This Plugin is used to generate custom code for Wordpress.
 * Version: 1.0
 * Author: Pradeep Singh
 * Author URI: http://PRADEEPSINGH
 * License: A "Slug" license name e.g. GPL2
 */

if(!defined('ABSPATH')) exit;

if(!class_exists('CodeGenerator'))
{
    class CodeGenerator
    {
        public $version = '1.0';
        
        public $codeType = array('1'=>'CPT','2'=>'Taxonomy','3'=>'Metaboxes','4'=>'Wordpress Ajax Code','5'=>'Widgets','6'=>'Custom Hook','7'=>'Wordpress Media Uploader Code For Admin','8'=>'Custom Admin Post Type Order');
        
        public function __construct() {
            
            register_activation_hook(__FILE__,array($this,'activate_plugin')); //install plugin
            register_deactivation_hook(__FILE__,array($this,'deactivate_plugin')); //uninstall plugin
            
            add_action('admin_menu',array($this,'plugin_menus')); //add Menus at admin
            add_action('admin_init',array($this,'plugin_admin_init'));
            //Ajax hook
            add_action('wp_ajax_generatecode', array($this, 'ajaxgeneratecode')); 
            add_action('wp_ajax_nopriv_generatecode', array($this, 'ajaxgeneratecode')); 

        }
        public function plugin_admin_init()
        {
            wp_register_script( 'validationEngine-en', plugins_url( 'includes/jquery.validationEngine-en.js', __FILE__ ) );
            wp_register_script( 'validationEngine', plugins_url( 'includes/jquery.validationEngine.js', __FILE__ ) );
            wp_register_style('validationEngine-style', plugins_url( 'includes/validationEngine.jquery.css', __FILE__ ) );
            wp_register_style('codegenerator-style', plugins_url( 'includes/codegenerator.css', __FILE__ ) );
        }
        public function plugin_menus()
        {
            $page_hook_suffix = add_menu_page( 'Code Generator', 'Code Generator', 'manage_options', 'codegenerator',array($this,'menu_page'), plugins_url( 'codegenerator/images/ic.gif' ),100);
             add_action('admin_print_scripts-' .$page_hook_suffix, array($this,'plugin_admin_scripts'));
        }
        public function plugin_admin_scripts()
        {
            wp_enqueue_script('validationEngine-en');
            wp_enqueue_script('validationEngine');
            wp_enqueue_style('validationEngine-style');
            wp_enqueue_style('codegenerator-style');
        }
        
        public function ajaxgeneratecode()
        {
            //print_r($_POST); die();
            $generate_code_for = $_POST['generate_code_for'];
            $output=null;
            switch ($generate_code_for)
            {
                case 1 : $output = $this->generateCPT($_POST['cpt']);                    break; 
                case 2 : $output = $this->generateTaxonomy($_POST['taxonomy']);          break; 
                case 3 : $output = $this->generateMetabox($_POST['metabox']);            break; 
                case 4 : $output = $this->generateWPAjax($_POST['wp_ajax']);            break; 
                case 5 : $output = $this->generateWidget($_POST['widget']);            break; 
                case 6 : $output = $this->generateHook($_POST['hook']);                break; 
                case 7 : $output = $this->generateMediaFrame($_POST['media']);                break; 
                case 8 : $output = $this->generateAdminPostOrder($_POST['admin_order']);                break; 
		default : $output = "<h3>No Code Found</h3>";
            }
            echo $output;
            wp_die(); 
        }
        public function generateAdminPostOrder($admin_order)
        {
            $output = 'function set_'.$admin_order['post_type'].'_admin_order($wp_query) {  
if (is_admin()) {  
	 // Get the post type from the query  
	 $post_type = $wp_query->query["post_type"];  
	 if ( $post_type == "'.$admin_order['post_type'].'") {  
	 	  // "orderby" value can be any column name  
		  $wp_query->set("orderby", "'.$admin_order['orderby'].'");  
	 	  // "order" value can be ASC or DESC  
		  $wp_query->set("order", "'.$admin_order['order'].'");  
		}  
	  }  
	}  
add_filter("pre_get_posts", "set_'.$admin_order['post_type'].'_admin_order");';
            return htmlentities($output);
        }
        public function generateMediaFrame($media)
        {
            $multiple = 'false';
            if($media['multiple']) $multiple = 'true';
            $output = 'add_action("admin_footer","'.$media['media_id'].'_javascript");
function '.$media['media_id'].'_javascript(){
    ?>
<script type="text/javascript">
jQuery(document).ready(function() { 
   var custom_file_frame;
   jQuery(document).on("click", "'.$media['selector'].'", function(event) {  
      event.preventDefault();
      if (typeof(custom_file_frame)!=="undefined") {
         custom_file_frame.close();
      }

      //Create WP media frame.
      custom_file_frame = wp.media.frames.customHeader = wp.media({
         //Title of media manager frame
         title: "'.$media['title'].'",
         library: {
            type: "image"
         },
         button: {
            //Button text
            text: "'.$media['button_text'].'"
         },
         //Do not allow multiple files, if you want multiple, set true
         multiple: '.$multiple.'
      });

      //callback for selected image
      custom_file_frame.on("select", function() {
         var attachment = custom_file_frame.state().get("selection").first().toJSON();
         //do something with attachment variable, for example attachment.filename
         alert(attachment.toSource());
      });
     //Open modal
      custom_file_frame.open();
   });
});
</script>
<?php
}
add_action("wp_enqueue_scripts","'.$media['media_id'].'_script");
function '.$media['media_id'].'_script()
{
    if ( ! did_action( "wp_enqueue_media" ) )
    wp_enqueue_media();
}';		
            return htmlentities($output);
        }
        public function generateHook($hook)
        {
            if($hook['argument']) 
            {
               $output = '$a = array(
	"eye patch" => "yes",
	"parrot" => true,
	"wooden leg" => (int) 1
);
$b = "And Hook said: I ate ice cream with Peter Pan.";
do_action("'.$hook['hook_name'].'",$a,$b); //copy this line and paste where you weant to print.                    
add_action("'.$hook['hook_name'].'","'.$hook['hook_name'].'_callback",10,2);
function '.$hook['hook_name'].'_callback($a,$b)
    {
       echo "<h3>This is custom hoock output.</h3><br>";
       print_r( $a );
       echo $b;
    }
';
            }
            else
            {
                $output = 'do_action("'.$hook['hook_name'].'"); //copy this line and paste where you weant to print. 
/*Copy Blow Code and paste in function file*/
add_action("'.$hook['hook_name'].'","'.$hook['hook_name'].'_callback");
function '.$hook['hook_name'].'_callback()
    {
       echo "<h3>This is custom hoock output.</h3>";
    }
';
            }
            return htmlentities($output);
        }
        public function generateWidget($widget)
        {
            $output = 'add_action("widgets_init","'.$widget['widget_id'].'_callback");
function '.$widget['widget_id'].'_callback() {
	register_widget("'.$widget['widget_id'].'_CLASS");
}
class '.$widget['widget_id'].'_CLASS extends WP_Widget {
       function __construct() {
	 	parent::__construct(
			"'.$widget['widget_id'].'", // Widget ID
			__("'.$widget['title'].'", "'.$widget['widget_id'].'"), // Name
			array( "description" => __( "'.$widget['desc'].'", "'.$widget['widget_id'].'" ), ) // Args
		);
	}
        function widget( $args, $instance ) {
		extract( $args );

		//Our variables from the widget settings.
		$title = apply_filters("widget_title", $instance["title"] );
		$name = $instance["name"];
		echo $before_widget;
                if ( $name )
	          printf( "<p>" . __("Hello %s.", "'.$widget['widget_id'].'") . "</p>", $name );
 
		// Display the widget title 
		if ( $title )
			echo $before_title . $title . $after_title;

                echo $after_widget;
	}
       //Update the widget 
	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		//Strip tags from title and name to remove HTML 
		$instance["title"] = strip_tags( $new_instance["title"] );
		$instance["name"] = strip_tags( $new_instance["name"] );
		
		return $instance;
	}  
        function form( $instance ) {
            ?>
               <p>
		<label for="<?php echo $this->get_field_id( "title" ); ?>"><?php _e("Title:", "'.$widget['widget_id'].'"); ?></label>
		<input id="<?php echo $this->get_field_id( "title" ); ?>" name="<?php echo $this->get_field_name( "title" ); ?>" value="<?php echo $instance["title"]; ?>" style="width:100%;" />
		</p>
                <p>
	         <label for="<?php echo $this->get_field_id( "name" ); ?>"><?php _e("Name:", "'.$widget['widget_id'].'"); ?></label>
		 <input id="<?php echo $this->get_field_id( "name" ); ?>" name="<?php echo $this->get_field_name( "name" ); ?>" value="<?php echo $instance["name"]; ?>" style="width:100%;" />
		</p>
	<?php
	}
 }       
';
            return htmlentities($output);
        }
        public function generateWPAjax($ajax)
        {
            $loader = null;
            $removeloader = null;
            if($ajax['ajax_for'])
            {
                if($ajax['ajax_for']=="wp") $hook = "wp_footer"; 
                else $hook = "admin_footer";
            }
            if($ajax['form_loader'])
            {
                    $html = '<span id="'.$ajax["form_id"].'_ajax"><img src="<?php echo get_bloginfo("url"); ?>/wp-includes/images/wpspin.gif" alt=""/></span>'; 
                    $loader = "jQuery('#".$ajax["form_id"]."').append('".$html."');";
                    $removeloader = 'jQuery("#'.$ajax["form_id"].'").find("#'.$ajax["form_id"].'_ajax").remove();';
            }
$output='add_action('.$hook.',"'.$ajax['form_action'].'_javascript");
function '.$ajax['form_action'].'_javascript()
{
?>
<script type="text/javascript">
                jQuery(document).ready(function(){
                 jQuery("#'.$ajax["form_id"].'").submit(function(){
                        var formData = jQuery("#'.$ajax["form_id"].'").serializeArray();
                        jQuery.ajax({
                               type:"POST",
                               url: "<?php echo get_bloginfo("url"); ?>/wp-admin/admin-ajax.php?action='.$ajax['form_action'].'",
                               data:formData,
                               beforeSend:function(){
                                       '.$loader.'
                               },
                               success:function(response){
                                       if(response)
                                               {
                                                       '.$removeloader.'
                                                       jQuery("'.$ajax['form_res_selector'].'").css("display","block");
                                                       jQuery("'.$ajax['form_res_selector'].'").html(response);
                                               }
                               },
                               error: function(MLHttpRequest, status, error){  
                                       alert(error);  
                               }  
                                });
                         return false;
			 });
                      });
   </script>
   <?php
   }
    //Ajax Wordpress hooks
    add_action("wp_ajax_'.$ajax['form_action'].'", "'.$ajax['form_action'].'_callback"); 
    add_action("wp_ajax_nopriv_'.$ajax['form_action'].'", "'.$ajax['form_action'].'_callback");
    /** //For classes call hooks like as...
    add_action("wp_ajax_'.$ajax['form_action'].'", array($this,"'.$ajax['form_action'].'_callback")); 
    add_action("wp_ajax_nopriv_'.$ajax['form_action'].'", array($this,"'.$ajax['form_action'].'_callback"));
    **/    
    function '.$ajax['form_action'].'_callback()
    {
            print_r($_POST);
            //do somethings...
            wp_die();
    } 
    ';
            return htmlentities($output);
        }
        public function generateMetabox($meta)
        {
                $html = '?>
			    <p>  
                            <label for="my_meta_box_text">Text Label</label>  
                            <input type="text" name="my_meta_box_text" id="my_meta_box_text" value="<?php echo get_post_meta($post->ID,"my_meta_box_text",true); ?>" />  
                            </p>
			<?php';
                $output = '
                        add_action("add_meta_boxes", "add_'.$meta["html_id"].'");
                        add_action("save_post", "save_'.$meta["html_id"].'");
                        function add_'.$meta["html_id"].'() {
                                  add_meta_box(
                                        "'.$meta["html_id"].'",
                                        "'.$meta["title"].'", 
                                        "content_'.$meta["html_id"].'", 
                                        "'.$meta["post_type"].'", 
                                        "'.$meta["place"].'", 
                                        "'.$meta["priority"].'" 
                                    );
                              }
                        function content_'.$meta["html_id"].'($post){
                               '.htmlentities($html).'
                             }
                          //save function
                          function save_'.$meta["html_id"].'(){
                               if($_POST["my_meta_box_text"])
                                {
                                    update_post_meta($_POST["post_ID"],"my_meta_box_text",$_POST["my_meta_box_text"]);
                                }
			   }
                           ';
                
                return $output;
        }
        public function generateTaxonomy($taxonomy)
        {
            $output = "function register_taxonomy_".$taxonomy['slug']."(){
                       register_taxonomy('".$taxonomy['slug']."',
                            array('".$taxonomy['post_type']."'), 
                                    array(
                                            'hierarchical'=>true,
                                            'show_ui'=>true,
                                            'labels' => array(
                                                    'name' => '".$taxonomy['name']."',
                                                    'singular_name' => '".$taxonomy['singular_name']."',
                                                    'search_items' =>  '".$taxonomy['search_label']."',
                                                    'popular_items' => '".$taxonomy['popular_label']."',
                                                    'all_items' => '".$taxonomy['all_item_label']."',
                                                    'parent_item' => '".$taxonomy['parent_label']."',
                                                    'parent_item_colon' => '".$taxonomy['parent_colon_label']."',
                                                    'edit_item' => '".$taxonomy['edit_label']."', 
                                                    'update_item' => '".$taxonomy['update_label']."',
                                                    'add_new_item' => '".$taxonomy['add_new_label']."',
                                                    'new_item_name' => '".$taxonomy['new_label']."',
                                                    'add_or_remove_items' => '".$taxonomy['add_remove_label']."',
                                                    'choose_from_most_used' => '".$taxonomy['choose_most_used_label']."',
                                                    'menu_name' => '".$taxonomy['menu_name']."'
							),
                                            'public' => true,
                                            'query_var'=> true,
                                            'rewrite' => array('slug' => '".$taxonomy['slug']."')
                                       )
                    );
                    }
                    add_action('init','register_taxonomy_".$taxonomy['slug']."');";
            return $output;
        }
        public function generateCPT($cpt)
        {
            if($cpt['supports'])
            {
              $supports = stripcslashes(implode(",",$cpt['supports']));
            }
            $output = "function cpt_".$cpt['name']."(){
                       register_post_type('".$cpt['name']."',array(
                          'labels' => array(
                          'name' => '".$cpt['label']."',
                          'singular_name' => '".$cpt['label']."',
                          'add_new' => '".$cpt['add_new_label']."',
                          'add_new_item' => '".$cpt['add_new_item_label']."',
                          'edit' => '".$cpt['edit_label']."',
                          'edit_item' => '".$cpt['edit_item_label']."',    
                          'view_item' => '".$cpt['view_item_label']."',
                          'search_items' => '".$cpt['search_item_label']."',
                          'not_found' => '".$cpt['not_found_label']."',
                          'not_found_in_trash' => '".$cpt['not_found_trash_label']."',
                          'parent' => '".$cpt['parent_label']."',
                     ),
                     'public' => true,
                     'has_archive' => true,
                     'show_ui' => true,
                     'rewrite' => array( 'slug' => '".$cpt['name']."', 'with_front' => true ),
                     'exclude_from_search' => false,
                     'hierarchical' => true,
                     'supports' => array(".$supports."),
                     'query_var' => true
                  ));
                  }
            add_action('init','cpt_".$cpt['name']."');";
            return $output;
        }
        public function menu_page()
        {
            require_once 'admin/default.php';
        }
        public function activate_plugin()
        {  
            add_option('code_generator_version',$this->version);
        }
        
        public function deactivate_plugin()
        {
            delete_option('code_generator_version');
        }
    }
    
    $GLOBALS['CodeGenerator'] = new CodeGenerator();
}


//exit;
