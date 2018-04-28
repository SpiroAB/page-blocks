<?php
	/**
	 * @package Spiro Page Blocks
	 * @author Spiro
	 * @version 0.0.3
	 * @filelocation: wp-content/plugins/spiro-page-blocks/spiro-page-blocks.php
	 */
	/*
		Plugin Name: Spiro Page Blocks
		Description: Add content-type page-blocks, and shortcode
		Version: 0.0.3
		Author: Spiro
		Author URI: https://spiro.se
		Text Domain: spiro_page_blocks
		Domain Path: /lang
	*/

	class SpiroPageBlocks
	{
		public function __construct()
		{
			add_action( 'init', [$this, 'init'] );
			add_shortcode( 'pageblocks', [$this, 'shortcode'] );
			add_shortcode( 'box', [$this, 'box_shortcode'] );
			add_shortcode( 'column', [$this, 'column_shortcode'] );
		}

		public function init()
		{
			load_plugin_textdomain('spiro_page_blocks', FALSE, dirname(plugin_basename(__FILE__)) . '/lang/');
			register_post_type(
				'page-block',
				[
					'labels' => [
						'name' => __('Page blocks', 'spiro_page_blocks'),
						'singular_name' => __('Page block', 'spiro_page_blocks'),
						'add_new' => __('Create', 'spiro_page_blocks'),
						'add_new_item' => __('Create page block', 'spiro_page_blocks'),
						'edit_item' => __('Edit page block', 'spiro_page_blocks'),
						'new_item' => __('Create page block', 'spiro_page_blocks'),
						'all_items' => __('All page blocks', 'spiro_page_blocks'),
						'view_item' => __('Show page block', 'spiro_page_blocks'),
						'search_items' => __('Search page block', 'spiro_page_blocks'),
						'not_found' => __('page block not found', 'spiro_page_blocks'),
						'menu_name' => __('Page blocks', 'spiro_page_blocks'),
					],
					'public' => TRUE,
					'capability_type' => 'page',
					'supports' => [
						'title',
						'editor',
						'author',
						'custom-fields',
						'revisions',
						'page-attributes',
						'thumbnail',
					],
					'taxonomies' => [
						'category'
					],
					'has_archive' => FALSE,
					'orderby' => 'menu_order',
				]
			);
		}

		public function shortcode($attributes)
		{
			$default_attributes = [
				'cat' => NULL,
				'class' => NULL
			];

			$classes = ['page_block_list'];

			$page_name = NULL;
			if(isset($GLOBALS['wp_query']->post))
			{
				$page_name = get_post_meta($GLOBALS['wp_query']->post->ID, 'navigation', true);
			}

			$attributes = shortcode_atts($default_attributes, $attributes);
			$filters = [
				'post_type' => 'page-block',
				'orderby' => 'menu_order',
				'order'   => 'ASC',
			];

			if ($attributes['class']) {
				$classes[] = $attributes['class'];
			}

			if(!$attributes['cat'])
			{
				return '[pageblocks cat="" error="no category"]';
			}

			if(is_numeric($attributes['cat']))
			{
				$category = get_term( (int) $attributes['cat'], 'category' );
			}
			else
			{
				$category = get_term_by( 'slug', $attributes['cat'], 'category' );
			}
			if(!$category)
			{
				return '[pageblocks cat="' . htmlentities($attributes['cat']) . '" error="category not found"]';
			}
			$classes[] = 'page_block_list-' . $category->slug;
			$filters['cat'] = $category->term_id;

			do_action( 'page_part_list', $category, 'page-block');

			$query = new WP_Query( $filters );

			$classes = implode(' ', $classes);
			/**
			 * @var string[] $navigations
			 * @var string[] $html_top
			 * @var string[] $html_main
			 * @var string[] $html_foot
			 */
			$navigations = [];
			$html_top = [];
			$html_main = [];
			$html_foot = [];
			$html_top['head'] = '<div class="'. $classes .'">';

			foreach($query->get_posts() as $current_post)
			{
				$classes = implode(' ', get_post_class('page_block page_block-' . $current_post->post_name, $current_post->ID));

				$attributes = [];
				$attributes['class'] = 'class="' . htmlentities($classes) . '"';

				$navigation_name = get_post_meta($current_post->ID, 'navigation', true);
				$navigation_slug = sanitize_title($navigation_name);
				if($navigation_name) {
					$navigations[$navigation_slug] = $navigation_name;
					$attributes['id'] = 'id="' . htmlentities($navigation_slug) . '"';
				}

				do_action( 'page_part_item', $current_post->ID, $navigation_name ?: $current_post->post_title, 'page-block');

				if(has_post_thumbnail($current_post))
				{
					$attributes['class'] = 'class="page_block_bg_image ' . htmlentities($classes) . '"';

					$image_url = esc_url(get_the_post_thumbnail_url($current_post, [1920, 0]));
					$attributes['style'] = 'style="background-image: url(' . $image_url . ')"';
				}

				$edit_url = get_edit_post_link($current_post->ID);

				$html_main[] = '<div ' . implode(' ', $attributes) . '>';
				$html_main[] = '<div class="inner">';
				if($edit_url) {
					$html_main[] = '<a class="inline_edit_button" href="' . htmlentities($edit_url, null, null, null) . '"><i class="fa fa-edit"></i></a>';
				}
				$html_main[] = apply_filters('the_content', $current_post->post_content);
				$html_main[] = '</div>';
				$html_main[] = '</div>';
			}

			if(count($html_main)< 2) return '[pageblocks cat="' . htmlentities($attributes['cat']) . '" error="category empty"]';

			if($navigations)
			{
				$navigations_links = [];
				if($page_name)
				{
					$navigations_links['header'] = '<li class="nav-item"><a href="#header" class="nav-link">' . htmlentities($page_name) . '</a></li>';
				}
				foreach($navigations as $nav_slug => $nav_title)
				{
					$navigations_links[$nav_slug] = '<li class="nav-item"><a href="#' . htmlentities($nav_slug) . '" class="nav-link">' . htmlentities($nav_title) . '</a></li>';
				}
				$html_top['nav'] = '<div class="page_block_navigation"><ul class="nav nav-pills">' . implode(' ', $navigations_links) . '</ul></div>';
			}
			else
			{
				$html_top['nav'] = '<div class="page_block_navigation page_block_no_navigation navbar"></div>';
			}

			$html_foot['foot'] = '</div>';

			return implode(PHP_EOL, $html_top + $html_main + $html_foot);
		}

		public function box_shortcode($attributes, $content)
		{
			$default_attributes = [
				'class' => NULL
			];
			$attributes = shortcode_atts($default_attributes, $attributes);

			if ($attributes['class']) {
				$custom_class = $attributes['class'];
			}

			return '<div class="box ' . $custom_class .'">' . apply_filters('the_content', $content) . '</div>';
		}

		public function column_shortcode($attributes, $content)
		{
			return '<div class="column">' . apply_filters('the_content', $content) . '</div>';
		}
	}
	
	new SpiroPageBlocks();

