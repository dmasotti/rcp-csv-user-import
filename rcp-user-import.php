<?php
/**
 * Plugin Name: Restrict Content Pro - CSV User Import
 * Plugin URL: https://restrictcontentpro.com/downloads/csv-user-import/
 * Description: Allows you to import a CSV of users into Restrict Content Pro
 * Version: 1.1.8
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

	wp_enqueue_script( 'rcp-csv-user-import', plugin_dir_url( __FILE__ ) . 'assets/js/admin.js', array( 'jquery' ), '1.1.7', true );

	$levels         = rcp_get_subscription_levels();
	$free_level_ids = array();

	if ( ! empty( $levels ) ) {
		foreach ( $levels as $level ) {
			if ( empty( $level->duration ) && empty( $level->price ) ) {
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
				<tr>
					<th><?php _e( 'Disable Notification Emails', 'rcp_csv_ui' ); ?></th>
					<td>
						<input type="checkbox" name="rcp_member_import_disable_notification_emails" id="rcp_member_import_disable_notification_emails" value="1"/>
						<span class="description"><?php _e( 'If checked, all member and admin notification emails will be disabled for imported users.', 'rcp_csvui' ); ?></span>
					</td>
				</tr>
				<tr>
					<th><?php _e( 'Send Password Reset Emails', 'rcp_csv_ui' ); ?></th>
					<td>
						<input type="checkbox" name="rcp_member_import_send_password_reset_emails" id="rcp_member_import_send_password_reset_emails" value="1"/>
						<span class="description"><?php _e( 'If checked, new accounts will be sent a password reset email. Existing accounts will not receive one.', 'rcp_csvui' ); ?></span>
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

		/**
		 * @var RCP_Levels $rcp_levels_db
		 */
		global $rcp_levels_db;

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

		// Maybe disable notification emails.
		if ( ! empty( $_POST['rcp_member_import_disable_notification_emails'] ) ) {
			remove_action( 'rcp_set_status', 'rcp_email_on_expiration', 11 );
			remove_action( 'rcp_set_status', 'rcp_email_on_activation', 11 );
			remove_action( 'rcp_set_status', 'rcp_email_on_free_trial', 11 );
			remove_action( 'rcp_set_status_free', 'rcp_email_on_free_subscription', 11 );
			remove_action( 'rcp_set_status', 'rcp_email_on_cancellation', 11 );
		}

		foreach ( $csv->data as $user ) {

			$expiration = ! empty( $_POST['rcp_expiration'] ) ? sanitize_text_field( $_POST['rcp_expiration'] ) : false;
			$email      = ! empty( $user['User Email'] ) ? $user['User Email'] : $user['user_email'];

			if ( ! empty( $user['id'] ) ) {

				$user_data = get_userdata( $user['id'] );

			} elseif ( ! empty( $user['ID'] ) ) {

				$user_data = get_userdata( $user['ID'] );

			} elseif ( ! empty( $user['User ID'] ) ) {

				$user_data = get_userdata( $user['User ID'] );

			} else {

				$user_data = get_user_by( 'email', $email );

			}

			// Password
			if ( ! empty( $user['User Password'] ) ) {
				$password = $user['User Password'];
			} elseif ( ! empty( $user['user_password'] ) ) {
				$password = $user['user_password'];
			} else {
				$password = '';
			}

			// First name
			if ( ! empty( $user['First Name'] ) ) {
				$first_name = $user['First Name'];
			} elseif ( ! empty( $user['first_name'] ) ) {
				$first_name = $user['first_name'];
			} else {
				$first_name = '';
			}

			// Last name
			if ( ! empty( $user['Last Name'] ) ) {
				$last_name = $user['Last Name'];
			} elseif ( ! empty( $user['last_name'] ) ) {
				$last_name = $user['last_name'];
			} else {
				$last_name = '';
			}

			if ( ! $user_data ) {

				/**
				 * Create a new account.
				 */

				if ( empty( $password ) ) {
					$password = wp_generate_password();
				}

				// User login
				if ( ! empty( $user['User Login'] ) ) {
					$user_login = $user['User Login'];
				} elseif ( ! empty( $user['user_login'] ) ) {
					$user_login = $user['user_login'];
				} else {
					$user_login = $email;
				}

				$user_data  = array(
					'user_login' => $user_login,
					'user_email' => $email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'user_pass'  => $password,
					'role'       => ! empty( $subscription_details->role ) ? $subscription_details->role : 'subscriber'
				);

				$user_id = wp_insert_user( $user_data );

				if ( ! empty( $_POST['rcp_member_import_send_password_reset_emails'] ) ) {
					wp_new_user_notification( $user_id, null, 'user' );
				}

			} else {

				/**
				 * Update an existing account with new information.
				 */

				$user_id = $user_data->ID;

				$data_to_update = array();

				if ( ! empty( $password ) ) {
					$data_to_update['user_pass'] = $password;
				}

				if ( ! empty( $first_name ) ) {
					$data_to_update['first_name'] = $first_name;
				}

				if ( ! empty( $last_name ) ) {
					$data_to_update['last_name'] = $last_name;
				}

				if ( ! empty( $data_to_update ) ) {
					$data_to_update['ID'] = $user_id;

					wp_update_user( $data_to_update );
				}

			}

			/**
			 * If the expiration date wasn't specified on the import screen,
			 * check the CSV file. If no expiration in the CSV file, calculate
			 * the expiration date based on the subscription level.
			 */
			if ( ! $expiration || strlen( trim( $expiration ) ) <= 0 ) {
				if ( ! empty( $user['Expiration'] ) ) {
					$expiration = $user['Expiration'];
				} elseif ( ! empty( $user['expiration'] ) ) {
					$expiration = $user['expiration'];
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

			/**
			 * Get subscription key.
			 */
			if ( ! empty( $user['Subscription Key'] ) ) {
				$subscription_key = $user['Subscription Key'];
			} elseif ( ! empty( $user['subscription_key'] ) ) {
				$subscription_key = $user['subscription_key'];
			} else {
				$subscription_key = '';
			}

			/**
			 * Get recurring flag.
			 */
			if ( isset( $user['Recurring'] ) ) {
				$recurring = $user['Recurring'];
			} elseif ( isset( $user['recurring'] ) ) {
				$recurring = $user['recurring'];
			} else {
				$recurring = null;
			}
			// Convert into boolean.
			if ( ! empty( $recurring ) && in_array( $recurring, array( '1', 'yes' ) ) ) {
				$recurring = true;
			} elseif ( in_array( $recurring, array( '0', 'no' ) ) ) {
				$recurring = false;
			}

			/**
			 * Get payment profile ID.
			 */
			if ( ! empty( $user['Payment Profile ID'] ) ) {
				$payment_profile_id = $user['Payment Profile ID'];
			} elseif ( ! empty( $user['payment_profile_id'] ) ) {
				$payment_profile_id = $user['payment_profile_id'];
			} else {
				$payment_profile_id = '';
			}

			/**
			 * Get join date.
			 */
			if ( ! empty( $user['Join Date'] ) ) {
				$join_date = date( 'Y-m-d H:i:s', strtotime( $user['Join Date'] ) );
			} elseif ( ! empty( $user['join_date'] ) ) {
				$join_date = date( 'Y-m-d H:i:s', strtotime( $user['join_date'] ) );
			} else {
				$join_date = ''; // Will default to today.
			}

			$member = new RCP_Member( $user_id );

			if ( function_exists( 'rcp_add_user_to_subscription' ) ) {

				/**
				 * Use RCP 2.9+ function for adding user to a subscription.
				 * @see rcp_add_user_to_subscription()
				 */

				if ( null === $recurring ) {
					// Get existing value so we don't change it.
					$recurring = $member->is_recurring();
				}

				// Set join date now if it's specified. If not, this will be set to today in rcp_add_user_to_subscription().
				if ( ! empty( $join_date ) ) {
					$member->set_joined_date( $join_date, $subscription_id );
				}

				$args = array(
					'status'             => $status,
					'subscription_id'    => $subscription_id,
					'expiration'         => $expiration,
					'subscription_key'   => $subscription_key,
					'recurring'          => $recurring,
					'payment_profile_id' => $payment_profile_id
				);

				rcp_add_user_to_subscription( $user_id, $args );

			} else {

				/**
				 * Backwards compatibility if RCP core is not on version 2.9+.
				 */

				$previous_subscription_level = $member->get_subscription_id();

				// Set subscription level.
				$member->set_subscription_id( $subscription_id );

				// Set recurring value.
				if ( true === $recurring ) {
					$member->set_recurring( true );
				} elseif ( false === $recurring ) {
					$member->set_recurring( false );
				}

				// Set expiration date.
				$member->set_expiration_date( $expiration );

				// Set payment profile ID
				if ( ! empty( $payment_profile_id ) ) {
					$member->set_payment_profile_id( $payment_profile_id );
				}

				// Set subscription key
				if ( ! empty( $subscription_key ) ) {
					$member->set_subscription_key( $subscription_key );
				}

				$member->set_status( $status );

				// Set join date.
				$member->set_joined_date( $join_date );

				// Remove the user's old role.
				$old_role = get_option( 'default_role', 'subscriber' );
				if ( ! empty( $previous_subscription_level ) ) {
					$old_level = $rcp_levels_db->get_level( $previous_subscription_level );
					$old_role  = ! empty( $old_level->role ) ? $old_level->role : $old_role;
				}
				$member->remove_role( $old_role );

				// Set the user's new role for this new subscription level.
				$role = ! empty( $subscription_details->role ) ? $subscription_details->role : get_option( 'default_role', 'subscriber' );
				$member->add_role( apply_filters( 'rcp_default_user_level', $role, $subscription_details->id ) );

			}

			/**
			 * Set merchant subscription ID (i.e. Stripe subscription ID number).
			 * This isn't available in rcp_add_user_to_subscription(), which is why it's down here.
			 */
			if ( ! empty( $user['Subscription ID'] ) ) {
				$member->set_merchant_subscription_id( sanitize_text_field( $user['Subscription ID'] ) );
			}

			update_user_meta( $user_id, 'rcp_signup_method', 'imported' );

			$member->add_note( __( 'Imported from CSV file.', 'rcp_csvui' ) );

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
