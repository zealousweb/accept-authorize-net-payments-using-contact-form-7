<?php
/**
 * CF7ADN Class
 *
 * Handles the plugin functionality.
 *
 * @package WordPress
 * @subpackage Accept Authorize.NET Payments Using Contact Form 7
 * @since 1.2
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( !class_exists( 'CF7ADN' ) ) {

	/**
	 * The main CF7ADN class
	 */
	class CF7ADN {

		private static $_instance = null;
		private static $private_data = null;

		var $admin = null,
		    $front = null,
		    $lib   = null;

		public static function instance() {

			if ( is_null( self::$_instance ) )
				self::$_instance = new self();

			return self::$_instance;
		}

		function __construct() {
			add_action( 'init', array( $this, 'action__init' ) );
			add_action( 'setup_theme', array( $this, 'action__setup_theme' ) );
			add_action( 'plugins_loaded',array( $this, 'action__plugins_loaded' ), 1 );
		}

		function action__plugins_loaded() {

			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			if ( !is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) ) {
				add_action( 'admin_notices', array( $this, 'action__admin_notices_deactive' ) );
				deactivate_plugins( CF7ADN_PLUGIN_BASENAME );
			}


			// Action to load plugin text domain
			add_action( 'init', array( $this, 'action__init' ) );

			global $wp_version;

			// Set filter for plugin's languages directory
			$cf7adn_lang_dir = dirname( CF7ADN_PLUGIN_BASENAME ) . '/languages/';
			$cf7adn_lang_dir = apply_filters( 'cf7adn_languages_directory', $cf7adn_lang_dir );

			// Traditional WordPress plugin locale filter.
			$get_locale = get_locale();

			if ( $wp_version >= 4.7 ) {
				$get_locale = get_user_locale();
			}

			// Traditional WordPress plugin locale filter
			$locale = apply_filters( 'plugin_locale',  $get_locale, 'accept-authorize-net-payments-using-contact-form-7' );
			$mofile = sprintf( '%1$s-%2$s.mo', 'accept-authorize-net-payments-using-contact-form-7', $locale );

			// Setup paths to current locale file
			$mofile_global = WP_LANG_DIR . '/plugins/' . basename( CF7ADN_DIR ) . '/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/plugin-name folder
				load_textdomain( 'accept-authorize-net-payments-using-contact-form-7', $mofile_global );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'accept-authorize-net-payments-using-contact-form-7', false, $cf7adn_lang_dir );
			}


		}

		function action__setup_theme() {

			if ( is_admin() ) {
				CF7ADN()->admin = new CF7ADN_Admin;
				CF7ADN()->admin->action = new CF7ADN_Admin_Action;
				CF7ADN()->admin->filter = new CF7ADN_Admin_Filter;
			} else {
				CF7ADN()->front = new CF7ADN_Front;
				CF7ADN()->front->action = new CF7ADN_Front_Action;
				CF7ADN()->front->filter = new CF7ADN_Front_Filter;
			}

			CF7ADN()->lib = new CF7ADN_Lib;

		}

		function action__wpcf7_admin_init() {
			$tag_generator = WPCF7_TagGenerator::get_instance();
			$tag_generator->add(
				'authorize',
				__( 'Authorize.Net', 'accept-authorize-net-payments-using-contact-form-7' ),
				array( $this, 'wpcf7_tag_generator_authorize_net' )
			);
		}

		/**
		 * Load Text Domain
		 * This gets the plugin ready for translation
		 */
		function action__init() {

			/* Initialize backend tags */
			add_action( 'wpcf7_admin_init',        array( $this, 'action__wpcf7_admin_init' ), 15, 0 );
			add_action('wp_ajax_cf7_cf7adn_validation',        array( $this, 'ajax__cf7_cf7adn_validation' ) );
			add_action('wp_ajax_nopriv_cf7_cf7adn_validation', array( $this, 'ajax__cf7_cf7adn_validation' ) );

			add_rewrite_rule( '^cf7adn-phpinfo(/(.*))?/?$', 'index.php?cf7adn-phpinfo=$matches[2]', 'top' );
			flush_rewrite_rules(); //phpcs:ignore

			/**
			 * Post Type: Authorize.Net Add-on.
			 */
			$labels = array(
				'name' => __( 'Authorize.Net Add-on', 'accept-authorize-net-payments-using-contact-form-7' ),
				'singular_name' => __( 'Authorize.Net Add-on', 'accept-authorize-net-payments-using-contact-form-7' ),
			);

			$args = array(
				'label' => __( 'Authorize.Net Add-on', 'accept-authorize-net-payments-using-contact-form-7' ),
				'labels' => $labels,
				'description' => '',
				'public' => false,
				'publicly_queryable' => false,
				'show_ui' => true,
				'delete_with_user' => false,
				'show_in_rest' => false,
				'rest_base' => '',
				'has_archive' => false,
				'show_in_menu' => 'wpcf7',
				'show_in_nav_menus' => false,
				'exclude_from_search' => true,
				'capability_type' => 'post',
				'capabilities' => array(
					'read' => true,
					'create_posts'  => false,
					'publish_posts' => false,
				),
				'map_meta_cap' => true,
				'hierarchical' => false,
				'rewrite' => false,
				'query_var' => false,
				'supports' => array( 'title' ),
			);

			register_post_type( 'cf7adn_data', $args );
		}

		function action__admin_notices_deactive() {
			echo '<div class="error">' .
				'<p>' .
					sprintf(
						/* translators: Contact Form 7 - Authorize.NET Add-on */
						__( '<p><strong><a href="https://wordpress.org/plugins/contact-form-7/" target="_blank">Contact Form 7</a></strong> is required to use <strong>%s</strong>.</p>', 'contact-form-7-paypal-extension' ), //phpcs:ignore
						'Contact Form 7 - Authorize.NET Add-on'
					) .
				'</p>' .
			'</div>';
		}

		function ajax__cf7_cf7adn_validation() {
			global $wpdb;
			if ( isset( $_POST[ '_wpcf7' ] ) ) {  //phpcs:ignore

				$id = intval($_POST[ '_wpcf7' ]);  //phpcs:ignore

				$unit_tag = wpcf7_sanitize_unit_tag( $_POST[ '_wpcf7_unit_tag' ] );  //phpcs:ignore

				$spam = false;

				if ( $contact_form = wpcf7_contact_form( $id ) ) {

					if ( WPCF7_VERIFY_NONCE && ! wpcf7_verify_nonce( sanitize_text_field($_POST['_wpnonce']), $contact_form->id() ) ) { //phpcs:ignore
						$spam = true;						
						exit( esc_html__( 'Spam detected') );
					} else {
						$items = array(
							'mailSent' => false,
							'into' => '#' . $unit_tag,
							'captcha' => null
						);

						/* Begin validation */
						require_once WPCF7_PLUGIN_DIR . '/includes/validation.php';
						$result = new WPCF7_Validation();

						$tags = $contact_form->scan_form_tags();
						$args = array();
						foreach ( $tags as $tag ) {
							if($tag->basetype == 'file'){
								$result = apply_filters( 'wpcf7_validate_' . $tag[ 'type' ], $result, $tag, $args );
							}else{
								$result = apply_filters( 'wpcf7_validate_' . $tag[ 'type' ], $result, $tag );
							}
						}

						$result = apply_filters( 'wpcf7_validate', $result, $tags );

						$invalid_fields = $result->get_invalid_fields();
						$return = array( 'success' => $result->is_valid(), 'invalid_fields' => $invalid_fields );

						if ( $return[ 'success' ] == false ) {
							$messages = $contact_form->prop( 'messages' );
							$return[ 'message' ] = $messages[ 'validation_error' ];

							if ( empty( $return[ 'message' ] ) ) {
								$default_messages = wpcf7_messages();
								$return[ 'message' ] = $default_messages[ 'validation_error' ][ 'default' ];
							}
						} else {
							$return[ 'message' ] = '';
						}

						$json = wp_json_encode( $return );
						exit( esc_js( $json ) );
					}
				}
			}
		}

		/*
		######## #### ##       ######## ######## ########   ######
		##        ##  ##          ##    ##       ##     ## ##    ##
		##        ##  ##          ##    ##       ##     ## ##
		######    ##  ##          ##    ######   ########   ######
		##        ##  ##          ##    ##       ##   ##         ##
		##        ##  ##          ##    ##       ##    ##  ##    ##
		##       #### ########    ##    ######## ##     ##  ######
		*/



		/*
		######## ##     ## ##    ##  ######  ######## ####  #######  ##    ##  ######
		##       ##     ## ###   ## ##    ##    ##     ##  ##     ## ###   ## ##    ##
		##       ##     ## ####  ## ##          ##     ##  ##     ## ####  ## ##
		######   ##     ## ## ## ## ##          ##     ##  ##     ## ## ## ##  ######
		##       ##     ## ##  #### ##          ##     ##  ##     ## ##  ####       ##
		##       ##     ## ##   ### ##    ##    ##     ##  ##     ## ##   ### ##    ##
		##        #######  ##    ##  ######     ##    ####  #######  ##    ##  ######
		*/

		/**
		 * -Render CF7 Shortcode settings into backend.
		 *
		 * @method wpcf7_tag_generator_authorize_net
		 *
		 * @param  object $contact_form
		 * @param  array  $args
		 */
		function wpcf7_tag_generator_authorize_net( $contact_form, $args = '' ) {

			$args = wp_parse_args( $args, array() );
			$type = $args['id'];

			$description = __( "Generate a form-tag for to display Authorize.Net payment form", 'accept-authorize-net-payments-using-contact-form-7' );
			?>
			<div class="control-box">
				<fieldset>
					<legend><?php echo esc_html( $description ); ?></legend>

					<table class="form-table">
						<tbody>
							<tr>
							<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'accept-authorize-net-payments-using-contact-form-7' ) ); ?></label></th>
							<td>
								<legend class="screen-reader-text"><input type="checkbox" name="required" value="on" checked="checked" /></legend>
								<input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
							</tr>

							<tr>
								<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Button Name', 'accept-authorize-net-payments-using-contact-form-7' ) ); ?></label></th>
								<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" value="<?php esc_html( 'Make Payment', 'accept-authorize-net-payments-using-contact-form-7' ) ?>" /></td>
							</tr>

						</tbody>
					</table>
				</fieldset>
			</div>

			<div class="insert-box">
				<input type="text" name="<?php echo esc_attr( $type ); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

				<div class="submitbox">
					<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'accept-authorize-net-payments-using-contact-form-7' ) ); ?>" />
				</div>

				<br class="clear" />

				<p class="description mail-tag">
					<label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>">
						<?php echo sprintf( esc_html("To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'accept-authorize-net-payments-using-contact-form-7'), '<strong><span class="mail-tag"></span></strong>' ); ?>
						<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" />
					</label>
				</p>
			</div>
			<?php

		}

	}
}

function CF7ADN() {
	return CF7ADN::instance();
}

CF7ADN();
