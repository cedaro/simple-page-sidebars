<?php
/**
 * Custom page checklist walker.
 *
 * @since 1.1.0
 */
class Simple_Page_Siders_Walker_Page_Checklist extends Walker_Page {
	/**
	 * @see Walker::start_el()
	 * @since 1.1.0
	 *
	 * @param string $output Passed by reference. Used to append additional content.
	 * @param object $page Page data object.
	 * @param int $depth Depth of page. Used for padding.
	 * @param int $current_page Page ID.
	 * @param array $args
	 */
	function start_el( &$output, $page, $depth = 0, $args = array(), $current_page = 0 ) {
		if ( $depth ) {
			$indent = str_repeat( "\t", $depth );
		} else {
			$indent = '';
		}

		$current_sidebar = Simple_Page_Sidebars_Admin::get_page_sidebar( $page->ID );

		$output .= sprintf( '%s<li><label class="selectit"><input type="checkbox" name="simplepagesidebars_sidebar_pages[]" value="%d"%s> %s%s</label>',
			$indent,
			$page->ID,
			checked( in_array( $page->ID, $args['selected'] ), true, false ),
			apply_filters( 'the_title', $page->post_title, $page->ID ),
			( $current_sidebar ) ? ' <em class="description" style="font-size: 11px">(' . $current_sidebar . ')</em>' : ''
		);
	}
}
