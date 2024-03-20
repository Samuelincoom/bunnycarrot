<?php

/**
 * Class ActionScheduler_AdminView
 * @codeCoverageIgnore
 */
class ActionScheduler_AdminView extends ActionScheduler_AdminView_Deprecated {

	private static $admin_view = NULL;

	private static $screen_id = 'tools_page_action-scheduler';

	/** @var ActionScheduler_ListTable */
	protected $list_table;

	/**
	 * @return ActionScheduler_AdminView
	 * @codeCoverageIgnore
	 */
	public static function instance() {

		if ( empty( self::$admin_view ) ) {
			$class = apply_filters('action_scheduler_admin_view_class', 'ActionScheduler_AdminView');
			self::$admin_view = new $class();
		}

		return self::$admin_view;
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function init() {
		if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || false == DOING_AJAX ) ) {

			add_action( 'carrot_admin_status_content_action-scheduler', array( $this, 'render_admin_ui' ) );
			add_action( 'carrot_system_status_report', array( $this, 'system_status_report' ) );
			add_filter( 'carrot_admin_status_tabs', array( $this, 'register_system_status_tab' ) );

			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_notices', array( $this, 'maybe_check_pastdue_actions' ) );
			add_action( 'current_screen', array( $this, 'add_help_tabs' ) );
		}
	}

	public function system_status_report() {
		$table = new ActionScheduler_wcSystemStatus( ActionScheduler::store() );
		$table->render();
	}

	/**
	 * Registers action-scheduler into WooCommerce > System status.
	 *
	 * @param array $tabs An associative array of tab key => label.
	 * @return array $tabs An associative array of tab key => label, including Action Scheduler's tabs
	 */
	public function register_system_status_tab( array $tabs ) {
		$tabs['action-scheduler'] = __( 'Scheduled Actions','carrot-bunnycdn-incoom-plugin' );

		return $tabs;
	}

	/**
	 * Include Action Scheduler's administration under the Tools menu.
	 *
	 * A menu under the Tools menu is important for backward compatibility (as that's
	 * where it started), and also provides more convenient access than the WooCommerce
	 * System Status page, and for sites where WooCommerce isn't active.
	 */
	public function register_menu() {
		$hook_suffix = add_submenu_page(
			'tools.php',
			__( 'Scheduled Actions','carrot-bunnycdn-incoom-plugin' ),
			__( 'Scheduled Actions','carrot-bunnycdn-incoom-plugin' ),
			'manage_options',
			'action-scheduler',
			array( $this, 'render_admin_ui' )
		);
		add_action( 'load-' . $hook_suffix , array( $this, 'process_admin_ui' ) );
	}

	/**
	 * Triggers processing of any pending actions.
	 */
	public function process_admin_ui() {
		$this->get_list_table();
	}

	/**
	 * Renders the Admin UI
	 */
	public function render_admin_ui() {
		$table = $this->get_list_table();
		$table->display_page();
	}

	/**
	 * Get the admin UI object and process any requested actions.
	 *
	 * @return ActionScheduler_ListTable
	 */
	protected function get_list_table() {
		if ( null === $this->list_table ) {
			$this->list_table = new ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner() );
			$this->list_table->process_actions();
		}

		return $this->list_table;
	}

	/**
	 * Action: admin_notices
	 *
	 * Maybe check past-due actions, and print notice.
	 *
	 * @uses $this->check_pastdue_actions()
	 */
	public function maybe_check_pastdue_actions() {

		# Filter to prevent checking actions (ex: inappropriate user).
		if ( ! apply_filters( 'action_scheduler_check_pastdue_actions', current_user_can( 'manage_options' ) ) ) {
			return;
		}

		# Get last check transient.
		$last_check = get_transient( 'action_scheduler_last_pastdue_actions_check' );

		# If transient exists, we're within interval, so bail.
		if ( ! empty( $last_check ) ) {
			return;
		}

		# Perform the check.
		$this->check_pastdue_actions();
	}

	/**
	 * Check past-due actions, and print notice.
	 *
	 * @todo update $link_url to "Past-due" filter when released (see issue #510, PR #511)
	 */
	protected function check_pastdue_actions() {

		# Set thresholds.
		$threshold_seconds = ( int ) apply_filters( 'action_scheduler_pastdue_actions_seconds', DAY_IN_SECONDS );
		$threshhold_min    = ( int ) apply_filters( 'action_scheduler_pastdue_actions_min', 1 );

		# Allow third-parties to preempt the default check logic.
		$check = apply_filters( 'action_scheduler_pastdue_actions_check_pre', null );

		# Scheduled actions query arguments.
		$query_args = array(
			'date'     => as_get_datetime_object( time() - $threshold_seconds ),
			'status'   => ActionScheduler_Store::STATUS_PENDING,
			'per_page' => $threshhold_min,
		);

		# If no third-party preempted, run default check.
		if ( is_null( $check ) ) {
			$store = ActionScheduler_Store::instance();
			$num_pastdue_actions = ( int ) $store->query_actions( $query_args, 'count' );

			# Check if past-due actions count is greater than or equal to threshold.
			$check = ( $num_pastdue_actions >= $threshhold_min );
			$check = ( bool ) apply_filters( 'action_scheduler_pastdue_actions_check', $check, $num_pastdue_actions, $threshold_seconds, $threshhold_min );
		}

		# If check failed, set transient and abort.
		if ( ! boolval( $check ) ) {
			$interval = apply_filters( 'action_scheduler_pastdue_actions_check_interval', round( $threshold_seconds / 4 ), $threshold_seconds );
			set_transient( 'action_scheduler_last_pastdue_actions_check', time(), $interval );

			return;
		}

		$actions_url = add_query_arg( array(
			'page'   => 'action-scheduler',
			'status' => 'past-due',
			'order'  => 'asc',
		), admin_url( 'tools.php' ) );

		# Print notice.
		echo '<div class="notice notice-warning"><p>';
		printf(
			_n(
				// translators: 1) is the number of affected actions, 2) is a link to an admin screen.
				'<strong>Action Scheduler:</strong> %1$d <a href="%2$s">past-due action</a> found; something may be wrong. <a href="https://actionscheduler.org/faq/#my-site-has-past-due-actions-what-can-i-do" target="_blank">Read documentation &raquo;</a>',
				'<strong>Action Scheduler:</strong> %1$d <a href="%2$s">past-due actions</a> found; something may be wrong. <a href="https://actionscheduler.org/faq/#my-site-has-past-due-actions-what-can-i-do" target="_blank">Read documentation &raquo;</a>',
				$num_pastdue_actions,
				'action-scheduler'
			),
			$num_pastdue_actions,
			esc_attr( esc_url( $actions_url ) )
		);
		echo '</p></div>';

		# Facilitate third-parties to evaluate and print notices.
		do_action( 'action_scheduler_pastdue_actions_extra_notices', $query_args );
	}

	/**
	 * Provide more information about the screen and its data in the help tab.
	 */
	public function add_help_tabs() {
		$screen = get_current_screen();

		if ( ! $screen || self::$screen_id != $screen->id ) {
			return;
		}

		$as_version = ActionScheduler_Versions::instance()->latest_version();
		$screen->add_help_tab(
			array(
				'id'      => 'action_scheduler_about',
				'title'   => __( 'About','carrot-bunnycdn-incoom-plugin' ),
				'content' =>
					'<h2>' . sprintf( __( 'About Action Scheduler %s','carrot-bunnycdn-incoom-plugin' ), $as_version ) . '</h2>' .
					'<p>' .
						__( 'Action Scheduler is a scalable, traceable job queue for background processing large sets of actions. Action Scheduler works by triggering an action hook to run at some time in the future. Scheduled actions can also be scheduled to run on a recurring schedule.','carrot-bunnycdn-incoom-plugin' ) .
					'</p>',
			)
		);

		$screen->add_help_tab(
			array(
				'id'      => 'action_scheduler_columns',
				'title'   => __( 'Columns','carrot-bunnycdn-incoom-plugin' ),
				'content' =>
					'<h2>' . __( 'Scheduled Action Columns','carrot-bunnycdn-incoom-plugin' ) . '</h2>' .
					'<ul>' .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Hook','carrot-bunnycdn-incoom-plugin' ), __( 'Name of the action hook that will be triggered.','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Status','carrot-bunnycdn-incoom-plugin' ), __( 'Action statuses are Pending, Complete, Canceled, Failed','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Arguments','carrot-bunnycdn-incoom-plugin' ), __( 'Optional data array passed to the action hook.','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Group','carrot-bunnycdn-incoom-plugin' ), __( 'Optional action group.','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Recurrence','carrot-bunnycdn-incoom-plugin' ), __( 'The action\'s schedule frequency.','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Scheduled','carrot-bunnycdn-incoom-plugin' ), __( 'The date/time the action is/was scheduled to run.','carrot-bunnycdn-incoom-plugin' ) ) .
					sprintf( '<li><strong>%1$s</strong>: %2$s</li>', __( 'Log','carrot-bunnycdn-incoom-plugin' ), __( 'Activity log for the action.','carrot-bunnycdn-incoom-plugin' ) ) .
					'</ul>',
			)
		);
	}
}
