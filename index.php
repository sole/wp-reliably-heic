<?php

/*
 * Plugin Name:       Reliably HEIC
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Reliably support HEIC files in WordPress
 * Version:           1.0.0
 * Author:            Soledad Penadés
 * Author URI:        https://soledadpenades.com/
 * Text Domain:       reliably-heic
 */

 require_once('ReliablyHEICPlugin.php');

 $plugin = new ReliablyHEICPlugin();
 $plugin->setup();