<?php
if (!defined('ABSPATH')) {exit;}

/**
 * WC_Background_Process class.
 */
abstract class carrot_bunnycdn_incoom_plugin_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'background-process';

	/**
	 * Start time of current process
	 *
	 * @var int
	 */
	protected $start_time = 0;

	/**
	 * Default batch limit.
	 *
	 * @var int
	 */
	protected $limit = 50;

	/**
	 * Default chunk size.
	 *
	 * @var int
	 */
	protected $chunk = 5;

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	public function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 == $memory_limit ) {
			// Unlimited, set to 32GB
			$memory_limit = '32000M';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the a process never exceeds 90% of the maximum WordPress memory.
	 *
	 * @param null|string $filter_name Name of filter to apply to the return
	 *
	 * @return bool
	 */
	public function memory_exceeded( $filter_name = null ) {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		if ( is_null( $filter_name ) || ! is_string( $filter_name ) ) {
			return $return;
		}

		return apply_filters( $filter_name, $return );
	}

	/**
	 * Time exceeded
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( 'carrot_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->action . '_time_exceeded', $return );
	}

	public function can_run() {

		if(!carrot_bunnycdn_incoom_plugin_cronjob_timed()){
			return false;
		}

		if ( $this->memory_exceeded() ) {
			return false;
		}

		return true;
	}

	/**
	 * Complete
	 */
	public function complete() {
		
		carrot_bunnycdn_incoom_plugin_after_action_scheduler_completed();

		try{
			$sendEmail = get_option('incoom_carrot_bunnycdn_incoom_plugin_send_email_task', 'on');
			if(!empty($sendEmail)){
				wp_mail( 
					get_option('admin_email'), 
					esc_html__('carrot-bunnycdn-incoom-plugin Synchronize', 'carrot-bunnycdn-incoom-plugin'), 
					esc_html__('carrot-bunnycdn-incoom-plugin: process has been completed.', 'carrot-bunnycdn-incoom-plugin') 
				);
			}
		} catch (\Throwable $th) {}

		try {
			incoom_carrot_bunnycdn_incoom_plugin_delete_cache_item(carrot_bunnycdn_incoom_plugin_CACHE_KEY_MEDIA_COUNTS);
			incoom_carrot_bunnycdn_incoom_plugin_delete_cache_item(carrot_bunnycdn_incoom_plugin_CACHE_KEY_ATTACHMENT_COUNTS);
		} catch (\Throwable $th) {}
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		$this->start_time = time(); // Set start time of current process

		$lock_duration = apply_filters( $this->action . '_queue_lock_time', 60 );

		set_site_transient( $this->action . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected function unlock_process() {
		delete_site_transient( $this->action . '_process_lock' );
		return $this;
	}
}
?>