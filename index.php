<?php

/*
 * Plugin Name:       Reliably HEIC
 * Plugin URI:        https://soledadpenades.com/projects/wordpress/reliably-heic/
 * Description:       Reliably support HEIC files in WordPress
 * Version:           1.1.0
 * Author:            Soledad Penadés
 * Author URI:        https://soledadpenades.com/
 * Text Domain:       reliably-heic
 */

 defined( 'ABSPATH' ) || exit;

 require_once('RHEIC_Plugin.php');

 $plugin = new RHEIC_Plugin();
 $plugin->setup();