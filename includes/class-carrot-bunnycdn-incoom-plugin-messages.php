<?php
if (!defined('ABSPATH')) {exit;}

class carrot_bunnycdn_incoom_plugin_Messages {

    // Message types and shortcuts
    const INFO    = 'i';
    const SUCCESS = 's';
    const WARNING = 'w';
    const ERROR   = 'e';

	/**
	 * Error messages.
	 *
	 * @var array
	 */
	private static $errors = array();

	/**
	 * Update messages.
	 *
	 * @var array
	 */
	private static $messages = array();

	public static function init(){
		
		try {
			if (!session_id()){
				@session_start();
			}
		} catch (\Throwable $th) {}

		if(!isset($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY])){
			$_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY] = [];
		}

		add_action('admin_notices', array(__CLASS__, 'show_messages'));
	}
    
    /**
	 * Add a message.
	 *
	 * @param string $text Message.
	 */
	public static function add_message( $text ) {
		self::add(esc_html($text), self::INFO);
	}

	/**
	 * Add an error.
	 *
	 * @param string $text Message.
	 */
	public static function add_error( $text ) {
		self::add(esc_html($text), self::ERROR);
	}

	public static function add($message, $type = self::INFO) 
    {
		if (empty($message)) {
			return false;
		}

		if(!isset($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][$type])){
			$_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][$type] = [];
		}

		$_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][$type][] = $message;
	}

	/**
	 * Output messages + errors.
	 */
	public static function show_messages() {

		if (!isset($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY])){
			return false;
		}

		$errors = [];
		if(!empty($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][self::ERROR])){
			$errors = array_unique($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][self::ERROR]);
		}

		$messages = [];
		if(!empty($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][self::INFO])){
			$messages = array_unique($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY][self::INFO]);
		}

		if ( count( $errors ) > 0 ) {
			echo '<div class="error inline">';
				foreach ( $errors as $error ) {
					echo '<p><strong>' . esc_html( $error ) . '</strong></p>';
				}
			echo '</div>';
		} elseif ( count( $messages ) > 0 ) {
			echo '<div class="error inline">';
				foreach ( $messages as $message ) {
					echo '<p><strong>' . esc_html( $message ) . '</strong></p>';
				}
			echo '</div>';
		}else{
			if(isset($_POST['incoom_carrot_bunnycdn_settings_nonce'])){
				echo '
				<div class="updated settings-error notice is-dismissible">
					<p><strong>'. esc_html__( 'Settings saved.', 'carrot-bunnycdn-incoom-plugin' ) .'</strong></p>
				</div>
				';
			}
		}

		try {
			unset($_SESSION[carrot_bunnycdn_incoom_plugin_FLASH_MESSAGE_KEY]);
		} catch (\Throwable $th) {}
	}
}
