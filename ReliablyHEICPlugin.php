<?php

if(!class_exists('ReliablyHEICPlugin')) {

	class ReliablyHEICPlugin {
		public function setup() {
			add_action('admin_menu', array($this, 'setup_admin_menu'));
		}

		public function add_admin_settings() {

		}

		public function setup_admin_menu() {
			add_submenu_page(
				'options-general.php',
				'Reliably HEIC Options',
				'Reliably HEIC',
				'manage_options',
				'reliably_heic',
				array($this, 'render_settings_page')
			);
		}

		public function render_settings_page() {
			if (!current_user_can('manage_options')) {
				return;
			}

			?>
			<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			hello =)
			<?php
		}


	}

}