<?php
/**
 * Plugin Name: Restrict Content Pro - CSV User Import
 * Plugin URL: https://restrictcontentpro.com/downloads/csv-user-import/
 * Description: Allows you to import a CSV of users into Restrict Content Pro
 * Version: 1.1.6
 * Author: Pippin Williamson
 * Author URI: https://pippinsplugins.com
 * Contributors: mordauk, chriscoyier, mindctrl, nosegraze
 */

if ( ! defined( 'RCP_CSVUI_PLUGIN_DIR' ) ) {
	define( 'RCP_CSVUI_PLUGIN_DIR', dirname( __FILE__ ) );
}

ini_set( 'max_execution_time', 90 );

/**
 * Create admin sub-menu page for CSV Import
 *
 * @return void
 */
function rcp_csvui_menu_page() {
	global $rcp_csvui_import_page;
	$rcp_csvui_import_page = add_submenu_page( 'rcp-members', __( 'CSV Import', 'rcp_csvui' ), __( 'CSV Import', 'rcp_csvui' ), 'manage_options', 'rcp-csv-import', 'rcp_csvui_purchase_import' );
}
add_action( 'admin_menu', 'rcp_csvui_menu_page', 100 );

/**
 * Load admin scripts on CSV Import page.
 *
 * @param string $hook Current page hook.
 *
 * @since 1.1.7
 * @return void
 */
function rcp_csvui_admin_scripts( $hook ) {

	if ( 'restrict_page_rcp-csv-import' != $hook ) {
		return;
	}

	if ( ! function_exists( 'rcp_get_subscription_levels' ) ) {
		return;
	}

	wp_enqueue_script( 'rcp-csv-user-import', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ), '1.1.6', true );

	$levels         = rcp_get_subscription_levels();
	$free_level_ids = array();

	if ( ! empty( $levels ) ) {
		foreach ( $levels as $level ) {
			if ( empty( $level->duration ) ) {
				$free_level_ids[] = $level->id;
			}
		}
	}

	wp_localize_script( 'rcp-csv-user-import', 'rcp_csvui_vars', array(
		'free_level_ids'      => $free_level_ids,
		'confirm_edit_status' => __( 'Members of free subscription levels should be given the status "Free" - not "Active". Changing this may cause certain features like the [is_paid] shortcode to not work as expected.', 'rcp_csvui' )
	) );

}
add_action( 'admin_enqueue_scripts', 'rcp_csvui_admin_scripts' );

/**
 * Render the CSV Import page
 *
 * @return void
 */
function rcp_csvui_purchase_import() {
	?>
	<div class="wrap">
		<h2><?php _e( 'CSV User Import', 'rcp_csvui' ); ?></h2>
		<?php settings_errors( 'rcp-csv-ui' ); ?>
		<P><?php _e( 'Use this tool to import user memberships into Restrict Content Pro', 'rcp_csvui' ); ?></p>
		<p><?php printf( __( '<strong>Note</strong>: your CSV should contain the following fields: <em>User Email, First Name, Last Name, User Login</em>. If you wish to update existing users, you can include a <em>User ID</em> field as well. See the <a href="%s">documentation article</a> for more available fields.', 'rcp_csvui' ), 'http://docs.restrictcontentpro.com/article/1621-csv-user-import' ); ?></p>
		<script type="text/javascript">jQuery(document).ready(function($) { var dateFormat = 'yy-mm-dd'; $('.rcp_datepicker').datepicker({dateFormat: dateFormat}); });</script>
		<form id="rcp_csvui_import" enctype="multipart/form-data" method="post">
			<table class="form-table">
				<tr>
					<th><?php _e( 'CSV File', 'rcp_csvui' ); ?></th>
					<td>
						<input type="file" accept=".csv, text/csv" name="rcp_csvui_file"/>
						<div class="description"><?php _e( 'Select the CSV file to import. Must follow guidelines above.', 'rcp_csvui' ); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Subscription Level', 'rcp_csv_ui' ); ?></th>
					<td>
						<select name="rcp_level" id="rcp_level">
						<?php
						$subscription_levels = rcp_get_subscription_levels();
						foreach ( $subscription_levels as $level ) {
							echo '<option value="' . esc_attr( absint( $level->id ) ) . '">' . esc_html( $level->name ) . '</option>';
						}
						?>
						</select>
						<div class="description"><?php _e( 'Select the subscription level to add users to.', 'rcp_csvui' ); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e('Status', 'rcp_csv_ui'); ?></th>
					<td>
						<select name="rcp_status" id="rcp_status">
							<option value="active"><?php _e( 'Active', 'rcp_csvui' ); ?></option>
							<option value="pending"><?php _e( 'Pending', 'rcp_csvui' ); ?></option>
							<option value="cancelled"><?php _e( 'Cancelled', 'rcp_csvui' ); ?></option>
							<option value="expired"><?php _e( 'Expired', 'rcp_csvui' ); ?></option>
							<option value="free"><?php _e( 'Free', 'rcp_csvui' ); ?></option>
						</select>
						<a href="#" id="rcp_csvui_edit_status" style="display:none;"><?php _e( 'Edit Status', 'rcp_csvui' ); ?></a>
						<div class="description"><?php _e( 'Select the subscription status to import users with.', 'rcp_csvui' ); ?></div>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Expiration', 'rcp_csv_ui' ); ?></th>
					<td>
						<input type="text" name="rcp_expiration" id="rcp_expiration" value="" class="rcp_datepicker"/>
						<div class="description"><?php _e( 'Select the expiration date for all users. Leave this blank and the expiration date will be automatically calculated based on the selected subscription.', 'rcp_csvui' ); ?></div>
					</td>
				</tr>

			</table>
			<input type="hidden" name="rcp_action" value="process_csv_import"/>
			<?php wp_nonce_field( 'rcp_csvui_nonce', 'rcp_csvui_nonce' ); ?>
			<?php submit_button( __( 'Upload and Import', 'rcp_csvui' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Process CSV import
 *
 * @return void
 */
function rcp_csvui_process_csv() {

	if ( isset( $_POST['rcp_action'] ) && $_POST['rcp_action'] == 'process_csv_import' ) {

		if ( ! wp_verify_nonce( $_POST['rcp_csvui_nonce'], 'rcp_csvui_nonce' ) ) {
			return;
		}

		if ( ! class_exists( 'parseCSV' ) ) {

			require_once dirname( __FILE__ ) . '/parsecsv.lib.php';
		}

		$import_file = ! empty( $_FILES['rcp_csvui_file'] ) ? $_FILES['rcp_csvui_file']['tmp_name'] : false;

		if ( ! $import_file ) {
			wp_die( __('Please upload a CSV file.', 'rcp_csvui' ), __('Error') );
		}

		$csv = new parseCSV();

		$csv->parse( $import_file );

		$subscription_id = isset( $_POST['rcp_level'] ) ? absint( $_POST['rcp_level'] ) : false;

		if ( ! $subscription_id ) {
			wp_die( __('Please select a subscription level.', 'rcp_csvui' ), __('Error') );
		}

		$subscription_details = rcp_get_subscription_details( $subscription_id );

		if ( ! $subscription_details ) {
			wp_die( sprintf( __('That subscription level does not exist: #%d.', 'rcp_csvui' ), $subscription_id ), __('Error') );
		}

		$status = isset( $_POST['rcp_status'] ) ? sanitize_text_field( $_POST['rcp_status'] ) : 'free';

		foreach ( $csv->data as $user ) {

			$expiration = ! empty( $_POST['rcp_expiration'] ) ? sanitize_text_field( $_POST['rcp_expiration'] ) : false;

			if ( ! empty( $user['id'] ) ) {

				$user_data = get_userdata( $user['id'] );

			} elseif ( ! empty( $user['ID'] ) ) {

				$user_data = get_userdata( $user['ID'] );

			} elseif ( ! empty( $user['User ID'] ) ) {

				$user_data = get_userdata( $user['User ID'] );

			} else {

				$user_data = get_user_by( 'email', $user['User Email'] );

			}

			if ( ! $user_data ) {

				$email      = $user['User Email'];
				$password   = ! empty( $user['User Password'] ) ? $user['User Password'] : wp_generate_password();
				$user_login = ! empty( $user['User Login'] ) ? $user['User Login'] : $user['User Email'];

				$user_data  = array(
					'user_login' => $user_login,
					'user_email' => $email,
					'first_name' => $user['First Name'],
					'last_name'  => $user['Last Name'],
					'user_pass'  => $password,
					'role'       => ! empty( $subscription_details->role ) ? $subscription_details->role : 'subscriber'
				);

				$user_id = wp_insert_user( $user_data );

			} else {
				$user_id = $user_data->ID;
			}

			$member = new RCP_Member( $user_id );

			update_user_meta( $user_id, 'rcp_subscription_level', $subscription_id );

			if ( ! empty( $user['Recurring'] ) && in_array( $user['Recurring'], array( '1', 'yes' ) ) ) {
				$member->set_recurring( true );
			} elseif ( in_array( $user['Recurring'], array( '0', 'no' ) ) ) {
				$member->set_recurring( false );
			}

			/**
			 * If the expiration date wasn't specified on the import screen,
			 * check the CSV file. If no expiration in the CSV file, calculate
			 * the expiration date based on the subscription level.
			 */
			if ( ! $expiration || strlen( trim( $expiration ) ) <= 0 ) {

				if ( ! empty( $user['Expiration'] ) ) {
					$expiration = $user['Expiration'];

				} else {
					// calculate expiration here
					$expiration = rcp_calculate_subscription_expiration( $subscription_id );
				}
			}

			// Make sure a supplied date is formatted correctly.
			if ( 'none' != strtolower( $expiration ) ) {
				$timestamp  = is_int( $expiration ) ? $expiration : strtotime( str_replace( ',', '', $expiration ), current_time( 'timestamp' ) );
				$expiration = date( 'Y-m-d H:i:s', $timestamp );
			}

			$member->set_expiration_date( $expiration );

			if ( ! empty( $user['Payment Profile ID'] ) ) {
				$member->set_payment_profile_id( $user['Payment Profile ID'] );
			}

			if ( ! empty( $user['Subscription Key'] ) ) {
				update_user_meta( $user_id, 'rcp_subscription_key', $user['Subscription Key'] );
			}

			$member->set_status( $status );

			// Set join date.
			if ( ! empty( $user['Join Date'] ) ) {
				$join_date = date( 'Y-m-d H:i:s', strtotime( $user['Join Date'] ) );
			} else {
				$join_date = ''; // Will default to today.
			}
			$member->set_joined_date( $join_date );

			do_action( 'rcp_user_import_user_added', $user_id, $user_data, $subscription_id, $status, $expiration, $user );
		}

		wp_redirect( admin_url( '/admin.php?page=rcp-csv-import&rcp-message=users-imported' ) ); exit;
	}
}
add_action( 'admin_init', 'rcp_csvui_process_csv' );

/**
 * Display admin notice after a successful import
 *
 * @return void
 */
function rcp_csvui_notices() {
	if( isset( $_GET['rcp-message'] ) && $_GET['rcp-message'] == 'users-imported' ) {
		add_settings_error( 'rcp-csv-ui', 'imported', __('All users have been imported.', 'rcp_csvui'), 'updated' );
	}
}
add_action( 'admin_notices', 'rcp_csvui_notices' );

/**
 * Load admin scripts on the import page
 *
 * @param string $hook Current page
 *
 * @return void
 */
function rcp_csvui_scripts( $hook ) {
	global $rcp_csvui_import_page;
	if( $hook != $rcp_csvui_import_page ) {
		return;
	}

	wp_enqueue_style( 'datepicker', RCP_PLUGIN_URL . 'includes/css/datepicker.css' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
}
add_action( 'admin_enqueue_scripts', 'rcp_csvui_scripts' );

/**
 * Convert CSV to array
 *
 * @param string $filename Path to the file.
 * @param string $delimiter Delimeter.
 *
 * @return array|bool
 */
function rcp_csvui_csv_to_array( $filename = '', $delimiter = ',') {
	if ( ! file_exists( $filename ) || ! is_readable( $filename ) ) {
		return false;
	}

	$header = NULL;
	$data = array();
	if ( false !== ( $handle = fopen( $filename, 'r' ) ) ) {
		while ( false !== ( $row = fgetcsv( $handle, 1000, $delimiter ) ) ) {
			if ( ! $header ) {
				$header = $row;
			} else {
				$data[] = array_combine( $header, $row );
			}
		}
		fclose( $handle );
	}
	return $data;
}
