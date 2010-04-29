<?php
/*
Plugin Name: Category Checklist Tree
Version: 1.1-beta
Description: Preserves the category hierarchy on the post editing screen
Author: scribu
Author URI: http://scribu.net
Plugin URI: http://scribu.net/wordpress/category-checklist-tree
*/

Category_Checklist::init();

class Category_Checklist {

	function init() {
		add_action('add_meta_boxes', array(__CLASS__, 'replace_box'));
	}

	// adapted from wp-admin/edit-form-advanced.php
	function replace_box($post_type) {
		foreach ( get_object_taxonomies($post_type) as $tax_name ) {
			$taxonomy = get_taxonomy($tax_name);
			if ( !$taxonomy->show_ui || !$taxonomy->hierarchical )
				continue;

			$label = isset($taxonomy->label) ? esc_attr($taxonomy->label) : $tax_name;

            remove_meta_box($tax_name . 'div', $post_type, 'side');

			// don't use 'core' as priority
			add_meta_box($tax_name . 'div', $label, array(__CLASS__, 'meta_box'), $post_type, 'side', 'high', array( 'taxonomy' => $tax_name ));
		}
	}

	// pasted from wp-admin/includes/meta-boxes.php -> post_categories_meta_box()
	function meta_box( $post, $box ) {
	$defaults = array('taxonomy' => 'category');
	if ( !isset($box['args']) || !is_array($box['args']) )
		$args = array();
	else
		$args = $box['args'];
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );
	$tax = get_taxonomy($taxonomy);

	?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
		<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php printf( __( 'All %s' ), $tax->label ); ?></a></li>
			<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
		</ul>

		<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
			<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
			</ul>
		</div>

		<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
			<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
				<?php wp_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids, 'checked_ontop' => false ) ) /* <= only change */ ?>
			</ul>
		</div>
	<?php if ( !current_user_can($tax->assign_cap) ) : ?>
	<p><em><?php _e('You cannot modify this Taxonomy.'); ?></em></p>
	<?php endif; ?>
	<?php if ( current_user_can($tax->edit_cap) ) : ?>
			<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
				<h4><a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3"><?php printf( __( '+ Add New %s' ), $tax->singular_label ); ?></a></h4>
				<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php printf( __( 'Add New %s' ), $tax->singular_label ); ?></label><input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( sprintf( 'New %s Name', $tax->singular_label ) ); ?>" tabindex="3" aria-required="true"/>
					<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent"><?php printf( __('Parent %s'), $tax->singular_label ); ?>:</label><?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => sprintf( __('&mdash; Parent %s &mdash;'), $tax->singular_label ), 'tab_index' => 3 ) ); ?>
					<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php esc_attr_e( 'Add' ); ?>" tabindex="3" />
					<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce', false ); ?>
					<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php
	}
}

