<?php

if(!class_exists('ReliablyHEICPlugin')) {

	class ReliablyHEICPlugin {
		public function setup() {
			// This is to add the 'Settings' link in the Plugins list
			$index_path = dirname(plugin_basename(__FILE__)) . '/index.php';
			add_filter('plugin_action_links_' . $index_path, array($this, 'add_plugin_settings_link' ));
			
			add_action('admin_menu', array($this, 'setup_admin_menu'));
		}

		public function add_admin_settings() {

		}

		public function add_plugin_settings_link($actions) {
			$link = array('<a href="' . admin_url( 'options-general.php?page=reliably_heic' ) . '">' . __( 'Settings', 'reliably_heic' ) . '</a>');
			return array_merge($actions, $link);
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

			$checks = $this->run_through_system_requirements();
			$satisfied = $this->are_system_requirements_satisfied($checks);

			?>
			<div class="wrap">
			<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
			<h2>Requirements check</h2>
			<?php if($satisfied) { ?>
				All in place: the plugin can work correctly!
			<?php } else {
				?>
				<p>Requirements missing:</p>
				<ul>
				<?php
				foreach($checks as $check) {
					if(isset($check['error'])) {
						echo '<li><strong>' . $check['desc'] .':</strong> ' . $check['error'] . '</li>';
					}
				}
				?>
				</ul>
				<?php
			}
			?>
			
			<?php
		}

		protected function run_through_system_requirements() {
			$checks = array(
				array(
					'desc' => 'ImageMagick extension present',
					'func' => 'validate_imagemagick'
				)
			);

			foreach($checks as &$check) {
				$callable = array($this, $check['func']);
				$result = call_user_func($callable);
				if(isset($result['error'])) {
					$check['error'] = $result['error'];
				} else {
					$check['ok'] = true;
				}
			}

			return $checks;
		}

		protected function are_system_requirements_satisfied($checks) {
			foreach($checks as $check) {
				if(isset($check['error'])) {
					return false;
				}
			}
			return true;
		}

		protected function validate_imagemagick() {
			$result = array();
			if(class_exists('Imagick')) {
				$result['ok'] = true;
			} else {
				$result['error'] = 'Imagick class does not exist. Did you activate the ImageMagick PHP module in your server? (<tt>extension=imagick.so</tt> in <tt>php.ini</tt>)';
			}
			return $result;
		}

	}

}