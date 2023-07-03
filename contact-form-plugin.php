<?php
/*
Plugin Name: Maplesyrup Web Contact Form
Plugin URI: https://maplesyrupweb.com
Description: A simple contact form for WordPress with options for the user to save to databse, comments, or email.
Author: Maple Syrup Web
Author URI: https://maplesyrupweb.com
Text Domain: Example Contact Form
Domain Path: /lang
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

 // include plugin dependencies

 if ( is_admin() ) {
 /*
    require_once plugin_dir_path( __FILE__ ) . 'admin/admin-menu.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-page.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-callbacks.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-register.php';
    require_once plugin_dir_path( __FILE__ ) . 'admin/settings-validate.php';
 */   
    }

// include dependencies: admin and public

// require_once plugin_dir_path( __FILE__ ) . 'includes/core-functions.php';



class ContactFormPlugin {

    function __construct() {

        global $wpdb;
        $this->charset = $wpdb->get_charset_collate();
        $this->tablename = $wpdb->prefix . "contact_form_submissions";
        //runs when the plugin is activated
        add_action('activate_wp-plugin-contact-form/contact-form-plugin.php', array($this, 'onActivate'));

        add_action('admin_menu', array($this, 'adminPage')); 
        add_action('admin_init', array($this, 'settings'));
        add_shortcode('contact_form', array($this, 'contact_form_plugin'));
        add_action('wp_head', array($this, 'maplesyrupweb_form_capture'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_css'));
        add_action('wp_enqueue_scripts', array($this, 'plugin_js'));
        
    }

    /**
     *   Add an options page to the admin dashboard
     */

    function adminPage() {
        
        //($page_title, $menu_title, $capability, $menu_slug, $function = '', $position = \null)
        add_options_page('Contact Form Settings Page', 'Contact Form', 'manage_options', 'contact-form-settings', array($this, 'ourHTML'));
        }
    
    /**
     *  Display the form on the setiings page
     */
    
    function ourHTML() { ?>

        <!-- To do:  add additional setting groups and pages -->

        <div class="wrap">
            <h1>Contact Form Settings</h1>
            <form action="options.php" method="POST" >
            <?php
                //same name used in the register_setting function.
                settings_fields( 'contactform-group' );
                // replaces the form-field markup in the form itself. 
                do_settings_sections( 'contact-form-settings-page' );
                submit_button(); 
            ?>
            </form>
        </div>

    <?php }

    /**
     *  Each of the settings on the settings page
     */

    function settings() {

        // $id, $title, $callback, $page
        add_settings_section('contact_form_settings', null, null, 'contact-form-settings-page');
        
        // ($page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = \null)
        // add_menu_page('Contact Form Settings', 'Contact Form Settings', 'manage_options', 'contact-form-settings', 'ourHTML',  'dashicons-email');
        

        //add_settings_field  $id, $title, $callback, $page, $section = 'default' 
        add_settings_field('contact_form_headline','Title of contact form', array($this,'headlineHTML') ,'contact-form-settings-page','contact_form_settings');
        //register_setting ($option_group, $option_name, $args = array())
        register_setting('contactform-group','contact_form_headline',array('santize_callback'=> 'santizie_text_field', 'default' => 'URL'));

        add_settings_field('contact_form_description', 'Form Description', array($this, 'formDescription'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','contact_form_description',array('santize_callback'=> 
        'santizie_text_field', 'default' => 'Instructions for the form'));

        add_settings_field('send_to_email', 'Where the contact form will send to', array($this, 'sendToEmailHTML'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','send_to_email',array('santize_callback'=> 
        'santizie_text_field', 'default' => 'Please enter the email'));

        add_settings_field('submit_to_database','Submit to Database', array($this,'checkBoxHTML') ,'contact-form-settings-page','contact_form_settings', array('contactFormArray'=> 'submit_to_database'));
        //register_setting ($option_group, $option_name, $args = array())
        register_setting('contactform-group','submit_to_database',array('santize_callback'=> 'santizie_text_field', 'default' => '1'));
        
        add_settings_field('submit_to_email','Submit to email', array($this,'checkBoxHTML') ,'contact-form-settings-page','contact_form_settings',array('contactFormArray' =>'submit_to_email'));
        register_setting('contactform-group','submit_to_email',array('santize_callback'=> 'santizie_text_field', 'default' => '1'));

        add_settings_field('submit_to_comments','Submit to comments', array($this,'checkBoxHTML') ,'contact-form-settings-page','contact_form_settings',array('contactFormArray' =>'submit_to_comments'));
        register_setting('contactform-group','submit_to_comments',array('santize_callback'=> 'santizie_text_field', 'default' => '1'));

        //Comments are approved or pending
        add_settings_field('comment_choice','Comments are pending or approved', array($this,'commentChoiceHTML') ,'contact-form-settings-page','contact_form_settings');
        register_setting('contactform-group','comment_choice', array('santize_callback'=> array($this, 'sanitizedLocation'),'default' => '0'));

        //Label for name field
        add_settings_field('contact_form_name_label', 'Label for name field', array($this, 'formNameLabel'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','contact_form_name_label',array('santize_callback'=> 
        'santizie_text_field', 'default' => 'Please type your name below'));

        //Label for email field
        add_settings_field('contact_form_email_field', 'Label for email field', array($this, 'formEmailLabel'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','contact_form_email_field',array('santize_callback'=> 
        'santizie_text_field', 'default' => 'Please type your email below'));

        //Label for message field
        add_settings_field('contact_form_message_field', 'Label for message field', array($this, 'formMessageLabel'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','contact_form_message_field',array('santize_callback'=> 
        'santizie_text_field', 'default' => 'Please type your message below'));

        //Label for phone field
        add_settings_field('contact_form_phone_field', 'Label for phone field', array($this, 'formPhoneLabel'), 'contact-form-settings-page', 'contact_form_settings');
        register_setting('contactform-group','contact_form_phone_field',array('santiphoneallback'=> 
        'santizie_text_field', 'default' => 'Please type your message below'));

   
}
    /**
     *  Display the form to input the form description
     */

    function formDescription() { ?>

        
        <input type="textarea" size="40" name="contact_form_description" value="<?php echo esc_attr(get_option('contact_form_description'));?>">
    
    <?php }

    /**
     *  Display the label for form name field
     */

    function formNameLabel() { ?>

        
        
        <input type="text" name="contact_form_name_label" value="<?php echo esc_attr(get_option('contact_form_name_label'));?>">
        
    <?php }


    /**
     *  Display the label for form message field
     */


    function formMessageLabel() { ?>

            
            
        <input type="text" name="contact_form_message_field" value="<?php echo esc_attr(get_option('contact_form_message_field'));?>">
        
    <?php }


    /**
     *  Display the label for form email field
     */

    function formEmailLabel() { ?>

        
        
        <input type="text" name="contact_form_email_field" value="<?php echo esc_attr(get_option('contact_form_email_field'));?>">
        
    <?php }

    /**
     *  Display the label for form phone field
     */

    function formPhoneLabel() { ?>

        
        
        <input type="text" name="contact_form_phone_field" value="<?php echo esc_attr(get_option('contact_form_phone_field'));?>">
        
    <?php }


    /**
     *  The title of the contact form for the user to input
     */

    function headlineHTML() { ?>

    <!-- name must match first argument of add_settings_field for headlineHTML -->
        <input type="text" name="contact_form_headline" value="<?php echo esc_attr(get_option('contact_form_headline'));?>">

    <?php }

    /**
     *  Where the contact form will send to
     */

    function sendToEmailHTML() { ?>

        
            <input type="text" name="send_to_email" value="<?php echo esc_attr(get_option('send_to_email'));?>">
    
        <?php }
    

    /**
     *   Return the contact form
     */

    function contact_form_plugin() {
    
        $content = '';
        $content .= '<h4>' . get_option('contact_form_headline') . '</h4>';
        $content .= '<h5>' . get_option('contact_form_description') . '</h5>';
        // use site_url() so the adress is not hardcoded
        $content .= '<form name="contact-form" method="post" action="' .site_url() . '/thank-you">';
        // $content .= '<form name="contact-form" method="post" action="'.plugin_dir_url(__FILE__).'process/" enctype="multipart/form-data">';

        //generate the HTML for the form
        $content .= '<br><div class="container form-container">';
        $content .= '<label for="full_name">' . get_option('contact_form_name_label') . '</label><br>';
        $content .= '<input type="text" class="form-control" size="40" name="full_name" id="full_name" placeholder="Name"/><br/>';
        $content .= '<span class="error" aria-live="polite"></span>';

        $content .= '<br /><br />';
        //Email
        $content .= '<label for="email_address">' . get_option('contact_form_email_field') . '</label><br/>';
        $content .= '<input type="email"  class="form-control" size="40" name="email_address" id="email_address" placeholder="Email"/><br/>';
        $content .= '<span class="error" aria-live="polite"></span></label>';
        $content .= '<br /><br />';
        //Phone
        $content .= '<label for="phone_number">' . get_option('contact_form_phone_field') . '</label><br>';
        $content .= '<input type="text" class="form-control" name="phone_number" id="phone_number" size="40" placeholder="111-222-3333" maxlength="20" /><br/>';
        $content .= '<span class="error" aria-live="polite"></span>';
        $content .= '<br /><br />';
        //Message
        $content .= '<label for="your_message">' . get_option('contact_form_message_field') . '</label><br>';
        $content .= '<textarea name="your_message" id="your_message" class="form-control" placeholder="Message" rows="4" cols="50" maxlength="50"></textarea><br/>';
        $content .= '<span class="error" aria-live="polite"></span>';
        $content .= '<br /><br />';
        //File Upload
        $content .= '<input type="file" name="my_file_upload" /><br/><br/>';
        $content .= '<input type="submit" name="maplesyrupweb_submit_form" class="btn btn-primary" value="SUBMIT" />';
        $content .= '<br /><br />';
        $content .= '</form></div>';
        return $content;
}

    /**
     *  Used for the contact form to send a form submission as HTML instead
     */

    function textHTMLContent()
    {
        return 'text/html';

    }

    /**
     *  Process the form submission.  If selected, submit to comments, database, and email.
     */

    function maplesyrupweb_form_capture()
    {
        // know which post/page you are on
        global $post; 
        // WordPress databse object
        global $wpdb;

        //must match the name of submit button
        if(array_key_exists('maplesyrupweb_submit_form',$_POST))
        {
            
            if (get_option('submit_to_email', '1')) {

            // the subject of the email
            $to = get_option('send_to_email');
            $subject = "Maplesyrup Web Form Submission";
            $body = '';

            $theName = sanitize_text_field($_POST['full_name']);
            $theEmail = sanitize_text_field($_POST['email_address']);
            $thePhone = sanitize_text_field($_POST['phone_number']);
            $theMessage = sanitize_textarea_field($_POST['your_message']);
            $headers = 'From: '.$theName.' <'.$theEmail.'>' . "\r\n" . 'Reply-To: ' . $theEmail;
            wp_mail($to, $subject, $theMessage, $headers);

            }
            // remove_filter('wp_mail_content_type','textHTMLContent');

            /* Form submission to comments in the admin dashbaord */
            $data = null;
            if (get_option('submit_to_comments', '1'))  {

                $body .= 'Name: '.$theName.' <br /> ';
                $body .= 'Email: '.$theEmail.' <br /> ';
                $body .= 'Phone: '.$thePhone. ' <br /> ';
                $body .= 'Message: '.$theMessage.' <br /> ';
                $theDateTime = current_time('mysql');

                if (get_option('comment_choice', '0') == '0') {
                
                    $data = array(
                        'comment_post_ID' => $post->ID,
                        'comment_content' => $body, 
                        //user IP address
                        'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                        'comment_date' => $theDateTime,
                        'comment_approved' => 0,  //pending
                );
                    wp_insert_comment($data);
                }

                else if (get_option('comment_choice', '1') == '1') {
                
                    $data = array(
                        'comment_post_ID' => $post->ID,
                        'comment_content' => $body, 
                        //user IP address
                        'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                        'comment_date' => $theDateTime,
                        'comment_approved' => 1, //approved
                        
                );
                    wp_insert_comment($data);
                }
            }

            // To do
            // 1. use JavaScript on front end and 
            // 2. PHP on back end to ensure the data submitted is valid

            if (get_option('submit_to_database', '1')) {

                $thedatetime = current_datetime('mysql');
                $stringDate = $thedatetime->format('Y-m-d H:i:s');
                $tablename = $wpdb->prefix."contact_form_submissions";
                // Use MySQL prepared statements
                $sql = $wpdb->prepare("INSERT INTO $tablename (`theDateTime`, `theName`, `theEmail`, `thePhone`, `theMessage`) values (%s, %s, %s, %s,%s)", $stringDate, $theName, $theEmail, $thePhone, $theMessage); 
                $result = $wpdb->query($sql);

                //Add sucess message below the form with div continaer
                // if ($result === FALSE) {
                //     echo '<div class="failed"></div>';
                // } else {
                //     echo '<div class="success">></div>';
                // }

            }
     
    }

}
    /**
     *  Load the CSS file for the plugin
     */

    function plugin_css() {
        
        //load style.css for this plugin
        $plugin_url  = plugin_dir_url( __FILE__ );
        wp_enqueue_style('msw_contact_form_style', $plugin_url . 'css/style.css');    
    }

    /**
     *  Load the JS file for the plugin
     */

    function plugin_js() {

        //load script.css for this plugin
        $plugin_url  = plugin_dir_url( __FILE__ );
        wp_enqueue_script('msw_contact_form_script', $plugin_url . 'js/script.js');    
        
        }

    /**
     *  The form for the option so submit the contact form to the database.  Not needed anymore.  Using checkBOXHTML to remove repeated   *  code.
     */
    
    function submitToDatabaseHTML() { ?>
    <!-- Produce the checkbox to allow user to save option to database -->

        <input type="checkbox" name="submit_to_database" value="1" <?php checked(get_option('submit_to_database'),'1'); ?>>

    <?php }

    /**
     *  Used for submitting to database, submitting to comments, and submitting to email checkboxes.  
     */

    function checkboxHTML($args) { ?>
        <input type="checkbox" name="<?php echo $args['contactFormArray'] ?>" value="1" <?php checked(get_option($args['contactFormArray']), '1') ?>>
      <?php }
    

    /**
     *  The form for the option to submit the form to WordPress's comments. Not needed anymore.  Using checkBOXHTML to remove repeated   *  code.
     */

    function submitToCommentsHTML() { ?>
    <!-- Produce the checkbox to allow user to save to comments -->

        <input type="checkbox" name="submit_to_comments" value="1" <?php checked(get_option('submit_to_comments'),'1'); ?>>

    <?php }

    /**
     *  The form for the option to submit to email. Not needed anymore.  Using checkBOXHTML to remove repeated code.
     */

    function submitToEmailHTML() { ?>
    
        
        <input type="checkbox" name="submit_to_email" value="1" <?php checked(get_option('submit_to_email'),'1'); ?>>
        
    <?php }

    /**
     * Create the table on activation of the plugin
     *
     */

    function onActivate() {

        // this is required to run dbDelta
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta("CREATE TABLE $this->tablename (
          ID int(11) unsigned NOT NULL AUTO_INCREMENT,
          theDateTime datetime NULL, 
          theName text NOT NULL DEFAULT '',
          theEmail text NOT NULL DEFAULT '',
          thePhone text NOT NULL DEFAULT '',
          theMessage longtext NOT NULL DEFAULT '',
          PRIMARY KEY  (id)
        ) $this->charset;");
    
      }
    
    /**
     *  The form for the option to submit to Comments as either pending or approved
     */

    function commentChoiceHTML() { ?>
        <!-- name must match first parameter of add_ettings_field -->
        <select name="comment_choice">
            <option value="0" <?php selected(get_option('comment_choice'),'0') ?>>Pending</option>
            <option value="1" <?php selected(get_option('comment_choice'),'1') ?>>Approved</option>
        </select>
    
    <?php }

    /**
     *  Sanitize the input for comments form
     */
    
    function sanitizedLocation($input) {

        if ($input != '0' AND $input != '1') {
    
            //$setting, $code, $message, $type = 'error'
            add_settings_error('comment_choice','comment_choice_error', 'The choice must be pending or approved approved');
            //return old value from databse
            return get_option('comment_choice');
        // if there are no errors, return the value that was passed
        return $input;
        }
    }    

 


}
        
$contactFormPlugin = new ContactFormPlugin();
