<?php
/**
 * @package Simple_Page_Sidebars
 *
 * @todo Consider adding a Sidebars submenu to the Appearance menu for
 *       selecting and editing a sidebar.
 * @todo Consider how to report any sidebars that get wiped out.
 */

/**
 * Main admin class. Contains all the functionality to handle the various
 * administrative tasks for creating, editing, and assigning sidebars to
 * pages.
 *
 * @since 1.1.0
 */
class Simple_Page_Sidebars_Admin {
	/**
	 * Load the admin functionality.
	 *
	 * @since 1.1.0
	 */
	public static function load() {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	/**
	 * Attaches the various hooks and methods for integrating with the
	 * dashboard.
	 *
	 * @since 0.2.0
	 */
	public static function init() {
		// Process submissions from custom Sidebar Edit screen.
		self::process_sidebar_update();

		// Process Add/Edit Page screen submissions.
		add_action( 'save_post', array( __CLASS__, 'update_page_sidebar' ) );
		// Process quick edit and bulk edit from All Pages screen.
		add_action( 'wp_ajax_simplepagesidebars_update_page_sidebar', array( __CLASS__, 'update_page_sidebar' ) );

		add_action( 'admin_menu', array( __CLASS__, 'add_sidebar_edit_screen' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_page_sidebar_meta_box' ) );

		add_action( 'admin_init', array( __CLASS__, 'register_default_sidebar_setting' ) );

		add_filter( 'parse_query', array( __CLASS__, 'parse_admin_query' ) );
		add_filter( 'manage_pages_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_edit-page_sortable_columns', array( __CLASS__, 'register_sortable_columns' ) );
		add_action( 'manage_pages_custom_column', array( __CLASS__, 'display_columns' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( __CLASS__, 'bulk_edit_custom_box' ), 10, 2 );
		add_action( 'admin_footer-edit.php', array( __CLASS__, 'quick_edit_js' ) );

		add_action( 'widgets_admin_page', array( __CLASS__, 'widgets_page_messages' ) );
	}

	/**
	 * Register setting for choosing the default sidebar.
	 *
	 * @since 0.2.0
	 */
	public static function register_default_sidebar_setting() {
		register_setting(
			'reading',
			'simple_page_sidebars_default_sidebar'
		);

		add_settings_field(
			'simple_page_sidebars_default_sidebar',
			'<label for="simple-page-sidebars-default-sidebar">' . __( 'Default Sidebar', 'simple-page-sidebars' ) . '</label>',
			array( __CLASS__, 'default_sidebar_settings_field' ),
			'reading'
		);
	}

	/**
	 * Default sidebar option dropdown.
	 *
	 * @since 0.2.0
	 * @uses $wp_registered_sidebars
	 */
	public static function default_sidebar_settings_field() {
		global $wp_registered_sidebars;

		$default_sidebar_id = get_option( 'simple_page_sidebars_default_sidebar' );
		$custom_sidebars = simple_page_sidebars_get_names();
		?>
		<select name="simple_page_sidebars_default_sidebar" id="simple-page-sidebars-default-sidebar">
			<option value=""></option>
			<?php
			foreach ( $wp_registered_sidebars as $sb ) {
				if ( is_array( $custom_sidebars ) && ! in_array( $sb['name'], $custom_sidebars ) ) {
					printf( '<option value="%s"%s>%s</option>',
						esc_attr( $sb['id'] ),
						selected( $sb['id'], $default_sidebar_id, false ),
						esc_html( $sb['name'] )
					);
				}
			}
			?>
		</select>
		<span class="description"><?php _e( 'The sidebar that should be replaced by custom sidebars.', 'simple-page-sidebars' ); ?></span>
		<?php
	}

	/**
	 * Register page sidebar meta box.
	 *
	 * @since 0.2.0
	 */
	public static function add_page_sidebar_meta_box( $post_type ) {
		if ( 'page' == $post_type || post_type_supports( $post_type, 'simple-page-sidebars' ) ) {
			add_meta_box( 'simplepagesidebarsdiv', __( 'Sidebar', 'simple-page-sidebars' ), array( __CLASS__, 'page_sidebar_meta_box' ), $post_type, 'side', 'default' );
		}
	}

	/**
	 * Meta box for adding a new sidebar or choosing an existing sidebar.
	 *
	 * @since 0.2.0
	 * @uses $wpdb, $wp_registered_sidebars
	 * @todo Improve the update message delivery and only show it on success.
	 *
	 * @param object $page The post object being added or edited.
	 */
	public static function page_sidebar_meta_box( $page ) {
		global $wpdb, $wp_registered_sidebars;

		$sidebar = self::get_page_sidebar( $page->ID );
		$custom_sidebars = simple_page_sidebars_get_names();

		// Show an error message if a default sidebar hasn't been selected on the Reading settings screen.
		if ( ! get_option( 'simple_page_sidebars_default_sidebar' )) {
			echo '<div class="simple-page-sidebars-page-sidebar-feedback simple-page-sidebars-page-sidebar-feedback-error"><p>';
				echo self::get_empty_default_sidebar_error();
			echo '</p></div>';
		}

		wp_nonce_field( 'update-page-sidebar_' . $page->ID, 'simplepagesidebars_page_sidebar_update_nonce', false );

		include_once( SIMPLE_PAGE_SIDEBARS_DIR . 'admin/views/meta-box-page-sidebar.php' );
	}

	/**
	 * Custom sort the pages on the All Pages screen.
	 *
	 * Any pages without the '_sidebar_name' meta field won't appear in the
	 * list when the pages are custom sorted.
	 *
	 * The $wp_query object is passed be reference and any changes made to it
	 * will be reflected globally.
	 *
	 * @since 1.1.0
	 * @link https://codex.wordpress.org/Class_Reference/WP_Query
	 *
	 * @param object $wp_query The WP_Query object passed by reference.
	 */
	public static function parse_admin_query( $wp_query ) {
		// Ensure this only affects requests in the dashboard.
		if ( is_admin() && isset( $_GET['post_type'] ) && 'page' == $_GET['post_type'] ) {
			if ( ! empty( $_GET['orderby'] ) && 'simple-page-sidebar' == $_GET['orderby'] ) {
				// An example to sort results by a custom meta field.
				$wp_query->set( 'meta_key', '_sidebar_name' );
				$wp_query->set( 'orderby', 'meta_value' );

				// Set the order.
				$order = ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] ) ? 'desc' : 'asc';
				$wp_query->set( 'order', $order );
			}
		}
	}

	/**
	 * Register sidebar column on the All Pages screen.
	 *
	 * @since 0.2.0
	 * @param array $columns Array of column names and corresponding IDs as keys.
	 * @param array $columns The filtered columns array.
	 */
	public static function register_columns( $columns ) {
		$columns['simple-page-sidebar'] = __( 'Sidebar', 'simple-page-sidebars' );
		return $columns;
	}

	/**
	 * Register sortable columns on the All Pages screen.
	 *
	 * @since 1.1.0
	 * @param array $columns Array of query vars and corresponding column IDs as keys.
	 * @return array $columns The filtered columns array.
	 */
	public static function register_sortable_columns( $columns ) {
		$columns['simple-page-sidebar'] = 'simple-page-sidebar';

		return $columns;
	}

	/**
	 * Display sidebar column on All Pages screen.
	 *
	 * @since 0.2.0
	 * @param string $column The ID of the column being displayed.
	 * @param int $page_id The ID of the page the column is associated with.
	 */
	public static function display_columns( $column, $page_id ) {
		if ( 'simple-page-sidebar' == $column ) {
			$sidebar = self::get_page_sidebar( $page_id );
			if ( $sidebar ) {
				// The edit link can be disabled to prevent confusion if support is added to other post types.
				if ( apply_filters( 'simple_page_sidebars_show_edit_link_in_column', true ) ) {
					printf( '<a href="%s">%s</a>',
						esc_url( self::get_sidebar_edit_link( $sidebar ) ),
						$sidebar
					);
				} else {
					echo $sidebar;
				}
			}

			// Add the nonce here and copy it to the inline editor with javascript.
			$nonce = wp_create_nonce( 'update-page-sidebar_' . $page_id );
			printf( '<input type="hidden" value="%s" class="simplepagesidebars_page_sidebar_update_nonce">', esc_attr( $nonce ) );
		}
	}

	/**
	 * Sidebar dropdown field for quick edit mode.
	 *
	 * @since 0.2.0
	 * @param string $column The ID of the column being rendered.
	 * @param string $post_type The type of post being updated.
	 */
	public static function quick_edit_custom_box( $column, $post_type ) {
		if ( 'page' != $post_type || 'simple-page-sidebar' != $column ) {
			return;
		}

		$sidebars = simple_page_sidebars_get_names();
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<div class="inline-edit-group" id="simple-page-sidebars-page-sidebar-edit-group">
					<label>
						<span class="title"><?php _e( 'Sidebar', 'simple-page-sidebars' ); ?></span>
						<select name="simplepagesidebars_page_sidebar_name" id="simple-page-sidebars-page-sidebar-name">
							<option value="default"><?php _e( 'Default Sidebar', 'simple-page-sidebars' ); ?></option>
							<?php
							foreach ( $sidebars as $sb ) {
								printf( '<option value="%1$s">%1$s</option>', $sb );
							}
							?>
						</select>
					</label>
					<?php wp_nonce_field( 'update-page-sidebar', 'simplepagesidebars_page_sidebar_update_nonce', false ); ?>
				</div>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Quick edit javascript.
	 *
	 * Selects the correct sidebar during quick edit and copies the nonce for
	 * saving.
	 *
	 * @since 0.2.0
	 */
	public static function quick_edit_js() {
		$screen = get_current_screen();

		if ( 'edit-page' != $screen->id || 'page' != $screen->post_type ) {
			return;
		}
		?>
		<script type="text/javascript">
		( function( window, $, undefined ) {
			'use strict';

			var wpInlineEdit = inlineEditPost.edit;

			inlineEditPost.edit = function( id ) {
				wpInlineEdit.apply( this, arguments );

				var postId = inlineEditPost.getId( id ),
					currentSidebar = $( '#post-' + postId + ' .simple-page-sidebar' ).text(),
					sidebarNameField = $( '#simple-page-sidebars-page-sidebar-name' ),
					$nonceField = $( '#simple-page-sidebars-page-sidebar-edit-group' ).find( 'input[name="simplepagesidebars_page_sidebar_update_nonce"]' );

				// Select the current sidebar option.
				sidebarNameField.find( 'option' ).attr( 'selected', false );
				if ( '' != currentSidebar ) {
					sidebarNameField.find( 'option:contains(' + currentSidebar + ')' ).attr( 'selected', true );
				}

				// Copy the sidebar name nonce.
				$nonceField.val( $( '#post-' + postId + ' .simplepagesidebars_page_sidebar_update_nonce' ).val() );
			};
		} )( window, jQuery );
		</script>
		<style type="text/css">
		.widefat .column-simple-page-sidebar { width: 15%;}
		</style>
		<?php
	}

	/**
	 * Sidebar dropdown field for bulk edit mode.
	 *
	 * @since 0.2.0
	 * @param string $column The ID of the column being rendered.
	 * @param string $post_type The type of post being updated.
	 */
	public static function bulk_edit_custom_box( $column, $post_type ) {
		if ( 'page' != $post_type || 'simple-page-sidebar' != $column ) {
			return;
		}

		$sidebars = simple_page_sidebars_get_names();
		?>
		<fieldset class="inline-edit-col-right" style="margin-top: 0">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Sidebar', 'simple-page-sidebars' ); ?></span>
					<select name="simplepagesidebars_page_sidebar_name" id="simple-page-sidebars-page-sidebar-name">
						<option value="-1"><?php _e( '&mdash; No Change &mdash;', 'simple-page-sidebars' ); ?></option>
						<option value="default"><?php _e( 'Default Sidebar', 'simple-page-sidebars' ); ?></option>
						<?php
						foreach ( $sidebars as $sb ) {
							printf( '<option value="%1$s">%2$s</option>', esc_attr( $sb ), esc_html( $sb ) );
						}
						?>
					</select>
				</label>
				<?php wp_nonce_field( 'bulk-update-page-sidebar', 'simplepagesidebars_bulk_update_nonce', false ); ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save custom page sidebar.
	 *
	 * Processes requests coming from normal page edits, quick edit, and bulk
	 * edit. Requires a valid nonce.
	 *
	 * @since 0.2.0
	 * @param int $post_id Optional. The ID of the page whose sidebar should be updated.
	 */
	public static function update_page_sidebar( $post_id = null ) {
		if ( empty( $post_id ) ) {
			$post_id = $_REQUEST['post_id'];
		}

		// Verify either an individual post nonce or the bulk edit nonce.
		// Requests can come from a page update, AJAX from the sidebar meta box, quick edit, or bulk edit.
		$is_nonce_valid = ( isset( $_REQUEST['simplepagesidebars_page_sidebar_update_nonce'] ) && wp_verify_nonce( $_REQUEST['simplepagesidebars_page_sidebar_update_nonce'], 'update-page-sidebar_' . $post_id ) ) ? true : false;
		$is_bulk_nonce_valid = ( isset( $_REQUEST['simplepagesidebars_bulk_update_nonce'] ) && wp_verify_nonce( $_REQUEST['simplepagesidebars_bulk_update_nonce'], 'bulk-update-page-sidebar' ) ) ? true : false;
		$is_autosave = ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ? true : false;
		$is_revision = wp_is_post_revision( $post_id );

		if ( ( $is_autosave || $is_revision ) || ( ! $is_nonce_valid && ! $is_bulk_nonce_valid ) ) {
			return $post_id;
		}

		// If 'new_sidebar_name' is set and not empty, it supercedes any 'sidebar_name' setting.
		// If 'sidebar_name' is blank or it equals 'default', delete meta.
		// If 'sidebar_name' is set and not empty, update to new name.
		// If 'sidebar_name' is -1, skip.

		// Bulk edit uses $_GET for some reason, so we use the $_REQUEST global to catch everything.
		$sidebar = ( isset( $_REQUEST['simplepagesidebars_page_sidebar_name'] ) ) ? self::sanitize_sidebar_name( $_REQUEST['simplepagesidebars_page_sidebar_name'] ) : -1;
		$new_sidebar_name = ( isset( $_REQUEST['simplepagesidebars_page_sidebar_name_new'] ) ) ? self::sanitize_sidebar_name( $_REQUEST['simplepagesidebars_page_sidebar_name_new'] ) : '';

		if ( isset( $new_sidebar_name ) && ! empty( $new_sidebar_name ) ) {
			update_post_meta( $post_id, '_sidebar_name', $new_sidebar_name );
		} elseif ( empty( $sidebar ) || 'default' == $sidebar ) {
			delete_post_meta( $post_id, '_sidebar_name' );
		} elseif ( -1 != intval( $sidebar ) ) {
			update_post_meta( $post_id, '_sidebar_name', $sidebar );
		}
	}

	/**
	 * Add a custom Sidebar Edit screen.
	 *
	 * The menu title argument in add_submenu_page() is null so the page won't
	 * appear in the admin menu. It simply registers the screen so it's
	 * available when visited.
	 *
	 * @since 1.1.0
	 */
	public static function add_sidebar_edit_screen() {
		add_submenu_page( 'admin.php', __( 'Edit Sidebar', 'simple-page-sidebars' ), null, 'edit_theme_options', 'simple-page-sidebars', array( __CLASS__, 'edit_sidebar_screen' ) );

		add_meta_box( 'simplepagesidebarseditdiv', 'Pages', array( __CLASS__, 'edit_sidebar_pages_meta_box' ), 'admin_page_simple-page-sidebars', 'normal', 'default' );
	}

	/**
	 * Display the Edit Sidebar screen.
	 *
	 * The sidebar being edited is passed as a variable through the query
	 * string. If it's determined that the sidebar isn't valid, an error will
	 * be shown.
	 *
	 * @since 1.1.0
	 */
	public static function edit_sidebar_screen() {
		global $wpdb;

		wp_enqueue_script( 'post' );

		$screen = get_current_screen();
		$sidebar_name = self::sanitize_sidebar_name( stripslashes( $_GET['sidebar'] ) );

		include( SIMPLE_PAGE_SIDEBARS_DIR . 'admin/views/edit-sidebar-screen.php' );
	}

	/**
	 * Add a page checbox list meta box to the Edit Sidebar screen.
	 *
	 * @since 1.1.0
	 * @param object $post The post being edited.
	 * @param array $metabox Any additional arguments passed during the meta box registration.
	 */
	public static function edit_sidebar_pages_meta_box( $post, $metabox ) {
		$default_sidebar = get_option( 'simple_page_sidebars_default_sidebar' );

		include_once( SIMPLE_PAGE_SIDEBARS_DIR . 'admin/includes/class-simple-page-sidebars-walker-page-checklist.php' );
		include_once( SIMPLE_PAGE_SIDEBARS_DIR . 'admin/views/meta-box-sidebar-pages.php' );
	}

	/**
	 * Process submissions for the Edit Sidebar screen.
	 *
	 * Handles cases where the sidebar is renamed, reassigns pages, and
	 * removes the sidebar if no pages are selected. Requires a valid nonce.
	 *
	 * @since 1.1.0
	 */
	public static function process_sidebar_update() {
		global $wpdb;

		if ( isset( $_POST['simplepagesidebars_sidebar_name'] ) ) {
			$current_name = stripslashes( $_POST['simplepagesidebars_sidebar_name'] );

			check_admin_referer( 'update-sidebar_' . $current_name, 'simplepagesidebars_sidebar_update_nonce' );

			$new_name = stripslashes( $_POST['simplepagesidebars_sidebar_name_new'] );
			$new_name = ( ! empty( $new_name ) && $new_name != $current_name ) ? trim( wp_strip_all_tags( $new_name ) ) : null;

			$pages = ( isset( $_POST['simplepagesidebars_sidebar_pages'] ) ) ? wp_parse_id_list( $_POST['simplepagesidebars_sidebar_pages'] ) : array();

			// Retrieve IDs of pages using the existing sidebar name.
			$current_pages = self::get_page_ids_using_sidebar( $current_name );

			// Pages to reset to the default sidebar.
			$reset_pages = array_diff( $current_pages, $pages );
			if ( $reset_pages ) {
				foreach( $reset_pages as $page_id ) {
					delete_post_meta( $page_id, '_sidebar_name' );
				}
			}

			// Update all sidebars if there is a new sidebar name.
			if ( $new_name ) {
				$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->postmeta SET meta_value=%s WHERE meta_key='_sidebar_name' AND meta_value=%s", $new_name, $current_name ) );
			}
			// Update newly selected pages with the current sidebar name.
			elseif ( $update_pages = array_diff( $pages, $current_pages ) ) {
				foreach( $update_pages as $page_id ) {
					update_post_meta( $page_id, '_sidebar_name', addslashes( $current_name ) );
				}
			}

			// The sidebar should have been removed from all pages.
			// Let WordPress move widgets to Inactive Sidebar, redirect to Widgets screen, and notify user.
			if ( empty( $pages ) ) {
				wp_safe_redirect( esc_url_raw( add_query_arg( 'simple-page-sidebars-message', 1, admin_url( 'widgets.php' ) ) ) );
				exit;
			}

			// Migrate widgets if the sidebar name was changed.
			if ( $new_name ) {
				$sidebars_widgets = wp_get_sidebars_widgets();

				$old_id = 'page-sidebar-' . sanitize_key( $current_name );
				$new_id = 'page-sidebar-' . sanitize_key( $new_name );

				// If new id matches an existing id, merge old widgets with new.
				if ( isset( $sidebars_widgets[ $new_id ] ) ) {
					$sidebars_widgets[ $new_id ] = array_merge( $sidebars_widgets[ $new_id ], $sidebars_widgets[ $old_id ] );
				}
				// Otherwise, copy old widgets to new.
				elseif ( isset( $sidebars_widgets[ $old_id ] ) ) {
					$sidebars_widgets[ $new_id ] = $sidebars_widgets[ $old_id ];
				}

				// Remove old widget area and save.
				unset( $sidebars_widgets[ $old_id ] );
				#echo '<pre>'; print_r( $sidebars_widgets ); echo '</pre>'; exit;
				wp_set_sidebars_widgets( $sidebars_widgets );
			}

			// Redirect back to sidebar edit screen with an update message.
			$name = ( ! empty( $new_name ) ) ? $new_name : $current_name;
			$redirect_link = self::get_sidebar_edit_link( $name, array( 'message' => 1 ) );
			wp_safe_redirect( esc_url_raw( $redirect_link ) );
			exit;
		}
	}

	/**
	 * Display messages on the widgets page.
	 *
	 * @since 1.1.0
	 */
	public static function widgets_page_messages() {
		$sidebars = simple_page_sidebars_get_names();

		// Display an error message if a default sidebar hasn't been selected on the Reading settings screen.
		if ( ! get_option( 'simple_page_sidebars_default_sidebar' ) && ! empty( $sidebars ) ) {
			echo '<div class="error"><p>' . self::get_empty_default_sidebar_error() . '</p></div>';
		}

		// Display any custom update messages.
		if ( isset( $_REQUEST['simple-page-sidebars-message'] ) && ! empty( $_REQUEST['simple-page-sidebars-message'] ) ) {
			?>
			<div id="message" class="updated">
				<p>
					<?php
					$messages = array(
						1 => __( 'The sidebar you were editing is no longer assigned to any pages and has been removed. Any widgets it contained should be in an "Inactive Widgets" area below.', 'simple-page-sidebars' )
					);

					$message_id = $_REQUEST['simple-page-sidebars-message'];
					if ( isset( $messages[ $message_id ] ) ) {
						echo $messages[ $message_id ];
					}
					?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Get a page's sidebar.
	 *
	 * Sanitizes the sidebar name before returning it.
	 *
	 * @since 1.1.0
	 * @param int $page_id ID of the page whose sidebar should be returned.
	 * @return string Sanitized sidebar name.
	 */
	public static function get_page_sidebar( $page_id ) {
		return self::sanitize_sidebar_name( get_post_meta( $page_id, '_sidebar_name', true ) );
	}

	/**
	 * Retrieve IDs of pages using a particular sidebar.
	 *
	 * @since 1.1.0
	 * @param string $sidebar The sidebar name.
	 * @return array An array of page IDs or an empty array.
	 */
	public static function get_page_ids_using_sidebar( $sidebar ) {
		global $wpdb;

		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID
			FROM $wpdb->posts p
			INNER JOIN $wpdb->postmeta pm ON p.ID=pm.post_id
			WHERE p.post_type='page' AND p.post_status!='auto-draft' AND pm.meta_key='_sidebar_name' AND pm.meta_value=%s",
			$sidebar
		) );

		return ( empty( $ids ) ) ? array() : $ids;
	}

	/**
	 * Sanitize a sidebar name.
	 *
	 * @since 1.1.0
	 * @param string $name The sidebar name.
	 * @return string Sanitized sidebar name.
	 */
	public static function sanitize_sidebar_name( $name ) {
		return trim( wp_strip_all_tags( $name ) );
	}

	/**
	 * Get the edit link for a sidebar.
	 *
	 * @since 1.1.0
	 * @param string $sidebar The sidebar name.
	 * @param array $query_args Optional. An array of additional query args to append to the edit link.
	 * @return string The URL to edit the sidebar.
	 */
	public static function get_sidebar_edit_link( $sidebar, $query_args = array() ) {
		$query_args = wp_parse_args( $query_args, array(
			'page' => 'simple-page-sidebars',
			'sidebar' => rawurlencode( $sidebar )
		) );

		$link = add_query_arg( $query_args, admin_url( 'admin.php' ) );

		return $link;
	}

	/**
	 * The error message to display if a default sidebar hasn't been selected.
	 *
	 * This is used a few times throughout the dashboard, so the string is
	 * abstracted out here.
	 *
	 * @since 1.1.0
	 * @return string Error message
	 */
	public static function get_empty_default_sidebar_error() {
		return sprintf( __( 'For Simple Page Sidebars to work, a default sidebar needs to be selected on the %s', 'simple-page-sidebars' ),
			' <a href="' . admin_url( 'options-reading.php' ) . '">' . __( 'Reading settings screen', 'simple-page-sidebars' ) . '</a>.'
		);
	}

	/**
	 * Backward compatible AJAX spinner.
	 *
	 * Displays the correct AJAX spinner depending on the version of WordPress.
	 *
	 * @since 1.1.0
	 *
	 * @param array $args Array of args to modify output.
	 */
	public static function spinner( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'id' => '',
			'class' => ''
		) );

		if ( version_compare( get_bloginfo( 'version' ), '3.5-beta-1', '<' ) ) {
			printf( '<img src="%1$s" id="%2$s" class="%3$s" alt="">',
				esc_url( SIMPLE_PAGE_SIDEBARS_URL . 'admin/images/wpspin_light.gif' ),
				esc_attr( $args['id'] ),
				esc_attr( $args['class'] )
			);
		} else {
			printf( '<span id="%s" class="spinner"></span>', esc_attr( $args['id'] ) );
		}
	}
}
