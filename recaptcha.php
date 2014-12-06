
<?php

/*
Plugin Name: Add reCAPTCHA to comment form
Plugin URI: http://sitepoint.com
Description: Add Google's reCAPTCHA to WordPress comment form
Version: 1.0
Author: Agbonghama Collins
Author URI: http://w3guy.com
License: GPL2
*/

class Captcha_comment_Form {
    /** @type string private key|public key */
    private $public_key, $private_key;

    /** @type string captcha errors */
    private static $captcha_error;

    /** class construction */
    public function __construct() {
        $this->public_key  = '6Le6d-USAAAAAFuYXiezgJh6rDaQFPKFEi84yfMc';
        $this->private_key = '6Le6d-USAAAAAKvV-30YdZbdl4DVmg_geKyUxF6b';

        // adds the captcha to the WordPress form
        add_action('comment_form',array($this,'captcha_display'));

        
    }
}