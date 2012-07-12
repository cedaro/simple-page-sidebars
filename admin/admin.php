<?php
class Simple_Page_Sidebars_Admin {
	function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
	}
	
	function init() {
		add_action( 'save_post', array( &$this, 'update_sidebar' ) );
		add_action( 'wp_ajax_simple_page_sidebars_update_page_sidebar', array( &$this, 'update_sidebar' ) );
		
		add_action( 'admin_menu', array( &$this, 'add_sidebar_meta_box' ) );
		
		add_filter( 'manage_pages_columns', array( &$this, 'manage_pages_columns' ) );
		add_action( 'manage_pages_custom_column', array( &$this, 'manage_pages_custom_column' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( &$this, 'quick_edit_custom_box' ), 10, 2 );
		add_action( 'admin_footer-edit.php', array( &$this, 'quick_edit_js' ) );
		add_action( 'bulk_edit_custom_box', array( &$this, 'bulk_edit_custom_box' ), 10, 2 );
	}
	
	/**
	 * Register setting for choosing the default sidebar
	 *
	 * @since 0.2
	 */
	function admin_init() {
		add_settings_field( 'simple_page_sidebars_default_sidebar', '<label for="simple-page-sidebars-default-sidebar">' . __( ' Default Sidebar', 'simple-page-sidebars' ) . '</label>', array( &$this, 'default_sidebar_settings_field' ), 'reading' );
		register_setting( 'reading', 'simple_page_sidebars_default_sidebar', array( &$this, 'register_reading_setting' ) );
	}
	
	function register_reading_setting( $input ) { return $input; }
	
	/**
	 * Default sidebar option dropdown
	 *
	 * @since 0.2
	 * @uses $wp_registered_sidebars
	 */
	function default_sidebar_settings_field() {
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
		<span class="description"><?php _e( 'The sidebar that will replaced by custom sidebars.', 'simple-page-sidebars' ); ?></span>
		<?php
	}
	
	/**
	 * Save custom page sidebar
	 *
	 * Processes AJAX requests and normal post backs.
	 * @since 0.2
	 */
	function update_sidebar( $post_id = 0 ) {
		if ( 0 == $post_id )
			$post_id = $_POST['post_id'];
		
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || 'page' != get_post_type( $post_id ) )
			return $post_id;
		
		// verify either an individual post nonce or the bulk edit nonce
		// requests can come from a page update, ajax from the sidebar meta box, quick edit, or bulk edit
		$sidebar_name_nonce = ( isset( $_REQUEST['sidebar_name_nonce'] ) && wp_verify_nonce( $_REQUEST['sidebar_name_nonce'], 'update-page-sidebar-name-' . $post_id ) ) ? true : false;
		$bulk_sidebar_name_nonce = ( isset( $_REQUEST['bulk_sidebar_name_nonce'] ) && wp_verify_nonce( $_REQUEST['bulk_sidebar_name_nonce'], 'bulk-update-page-sidebar-name' ) ) ? true : false;
		if ( ! $sidebar_name_nonce && ! $bulk_sidebar_name_nonce ) {
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				exit;
			} else {
				return;
			}
		}
		
		// if 'new_sidebar_name' is set and not empty, it supercedes any 'sidebar_name' setting
		// if 'sidebar_name' is blank or it equals 'default', delete meta
		// if 'sidebar_name' is set and not empty, update to new name
		// if 'sidebar_name' is -1, skip
		
		// bulk edit uses $_GET for some reason, so we use the $_REQUEST global
		if ( isset( $_REQUEST['new_sidebar_name' ] ) && ! empty( $_REQUEST['new_sidebar_name'] ) ) {
			update_post_meta( $post_id, '_sidebar_name', $_REQUEST['new_sidebar_name'] );
		} else {
			// if $_REQUEST['sidebar_name'] isn't set, we don't want to update the sidebar meta value
			$sidebar = ( isset( $_REQUEST['sidebar_name'] ) ) ? $_REQUEST['sidebar_name'] : -1;
			
			if ( empty( $sidebar ) || 'default' == $sidebar ) {
				delete_post_meta( $post_id, '_sidebar_name' );
			} elseif ( -1 != intval( $sidebar ) ) {
				update_post_meta( $post_id, '_sidebar_name', $_REQUEST['sidebar_name'] );
			}
		}
	}
	
	/**
	 * Register sidebar meta box
	 *
	 * @since 0.2
	 */
	function add_sidebar_meta_box() {
		add_meta_box( 'simplepagesidebarsdiv', 'Sidebar', array( &$this, 'sidebar_meta_box' ), 'page', 'side', 'core' );
	}
	
	/**
	 * Meta box for adding a new sidebar or choosing an existing sidebar
	 *
	 * @since 0.2
	 * @uses $wp_registered_sidebars, $wpdb
	 */
	function sidebar_meta_box( $page ) {
		global $wp_registered_sidebars, $wpdb;
		
		$sidebar = get_post_meta( $page->ID, '_sidebar_name', true );
		
		$custom_sidebars = simple_page_sidebars_get_names();
		
		wp_nonce_field( 'update-page-sidebar-name-' . $page->ID, 'sidebar_name_nonce', false );
		
		$default_sidebar_id = get_option( 'simple_page_sidebars_default_sidebar' );
		if ( ! $default_sidebar_id ) {
			echo '<p class="error">';
				printf( __( 'For Simple Page Sidebars to work, a default sidebar needs to be selected on the %s', 'simple-page-sidebars' ),
					' <a href="' . admin_url( 'options-reading.php' ) . '">' . __( 'Reading options panel', 'simple-page-sidebars') . '</a>.'
				);
			echo '</p>';
		}
		?>
		<p>
			<label for="sidebar-name"><?php _e( 'Current sidebar:', 'simple-page-sidebars' ); ?></label>
			<select name="sidebar_name" id="sidebar-name" class="widefat">
				<option value="default"><?php _e( 'Default Sidebar', 'simple-page-sidebars' ); ?></option>
				<?php
				foreach ( $custom_sidebars as $sb ) {
					printf( '<option value="%s"%s>%s</option>',
						esc_attr( $sb ),
						selected( $sb, $sidebar, false ),
						esc_html( $sb )
					);
				}
				?>
			</select>
			<label for="new-sidebar-name" class="screen-reader-text"><?php _e( 'Or create a new sidebar:', 'simple-page-sidebars' ); ?></label>
			<input type="text" name="new_sidebar_name" id="new-sidebar-name" class="widefat hide-if-js" value="" />
			<span id="sidebarnew" class="hide-if-no-js"><?php _e( 'Enter New', 'simple-page-sidebars' ); ?></span>
			<span id="sidebarcancel" class="hidden"><?php _e( 'Cancel', 'simple-page-sidebars' ); ?></span>
		</p>
		
		<p style="margin-top: 10px; margin-bottom: 0; text-align: right">
			<img src="<?php echo esc_url( admin_url( 'images/wpspin_light.gif' ) ); ?>" alt="" id="sidebar-update-feedback" style="display: none" />
			<button class="button"><?php _e( 'Update', 'simple-page-sidebars' ); ?></button>
		</p>
		
		<style type="text/css">
		#sidebar-update-feedback { display: none; margin: 0 5px 0 0; vertical-align: middle;}
		#sidebarcancel, #sidebarnew { cursor: pointer; float: left; margin: 3px 3px 0 3px; color: #21759b; font-size: 12px;}
		#sidebarcancel, #sidebarnew:hover { color: #d54e21;}
		#simplepagesidebarsdiv .error { color: #ee0000;}
		</style>
		
		<script type="text/javascript">
		jQuery(function($) {
			$('#sidebarcancel, #sidebarnew').click(function() {
				$('#new-sidebar-name, #sidebar-name, #sidebarcancel, #sidebarnew').toggle();
				
				// clear the new sidebar name field when cancel is clicked
				if ( 'sidebarcancel' == $(this).attr('id') ) {
					$('#new-sidebar-name').val('');
				}
			});
			
			$('#simplepagesidebarsdiv').find('button').click(function(e) {
				e.preventDefault();
				
				$('#sidebar-update-feedback').show();
				$.post(ajaxurl, {
						action : 'simple_page_sidebars_update_page_sidebar',
						post_id : $('#post_ID').val(),
						sidebar_name : $('select[name="sidebar_name"]').val(),
						new_sidebar_name : $('input[name="new_sidebar_name"]').val(),
						sidebar_name_nonce : $('input[name="sidebar_name_nonce"]').val()
					},
					function(data){
						new_sidebar_name = $('#new-sidebar-name').val();
						
						if ( '' != new_sidebar_name ) {
							if ( $('#simplepagesidebarsdiv select option[value="' + new_sidebar_name + '"]').length < 1 ) {
								$('#simplepagesidebarsdiv select').append('<option selected="selected">' + new_sidebar_name + '</option>').val(new_sidebar_name);
							} else {
								$('#simplepagesidebarsdiv select option[value="' + new_sidebar_name + '"]').attr('selected','selected');
							}
							
							$('#new-sidebar-name, #sidebar-name, #sidebarcancel, #sidebarnew').toggle().filter('input').val('');
						}
						
						$('#sidebar-update-feedback').hide();
					}
				);
			});
		});
		</script>
		
		<br class="clear" />
		<?php
	}
	
	/**
	 * Register sidebar column on All Pages screen
	 *
	 * @since 0.2
	 */
	function manage_pages_columns( $columns ) {
		$columns['sidebar'] = __( 'Sidebar', 'simple-page-sidebars' );
		return $columns;
	}
	
	/**
	 * Display sidebar column on All Pages screen
	 *
	 * @since 0.2
	 */
	function manage_pages_custom_column( $column, $page_id ) {
		if ( 'sidebar' == $column ) {
			$sidebar = get_post_meta( $page_id, '_sidebar_name', true );
			echo ( $sidebar ) ? esc_html( $sidebar ) : '';
			
			// add the nonce here and copy it to the inline editor with javascript
			wp_nonce_field( 'update-page-sidebar-name-' . $page_id, 'sidebar_name_nonce', false );
		}
	}
	
	/**
	 * Sidebar dropdown field for quick edit mode
	 *
	 * @since 0.2
	 */
	function quick_edit_custom_box( $column, $post_type ) {
		if ( 'page' != $post_type || 'sidebar' != $column )
			return;
		
		$sidebars = simple_page_sidebars_get_names();
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<div class="inline-edit-group" id="sidebar-edit-group">
					<label>
						<span class="title"><?php _e( 'Sidebar', 'simple-page-sidebars' ); ?></span>
						<select name="sidebar_name" id="sidebar-name">
							<option value="default"><?php _e( 'Default Sidebar', 'simple-page-sidebars' ); ?></option>
							<?php
							foreach ( $sidebars as $sb ) {
								printf( '<option value="%1$s">%1$s</option>', $sb );
							}
							?>
						</select>
					</label>
				</div>
			</div>
		</fieldset>
		<?php
	}
	
	/**
	 * Quick edit javascript
	 *
	 * Selects the correct sidebar during quick edit and copies the nonce for saving.
	 *
	 * @since 0.2
	 */
	function quick_edit_js() {
		$current_screen = get_current_screen();
		
		if ( 'edit-page' != $current_screen->id || 'page' != $current_screen->post_type )
			return;
		?>
		<script type="text/javascript">
		jQuery(function($) {
			$('table.pages').on('click', 'a.editinline', function(e) {
				inlineEditPost.revert();
				
				var id = inlineEditPost.getId(this);
				var currentSidebar = $('#post-' + id + ' .sidebar').text();
				
				// select the current sidebar option
				$('select#sidebar-name option').attr('selected', false);
				if ( '' != currentSidebar ) {
					$('select#sidebar-name option:contains(' + currentSidebar + ')').attr('selected', true);
				}
				
				// copy the sidebar name nonce
				$('#sidebar-edit-group').find('input[name="sidebar_name_nonce"]').remove().end().append( $('#post-' + id + ' input[name="sidebar_name_nonce"]').clone() );
			});
		});
		</script>
		<style type="text/css">
		.widefat .column-sidebar { width: 15%;}
		</style>
		<?php
	}
	
	/**
	 * Sidebar dropdown field for bulk edit mode
	 *
	 * @since 0.2
	 */
	function bulk_edit_custom_box( $column, $post_type ) {
		if ( 'page' != $post_type || 'sidebar' != $column ) { return; }
		
		$sidebars = simple_page_sidebars_get_names();
		?>
		<fieldset class="inline-edit-col-right" style="margin-top: 0">
			<div class="inline-edit-col">
				<label>
					<span class="title"><?php _e( 'Sidebar', 'simple-page-sidebars' ); ?></span>
					<select name="sidebar_name" id="sidebar-name">
						<option value="-1"><?php _e( '&mdash; No Change &mdash;', 'simple-page-sidebars' ); ?></option>
						<option value="default"><?php _e( 'Default Sidebar', 'simple-page-sidebars' ); ?></option>
						<?php
						foreach ( $sidebars as $sb ) {
							printf( '<option value="%1$s">%1$s</option>', $sb );
						}
						?>
					</select>
				</label>
				<?php wp_nonce_field( 'bulk-update-page-sidebar-name', 'bulk_sidebar_name_nonce', false ); ?>
			</div>
		</fieldset>
		<?php
	}
}
$simple_page_sidebars_admin = new Simple_Page_Sidebars_Admin();
?>