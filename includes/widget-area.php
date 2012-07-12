<?php
class Simple_Page_Sidebars_Widget_Area extends WP_Widget {
	function Simple_Page_Sidebars_Widget_Area() {
		$widget_ops = array(
			'classname' => 'widget_area',
			'description' => __( 'Include all widgets from another widget area', 'simple-page-sidebars' )
		);
		
		$this->WP_Widget( 'area', __( 'Widget Area', 'simple-page-sidebars' ), $widget_ops );
	}
 
	function widget( $args, $instance ) {
		extract( $args );
		
		// don't want to create a never ending loop!
		if ( $id != $instance['area_id'] ) {
			echo $before_widget;
				
				echo ( ! empty( $instance['title'] ) ) ? $before_title . esc_html( $instance['title'] ) . $after_title : '';
				
				echo '<div class="widget-area-inside">';
					dynamic_sidebar( $instance['area_id'] );
				echo '</div>';
				
			echo $after_widget;
		}
	}
 
	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = esc_html( $new_instance['title'] );
		$instance['area_id'] = $new_instance['area_id'];
	
		return $instance;
	}
 
	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array( 'area_id' => '', 'title' => '' ) );
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'simple-page-sidebars' ); ?></label>
			<input type="text" name="<?php echo $this->get_field_name( 'title' ); ?>" id="<?php echo $this->get_field_id('title'); ?>" value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'area_id' ); ?>"><?php _e( 'Area Name:', 'simple-page-sidebars' ); ?></label>
			<select name="<?php echo $this->get_field_name( 'area_id' ); ?>" id="<?php echo $this->get_field_id( 'area_id' ); ?>" class="widefat">
				<option value=""></option>';
				<?php
				global $wp_registered_sidebars;
				
				foreach ( $wp_registered_sidebars as $id => $area ) {
					echo '<option value="' . $id . '"' . selected( $id, $instance['area_id'], false ) . '>' . esc_html( $area['name'] ) . '</option>';
				}
				?>
			</select>
		</p>
		<?php
	}
}
add_action( 'widgets_init', create_function( '', 'return register_widget("Simple_Page_Sidebars_Widget_Area");' ) );
?>