<?php

	/**
	 * @package Spiro Page Blocks
	 * @author Puggan Sundragon <puggan@spiro.se>
	 * @version 1.0.0
	 */

	/*
		Plugin Name: Spiro Page Blocks
		Description: A simple post type that can be listed on pages using shortcode
		Version: 1.0.0
		Author: Puggan Sundragon <puggan@spiro.se>
		Author URI: https://spiro.se
		Text Domain: page_blocks
		Domain Path: /lang
	*/

	namespace SpiroAB;
	require_once __DIR__ . '/PageBlocks.php';
	new PageBlocks();

