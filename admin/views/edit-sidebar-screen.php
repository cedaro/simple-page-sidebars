<div class="wrap simple-page-sidebars-edit-sidebar">
	<div id="icon-tools" class="icon32"><br></div>
	<h2><?php _e( 'Edit Sidebar', 'simple-page-sidebars' ); ?></h2>

	<?php
	// Display an error message if a default sidebar hasn't been selected on the Reading settings screen.
	if ( ! get_option( 'simple_page_sidebars_default_sidebar' ) ) {
		echo '<div class="error"><p>' . self::get_empty_default_sidebar_error() . '</p></div>';
	}

	// Display an error message and stop rendering the screen if the requested sidebar is not valid.
	if ( ! $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key='_sidebar_name' AND meta_value=%s", $sidebar_name ) ) ) {
		echo '<div class="error"><p>' . __( 'Whoops, that doesn\'t appear to be a sidebar that can be edited.', 'simple-page-sidebars' ) . '</p></div>';
		echo '</div>'; // close div.wrap
		return;
	}

	// Display any custom update messages.
	if ( isset( $_REQUEST['message'] ) ) {
		?>
		<div id="message" class="updated">
			<p>
				<?php
				$messages = array(
					1 => sprintf( '%s <a href="' . esc_url( admin_url( 'widgets.php' ) ) . '">%s</a>',
						__( 'Sidebar settings updated.', 'simple-page-sidebars' ),
						__( 'Update widgets now.', 'simple-page-sidebars' )
					)
				);

				if ( ! empty( $_REQUEST['message'] ) && isset( $messages[ $_REQUEST['message'] ] ) ) {
					echo $messages[ $_REQUEST['message'] ];
				}

				$_SERVER['REQUEST_URI'] = remove_query_arg( array( 'message' ), $_SERVER['REQUEST_URI'] );
				?>
			</p>
		</div>
		<?php
	}
	?>

	<form action="" method="post">
		<div id="poststuff">
			<div id="post-body" class="metabox-holder"><!--columns-2-->

				<div id="post-body-content">
					<div class="sidebar-name-wrap">
						<label for="simple-page-sidebars-sidebar-name-new" class="screen-reader-text"><?php _e( 'Sidebar Name:', 'simple-page-sidebars' ); ?></label>
						<input type="text" name="simplepagesidebars_sidebar_name_new" id="simple-page-sidebars-sidebar-name-new" value="<?php echo esc_attr( $sidebar_name ); ?>" placeholder="<?php esc_attr_e( 'Enter sidebar name here', 'simple-page-sidebars' ); ?>" autocomplete="off">
						<input type="hidden" name="simplepagesidebars_sidebar_name" value="<?php echo esc_attr( $sidebar_name ); ?>">
					</div>

					<?php do_meta_boxes( $screen->id, 'normal', '' ); ?>

					<p class="submit">
						<?php
						wp_nonce_field( 'update-sidebar_' . $sidebar_name, 'simplepagesidebars_sidebar_update_nonce', true );
						wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
						wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false );
						?>
						<input type="submit" name="simplepagesidebars_sidebar_update" value="<?php esc_attr_e( 'Update Sidebar', 'simple-page-sidebars' ); ?>" class="button-primary">
						<!--<a href="#">Delete Sidebar</a>-->
					</p>
				</div>

				<!--<div id="postbox-container-1" class="postbox-container"></div>-->
			</div>
		</div>
	</form>

</div>

<style type="text/css">
.sidebar-name-wrap { margin: 0 0 20px 0;}
.sidebar-name-wrap input { padding: 3px 8px; width: 100%; font-size: 1.7em;}
.sidebar-name-wrap input:-moz-placeholder { color: #a9a9a9;}
.sidebar-name-wrap input::-webkit-input-placeholder { padding: 3px 0; color: #a9a9a9;}
</style>