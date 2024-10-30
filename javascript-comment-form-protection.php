<?php
/*
Plugin Name: Javascript Comment Form Protection
Plugin URI: http://wordpress.org/extend/plugins/javascript-comment-form-protection/
Version: 0.6
Author: Greg Molloy
Description: Simple script to stop automated bots filling out comments now with password protection and encryption.
Author URI: https://www.3zero.co.uk/
Text Domain: javascript-comment-form-protection
License: GPL3
*/

add_action( 'wp', 'encrypt' );
function encrypt($string, $key) 
{
   $result = '';
   for($i=0; $i<strlen($string); $i++) {
     $char = substr($string, $i, 1);
     $keychar = substr($key, ($i % strlen($key))-1, 1);
     $char = chr(ord($char)+ord($keychar));
     $result.=$char;
   }
   return base64_encode($result);
}


add_action('wp_insert_comment','my_function',100,2);

function my_function($comment_id, $comment_object) {
    // Now you have access to $comment_id, save it, print it, do whatever you want with it
    $test=$comment_id;
}




function after_comment_form_submit_button( $submit_button, $args ) {

$password = get_option('formpassword');
if (empty($password)) {
    $password = $_SERVER['HTTP_HOST'];    
}

$data =  get_comment_id_fields(); 
$whatIWant = substr($data, strpos($data, "value") + strlen("value"), 12);    
$whatIWant = (preg_replace('/[^0-9]/', '', $whatIWant));
$stopbots = encrypt($whatIWant, "$password");
 $submit = sprintf(
  $args['submit_button'],
 esc_attr( $args['name_submit'] ),
    esc_attr( $args['id_submit'] ),
    esc_attr( $args['class_submit'] ),
    esc_attr( $args['label_submit'] )
);

    $comment_id = get_comment_ID();
    $submit_button = "<input id=\"verify\" name=\"verify\" type=\"hidden\" value=\"\" />$submit";
    $after_submit =  "
<script>
function protectForm() {
oFormObject = document.forms['commentform'];
oFormObject.elements[\"verify\"].value = '$stopbots';
}
window.onload = protectForm;
</script>
<noscript>THIS FORM REQUIRES JAVASCRIPT ENABLED</noscript>";
    return  $submit_button . $after_submit;
};


add_filter( 'comment_form_submit_button', 'after_comment_form_submit_button', 10, 2 );








function custom_validate_verify() {
    
$password = get_option('formpassword');
if (empty($password)) {
    $password = $_SERVER['HTTP_HOST'];    
}    
$formid = $_POST['verify'];
$verifyid = encrypt($_POST['comment_post_ID'], "$password");

    if(( $formid != $verifyid )) // do you url validation here (I am not a regex expert)
        wp_die( __( "Error: Your comment could not be verified. Are you sure your not a robot?.") );
}
add_action('pre_comment_on_post', 'custom_validate_verify');



add_action('admin_menu', 'add_global_custom_options');

function add_global_custom_options()    
{  
    add_options_page('Javascript Comment Form Protection', 'Comment Form Protection Password', 'manage_options', 'functions','global_custom_options');  
}

function global_custom_options()
{
?>
    <div class="wrap">
        <h2>Protection Password</h2>
        <form method="post" action="options.php">
            <?php wp_nonce_field('update-options') ?>
            <p><strong>Enter A Password To Protect Your Form</strong><br />
                            <input type="text" name="formpassword" size="45" value="<?php echo get_option('formpassword'); ?>" />
            </p>
            <p><input type="submit" name="Submit" value="Store Password" /></p>
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="page_options" value="formpassword" />
        </form>
    </div>
<?php
}

add_filter( 'plugin_row_meta', 'custom_plugin_row_meta', 10, 2 );

function custom_plugin_row_meta( $links, $file ) {

	if ( strpos( $file, 'javascript-comment-form-protection.php' ) !== false ) {
		$new_links = array(
				'donate' => '<a href="https://www.paypal.me/3zero" target="_blank">Donate</a>'
				);
		
		$links = array_merge( $links, $new_links );
	}
	
	return $links;
}


?>