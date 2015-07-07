<?php
/**
 * Area widget class.
 *
 * A widget for display another widget area within a sidebar.
 *
 * @since 0.2.0
 */
class Simple_Page_Sidebars_Widget_Area extends WP_Widget {
	/**
	 * Widget constructor method.
	 *
	 * @since 0.2.0
	 */
	public function __construct() {
		$widget_ops = array(
			'classname' => 'widget_area',
			'description' => __( 'Include all widgets from another widget area', 'simple-page-sidebars' )
		);

		// Call the parent constructor.
		parent::__construct( 'area', __( 'Widget Area', 'simple-page-sidebars' ), $widget_ops );
	}

	/**
	 * Display the widget.
	 *
	 * @since 0.2.0
	 */
	public function widget( $args, $instance ) {
		extract( $args );

		// Don't allow an infinite loop.
		if ( $id != $instance['area_id'] ) {
			echo $before_widget;

				$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
				echo ( empty( $title ) ) ? '' : $before_title . $title . $after_title;

				echo '<div class="widget-area-inside">';
					dynamic_sidebar( $instance['area_id'] );
				echo '</div>';

			echo $after_widget;
		}
	}

	/**
	 * Display the form for modifying the widget settings.
	 *
	 * @since 0.2.0
	 */
	public function form( $instance ) {
		global $wp_registered_sidebars;

		$instance = wp_parse_args( (array) $instance, array(
			'area_id' => '',
			'title'   => ''
		) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'simple-page-sidebars' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'area_id' ); ?>"><?php _e( 'Area Name:', 'simple-page-sidebars' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'area_id' ); ?>" id="<?php echo $this->get_field_id( 'area_id' ); ?>" class="widefat">
				<option value=""></option>
				<?php
				foreach ( $wp_registered_sidebars as $id => $area ) {
					if ( false === strpos( $id, 'orphaned_widgets_' ) && 'wp_inactive_widgets' != $id ) {
						printf( '<option value="%s"%s>%s</option>',
							esc_attr( $id ),
							selected( $id, $instance['area_id'], false ),
							esc_html( $area['name'] )
						);
					}
				}
				?>
			</select>
		</p>
		<?php
	}

	/**
	 * Update the widget settings.
	 *
	 * @since 0.2.0
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title'] = wp_strip_all_tags( $new_instance['title'] );
		$instance['area_id'] = $new_instance['area_id'];

		return $instance;
	}
}
