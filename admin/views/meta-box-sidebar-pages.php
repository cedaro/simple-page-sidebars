<div id="posttype-page" class="posttypediv">
	<p>
		<?php
		printf( __( 'The above sidebar will replace the "<strong>%s</strong>" sidebar for all pages selected below.', 'simple-page-sidebars' ), $default_sidebar );
		echo ' ';
		_e( 'Any currently assigned custom sidebars will also be overridden for the selected pages.', 'simple-page-sidebars' );
		?>
	</p>

	<div id="page-all" class="tabs-panel tabs-panel-view-all tabs-panel-active">
		<ul id="pagechecklist" class="list:page categorychecklist form-no-clear">
			<?php
			$posts = get_posts( array(
				'post_type' => 'page',
				'order' => 'ASC',
				'orderby' => 'title',
				'posts_per_page' => -1,
				'suppress_filters' => true,
				'cache_results' => false
			) );

			$args['sidebar'] = self::sanitize_sidebar_name( stripslashes( $_GET['sidebar'] ) );
			$args['selected'] = self::get_page_ids_using_sidebar( $args['sidebar'] );
			$args['walker'] = new Simple_Page_Siders_Walker_Page_Checklist;

			$items = walk_page_tree( $posts, 0, 0, $args );
			echo $items;
			?>
		</ul>
	</div><!-- end div.tabs-panel -->

	<p style="margin: 5px 0 0 0">
		<span class="description"><?php _e( 'To delete this sidebar, simply uncheck all pages and click the "Update Sidebar" button.', 'simple-page-sidebars' ); ?></span>
	</p>
</div><!-- end div.posttypediv -->