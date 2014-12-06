<?php
/*
Plugin Name: Add reCAPTCHA to comment form
Plugin URI: http://athanasiadis.me
Description: Add Google's reCAPTCHA to WordPress comment form
Version: 1.0
Author: Athanasiadis Evagelos
Author URI: http://athanasiadis.me
License: GPL2
*/
require_once "recaptchalib.php";


class Captcha_Comment_Form {
	/** @type string private key|public key */
	private $public_key, $private_key;
	/** @type  string captcha errors */
	private static $captcha_error;
	/** class constructor */
	public function __construct() {
		$this->public_key  = '6Le6d-USAAAAAFuYXiezgJh6rDaQFPKFEi84yfMc';
		$this->private_key = '6Le6d-USAAAAAKvV-30YdZbdl4DVmg_geKyUxF6b';
		$this->site_key = "6Lcm6f4SAAAAAGiao2I8Y9z9dtM3krk8ZJcfLL-7";
		$this->secret = "6Lcm6f4SAAAAAA-aOLeb8V-htg_n4Fjl-y877b7f";
		$this->resp=null;

		// adds the captcha to the comment form
		add_action( 'comment_form', array( $this, 'captcha_display' ) );
		// delete comment that fail the captcha challenge
		add_action( 'wp_head', array( $this, 'delete_failed_captcha_comment' ) );
		// authenticate the captcha answer
		add_filter( 'preprocess_comment', array( $this, 'validate_captcha_field' ) );
		// redirect location for comment
		add_filter( 'comment_post_redirect', array( $this, 'redirect_fail_captcha_comment' ), 10, 2 );
	}

	/** Output the reCAPTCHA form field. */
		public function captcha_display() {

			$reCaptcha = new ReCaptcha($this->secret);

			// Was there a reCAPTCHA response?
			if ($_POST["g-recaptcha-response"]) {
				$resp = $reCaptcha->verifyResponse(
					$_SERVER["REMOTE_ADDR"],
					$_POST["g-recaptcha-response"]
				);
			}



			if ( isset( $resp ) && $resp == 'empty' ) {
				echo '<strong>ERROR</strong>: CAPTCHA should not be emptymalaka';
			} elseif ( isset( $_GET['captcha'] ) && $_GET['captcha'] == 'failed' ) {
				echo '<strong>ERROR</strong>: CAPTCHA response was incorrect';
			}

		/**
		 * 'sitekey' : '6Lcm6f4SAAAAAGiao2I8Y9z9dtM3krk8ZJcfLL-7'
		 */

		echo <<<CAPTCHA_FORM
			<form action="?" method="post">
				<?php echo 'hello'; ?>
				<script type="text/javascript"
	        src="https://www.google.com/recaptcha/api.js?hl=<?php echo $lang;?>">
	</script>
	<div class="g-recaptcha" data-sitekey="<6Lcm6f4SAAAAAGiao2I8Y9z9dtM3krk8ZJcfLL-7"></div>
</form>




CAPTCHA_FORM;




	}

	/**
	 * Add query string to the comment redirect location
	 *
	 * @param $location string location to redirect to after comment
	 * @param $comment object comment object
	 *
	 * @return string
	 */
	function redirect_fail_captcha_comment( $location, $comment ) {
		if ( ! empty( self::$captcha_error ) ) {
			// replace #comment- at the end of $location with #commentform
			$args = array( 'comment-id' => $comment->comment_ID );
			if ( self::$captcha_error == 'captcha_empty' ) {
				$args['captcha'] = 'empty';
			} elseif ( self::$captcha_error == 'challenge_failed' ) {
				$args['captcha'] = 'failed';
			}
			$location = add_query_arg( $args, $location );
		}
		return $location;
	}
	/** Delete comment that fail the captcha test. */
	function delete_failed_captcha_comment() {
		if ( isset( $_GET['comment-id'] ) && ! empty( $_GET['comment-id'] ) ) {
			wp_delete_comment( absint( $_GET['comment-id'] ) );
		}
	}

	/**
	 * Verify the captcha answer
	 *
	 * @param $commentdata object comment object
	 *
	 * @return object
	 */
	public function validate_captcha_field( $commentdata ) {
		// if captcha is left empty, set the self::$captcha_error property to indicate so.
		if ( empty( $_POST['recaptcha_response_field'] ) ) {
			self::$captcha_error = 'captcha_empty';
		} // if captcha verification fail, set self::$captcha_error to indicate so
		elseif ( $this->recaptcha_response() == 'false' ) {
			self::$captcha_error = 'challenge_failed';
		}
		return $commentdata;
	}
	/**
	 * Get the reCAPTCHA API response.
	 *
	 * @return string
	 */
	public function recaptcha_response() {
		// reCAPTCHA challenge post data
		$challenge = isset( $_POST['recaptcha_challenge_field'] ) ? esc_attr( $_POST['recaptcha_challenge_field'] ) : '';
		// reCAPTCHA response post data
		$response = isset( $_POST['recaptcha_response_field'] ) ? esc_attr( $_POST['recaptcha_response_field'] ) : '';
		$remote_ip = $_SERVER["REMOTE_ADDR"];
		$post_body = array(
			'privatekey' => $this->private_key,
			'remoteip'   => $remote_ip,
			'challenge'  => $challenge,
			'response'   => $response
		);
		echo $response;
		return $this->recaptcha_post_request( $post_body );
	}

	/**
	 * Send HTTP POST request and return the response.
	 *
	 * @param $post_body array HTTP POST body
	 *
	 * @return bool
	 */
	public function recaptcha_post_request( $post_body ) {
		$args = array( 'body' => $post_body );
		// make a POST request to the Google reCaptcha Server
		$request = wp_remote_post( 'https://www.google.com/recaptcha/api/siteverify', $args );
		echo 'responseeeeeeeee';
		// get the request response body
		$response_body = wp_remote_retrieve_body( $request );
		/**
		 * explode the response body and use the request_status
		 * @see https://developers.google.com/recaptcha/docs/verify
		 */
		$answers = explode( "\n", $response_body );
		$request_status = trim( $answers[0] );
		return $request_status;
	}
}
new Captcha_Comment_Form();