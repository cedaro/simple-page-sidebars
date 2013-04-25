<div id="simple-page-sidebars-page-sidebar-update-message" class="simple-page-sidebars-page-sidebar-feedback" style="display: none">
	<p>
		<?php _e( 'Sidebar saved.', 'simple-page-sidebars' ); ?>
		<a href="<?php echo admin_url( 'widgets.php' ); ?>"><?php _e( 'Update widgets now.', 'simple-page-sidebars' ); ?></a>
	</p>
</div>

<p>
	<label for="simple-page-sidebars-page-sidebar-name"><?php _e( 'Current sidebar:', 'simple-page-sidebars' ); ?></label>
	<select name="simplepagesidebars_page_sidebar_name" id="simple-page-sidebars-page-sidebar-name" class="widefat">
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

	<label for="simple-page-sidebars-page-sidebar-name-new" class="screen-reader-text"><?php _e( 'Or create a new sidebar:', 'simple-page-sidebars' ); ?></label>
	<input type="text" name="simplepagesidebars_page_sidebar_name_new" id="simple-page-sidebars-page-sidebar-name-new" class="widefat hide-if-js" value="">

	<span id="sidebarnew" class="hide-if-no-js"><?php _e( 'Enter New', 'simple-page-sidebars' ); ?></span>
	<span id="sidebarcancel" class="hidden"><?php _e( 'Cancel', 'simple-page-sidebars' ); ?></span>
</p>

<p style="margin-top: 10px; margin-bottom: 0; text-align: right">
	<?php self::spinner( array( 'id' => 'simple-page-sidebars-page-sidebar-update-spinner' ) ); ?>
	<button class="button"><?php _e( 'Update', 'simple-page-sidebars' ); ?></button>
</p>

<style type="text/css">
#sidebarcancel, #sidebarnew { cursor: pointer; float: left; margin: 3px 3px 0 3px; color: #21759b; font-size: 12px;}
#sidebarcancel, #sidebarnew:hover { color: #d54e21;}
#simple-page-sidebars-page-sidebar-update-spinner { display: none; margin: 0 5px 0 0; vertical-align: middle;}

.simple-page-sidebars-page-sidebar-feedback { clear: both; margin: 1em 0; padding: 0 0.6em; color: #333; background-color: #ffffe0; border: 1px solid #e6db55;
	-moz-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-radius: 3px;}
.simple-page-sidebars-page-sidebar-feedback a { text-decoration: none;}
.simple-page-sidebars-page-sidebar-feedback p { margin: 0.5em 0; padding: 2px;}
.simple-page-sidebars-page-sidebar-feedback-error { background-color: #ffebe8; border-color: #cc0000;}
.simple-page-sidebars-page-sidebar-feedback-error a { color: #cc0000; text-decoration: underline;}
</style>

<script type="text/javascript">
jQuery(function($) {
	var simplePageSidebars = {
		spinner      : $('#simple-page-sidebars-page-sidebar-update-spinner'),
		nameField    : $('#simple-page-sidebars-page-sidebar-name'),
		nameFieldNew : $('#simple-page-sidebars-page-sidebar-name-new'),

		init : function() {
			$('#sidebarcancel, #sidebarnew').on('click', function(e) {
				e.preventDefault();

				simplePageSidebars.nameField.toggle();
				simplePageSidebars.nameFieldNew.toggle();

				$('#sidebarcancel, #sidebarnew').toggle();

				// Clear the new sidebar name field when cancel is clicked.
				if ( 'sidebarcancel' == $(this).attr('id') ) {
					simplePageSidebars.nameFieldNew.val('');
				}
			});
		},

		update : function() {
			simplePageSidebars.spinner.show();

			$.post(
				ajaxurl,
				{
					action : 'simplepagesidebars_update_page_sidebar',
					post_id : $('#post_ID').val(),
					simplepagesidebars_page_sidebar_name : simplePageSidebars.nameField.val(),
					simplepagesidebars_page_sidebar_name_new : simplePageSidebars.nameFieldNew.val(),
					simplepagesidebars_page_sidebar_update_nonce : $('input[name="simplepagesidebars_page_sidebar_update_nonce"]').val()
				},
				function( data ){
					var newName = simplePageSidebars.nameFieldNew.val();

					if ( '' != newName ) {
						if ( simplePageSidebars.nameField.find('option[value="' + newName + '"]').length < 1 ) {
							simplePageSidebars.nameField.append('<option selected="selected">' + newName + '</option>').val( newName );
						} else {
							simplePageSidebars.nameField.find('option[value="' + newName + '"]').attr('selected', 'selected');
						}

						simplePageSidebars.nameField.toggle();
						simplePageSidebars.nameFieldNew.toggle().val('');
						$('#sidebarcancel, #sidebarnew').toggle();
					}

					$('#simple-page-sidebars-page-sidebar-update-message').show();
					simplePageSidebars.spinner.hide();
				}
			);
		}
	};

	simplePageSidebars.init();

	$('#simplepagesidebarsdiv').find('button').on( 'click', function(e) {
		e.preventDefault();
		simplePageSidebars.update();
	});
});
</script>
<div class="clear"></div>