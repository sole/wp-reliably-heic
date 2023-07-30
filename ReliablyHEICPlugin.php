<?php

if(!class_exists('ReliablyHEICPlugin')) {

	class ReliablyHEICPlugin {
		public function setup() {
			$index_path = dirname(plugin_basename(__FILE__)) . '/index.php';
			add_filter('plugin_action_links_' . $index_path, array($this, 'add_plugin_settings_link' ));
			
			add_filter('upload_mimes', array($this, 'add_heic_upload_mime_type'));
			// TODO: this isn't doing what it should, look in here for more info or tips... https://developer.wordpress.org/reference/functions/media_upload_form/
			add_filter('plupload_default_settings', array($this, 'allow_heic_upload'), 1);
			add_filter('wp_handle_upload_prefilter', array($this, 'handle_heic_upload'));
			
			add_action('admin_menu', array($this, 'setup_admin_menu'));
			
		}

		public function add_plugin_settings_link($actions) {
			// This is to add the 'Settings' link in the Plugins list
			$link = array('<a href="' . admin_url( 'options-general.php?page=reliably_heic' ) . '">' . __( 'Settings', 'reliably_heic' ) . '</a>');
			return array_merge($actions, $link);
		}

		public function add_heic_upload_mime_type($mime_types) {
			if(!isset($mime_types['heic'])) {
				$heic_type = array('heic' => 'image/heic');
				return array_merge($mime_types, $heic_type);
			}
			return $mime_types;
		}

		public function allow_heic_upload($settings) {
			// Arguably disables the 'This image cannot be displayed in a web browser. For best results, convert it to JPEG before uploading.' message
			error_log('does this run');
			error_log(print_r($settings, 1));
			$settings['heic_upload_error'] = false;
			return $settings;
		}

		public function handle_heic_upload($file) {
			$not_an_heic_image = false;
			$file_type = $file['type'];
			if($file_type != 'image/heic') {
				$not_an_heic_image = true;
			}

			if(!$this->can_work() || $not_an_heic_image) {
				return $file;
			}
			
			error_log('original file data '. print_r($file, true));
			$input_path = $file['tmp_name'];
			$output_path = $input_path . '.jpg';
			error_log('input_path '. $input_path);
			error_log('output_path '. $output_path);

			try {
				// TODO is there a way to carry over the EXIF metadata (exif) to the copy?
				$t0 = time();
				$this->save_image_for_browser($input_path, $output_path);
				$time_diff = time() - $t0;
				error_log('took ' . $time_diff);
				error_log('filesize of output image '. wp_filesize($output_path));
				// TODO resizing if configured etc
				// Not sure if there's a better way? if I set tmp_name on the file, will the server delete temp uploads?
				rename($output_path, $input_path);

				// Make the upload make sense
				$file['name'] = basename($file['name']) . '.jpg';
				$file['size'] = wp_filesize($input_path);
				$file['type'] = 'image/jpeg';

				return $file;

			} catch(Exception $e) {
				error_log('and this???' . $e->getMessage());
			} catch(ImagickException $e) {
				error_log('save for browser');
				error_log($e->getMessage());
			}
			
			
			return $file;
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
			<?php // echo print_r(wp_plupload_default_settings(), 1); ?>
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
				),
				array(
					'desc' => 'ImageMagick extension can decode HEIC',
					'func' => 'validate_imagemagick_decode_heic'
				),
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

		protected function can_work() {
			$checks = $this->run_through_system_requirements();
			return $this->are_system_requirements_satisfied($checks);
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

		protected function validate_imagemagick_decode_heic() {
			$result = array();
			if(!class_exists('Imagick')) {
				$result['error'] = 'Irrelevant error if you already got an ImageMagick error';
			} else {
				$im = new Imagick();
				$v = Imagick::getVersion();
				$test_image = 'test.heic';
				$test_image_path = dirname(__FILE__) . '/' . $test_image;
				error_log('test image path ' . $test_image_path);
				try {
					$im->readImage($test_image_path);
				} catch(ImagickException $ie) {
					$result['error'] = 'ImageMagick is present (version <tt>'. $v['versionString'] .'</tt>) but HEIC images cannot be opened: <tt>' . $ie->getMessage() . '</tt>';
				}
			}
			return $result;
		}

		protected function save_image_for_browser($input_path, $output_path) {
			$im = new Imagick();
			if(!$im->readImage($input_path)) {
				throw new Exception('The image at ' . $input_path . ' could not be read with ImageMagick');
			}

			$geom = $im->getImageGeometry();
			error_log('geometry ' . print_r($geom, true));

			
			$res_format = $im->setImageFormat('jpg');
			error_log('set format to jpg: ' . $res_format);
			
			$ok = $im->writeImage($output_path);
			error_log('and save image to ' . $output_path . ' = '. $ok);
			
		}

	}

}