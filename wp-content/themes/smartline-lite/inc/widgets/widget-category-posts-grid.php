<?php

// Add Category Posts Grid Widget
class Smartline_Category_Posts_Grid_Widget extends WP_Widget {

	function __construct() {
		
		// Setup Widget
		$widget_ops = array(
			'classname' => 'smartline_category_posts_grid', 
			'description' => __('Display latest posts from category in a grid layout. Please use this widget ONLY on Frontpage Magazine widget area.', 'smartline-lite')
		);
		$this->WP_Widget('smartline_category_posts_grid', __('Category Posts Grid (Smartline)', 'smartline-lite'), $widget_ops);
		
		// Delete Widget Cache on certain actions
		add_action( 'save_post', array( $this, 'delete_widget_cache' ) );
		add_action( 'deleted_post', array( $this, 'delete_widget_cache' ) );
		add_action( 'switch_theme', array( $this, 'delete_widget_cache' ) );
		
	}

	public function delete_widget_cache() {
		
		delete_transient( $this->id );
		
	}
	
	private function default_settings() {
	
		$defaults = array(
			'title'				=> '',
			'number'			=> 6,
			'category'			=> 0
		);
		
		return $defaults;
		
	}
	
	// Display Widget
	function widget($args, $instance) {

		// Get Sidebar Arguments
		extract($args);
		
		// Get Widget Settings
		$defaults = $this->default_settings();
		extract( wp_parse_args( $instance, $defaults ) );
		
		// Add Widget Title Filter
		$widget_title = apply_filters('widget_title', $title, $instance, $this->id_base);
		
		// Output
		echo $before_widget;
	?>
		<div id="widget-category-posts-grid" class="widget-category-posts clearfix">
		
			<?php // Display Title
			if( !empty( $widget_title ) ) { echo $before_title . $widget_title . $after_title; }; ?>
			
			<div class="widget-category-posts-content">
			
				<?php echo $this->render($instance); ?>
				
			</div>
			
		</div>
	<?php
		echo $after_widget;
	
	}
	
	// Render Widget Content
	function render($instance) {
		
		// Get Output from Cache
		$output = get_transient( $this->id );
		
		// Generate output if not cached
		if( $output === false ) :

			// Get Widget Settings
			$defaults = $this->default_settings();
			extract( wp_parse_args( $instance, $defaults ) );
		
			// Get latest posts from database
			$query_arguments = array(
				'posts_per_page' => (int)$number,
				'ignore_sticky_posts' => true,
				'cat' => (int)$category
			);
			$posts_query = new WP_Query($query_arguments);
			$i = 0;

			// Start Output Buffering
			ob_start();
			
			// Check if there are posts
			if( $posts_query->have_posts() ) :
			
				// Limit the number of words for the excerpt
				add_filter('excerpt_length', 'smartline_frontpage_category_excerpt_length');
				
				// Display Posts
				while( $posts_query->have_posts() ) :
					
					$posts_query->the_post(); 
					
					 // Open new Row on the Grid
					 if ( $i % 2 == 0) : ?>
				
						<div class="category-posts-grid-row clearfix">
			
					<?php // Set Variable row_open to true
						$row_open = true;
						
					endif; ?>

					
					<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

						<a href="<?php the_permalink() ?>" rel="bookmark"><?php the_post_thumbnail('category_posts_wide_thumb'); ?></a>

						<h3 class="post-title"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h3>

						<div class="postmeta"><?php $this->display_postmeta($instance); ?></div>

						<div class="entry">
							<?php the_excerpt(); ?>
						</div>

					</article>

			
					<?php // Close Row on the Grid
					if ( $i % 2 == 1) : ?>
					
						</div>
					
					<?php // Set Variable row_open to false
						$row_open = false;
					
					endif; $i++;
					
				endwhile;
				
				// Remove excerpt filter
				remove_filter('excerpt_length', 'smartline_frontpage_category_excerpt_length');
				
			endif;
			
			// Reset Postdata
			wp_reset_postdata();
			
			// Get Buffer Content
			$output = ob_get_clean();
			
			// Set Cache
			set_transient( $this->id, $output, YEAR_IN_SECONDS );
			
		endif;
		
		return $output;
		
	}
	
	// Display Postmeta
	function display_postmeta($instance) { ?>

		<span class="meta-date">
		<?php printf('<a href="%1$s" title="%2$s" rel="bookmark"><time datetime="%3$s">%4$s</time></a>',
				esc_url( get_permalink() ),
				esc_attr( get_the_time() ),
				esc_attr( get_the_date( 'c' ) ),
				esc_html( get_the_date() )
			);
		?>
		</span>

	<?php if ( comments_open() ) : ?>
		<span class="meta-comments sep">
			<?php comments_popup_link( __('Leave a comment', 'smartline-lite'),__('One comment','smartline-lite'),__('% comments','smartline-lite') ); ?>
		</span>
	<?php endif;

	}

	function update($new_instance, $old_instance) {

		$instance = $old_instance;
		$instance['title'] = sanitize_text_field($new_instance['title']);
		$instance['category'] = (int)$new_instance['category'];
		$instance['number'] = (int)$new_instance['number'];
		
		$this->delete_widget_cache();
		
		return $instance;
	}

	function form( $instance ) {
		
		// Get Widget Settings
		$defaults = $this->default_settings();
		extract( wp_parse_args( $instance, $defaults ) );

?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'smartline-lite'); ?>
				<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id('category'); ?>"><?php _e('Category:', 'smartline-lite'); ?></label><br/>
			<?php // Display Category Select
				$args = array(
					'show_option_all'    => __('All Categories', 'smartline-lite'),
					'show_count' 		 => true,
					'selected'           => $category,
					'name'               => $this->get_field_name('category'),
					'id'                 => $this->get_field_id('category')
				);
				wp_dropdown_categories( $args ); 
			?>
		</p>
		
		<p>
			<label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts:', 'smartline-lite'); ?>
				<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" />
				<br/><span class="description"><?php _e('Please chose an even number (2, 4, 6, 8).', 'smartline-lite'); ?></span>
			</label>
		</p>
<?php
	}
}
register_widget('Smartline_Category_Posts_Grid_Widget');
?>