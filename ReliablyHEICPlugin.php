<?php

if(!class_exists('ReliablyHEICPlugin')) {

	class ReliablyHEICPlugin {
		public function setup() {
			$index_path = dirname(plugin_basename(__FILE__)) . '/index.php';
			add_filter('plugin_action_links_' . $index_path, array($this, 'add_plugin_settings_link' ));
			
			add_filter('upload_mimes', array($this, 'add_heic_upload_mime_type'));
			
			// We shouldn't need this, but apparently we do, because the outcome of the
			// add_filter underneath is invalidated when some other function undoes
			// our work later.
			// So keeping both for good measure!
			remove_filter('plupload_default_settings', 'wp_show_heic_upload_error');
			add_filter('plupload_default_settings', array($this, 'allow_heic_upload'), 10);
			
			add_filter('wp_handle_upload_prefilter', array($this, 'handle_heic_upload'));

			$this->settings_page_name = 'reliably-heic-page';
			$this->setting_experimental_id = 'reliably-heic-experimental-front-end';
			$this->setting_experimental_description = 'Enable front-end HEIC to JPEG image conversion';

			add_action('admin_menu', array($this, 'setup_admin_menu'));
			
			// Only load the JS scripts if the experimental setting is ON
			if(get_option($this->setting_experimental_id)) {
				add_action('admin_enqueue_scripts', array($this, 'add_js_to_media_new'));
			}
		}

		/**
		 * This is to add the 'Settings' link in the Plugins list (wp-admin/plugins.php)
		 */
		public function add_plugin_settings_link($actions) {
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

		/** 
		 * Arguably disables the 'This image cannot be displayed in a web browser. For best results, convert it to JPEG before uploading.' message
		 * ... arguably. (See the double combo of add_filter/remove_filter in the plugin initialisation method).
		 */
		public function allow_heic_upload($settings) {
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
			
			$input_path = $file['tmp_name'];
			$output_path = $input_path . '.jpg';
			
			try {
				$this->save_image_for_browser($input_path, $output_path);
				
				// Swaaap!
				rename($output_path, $input_path);

				// Make the upload make sense
				$info = pathinfo($file['name']);
				$filename = $info['filename'];
				
				// Fallback if wEirD thIngS hApPeneD
				if(strlen($filename) == 0) {
					$filename = basename($input_path);
				}
				$file['name'] = $filename . '.jpg';
				$file['size'] = wp_filesize($input_path);
				$file['type'] = 'image/jpeg';

				return $file;

			} catch(Exception | ImagickException $e) {
				error_log('save for browser');
				error_log($e->getMessage());
			}
			
			
			return $file;
		}


		public function setup_admin_menu() {
			add_submenu_page(
				'options-general.php',
				'Reliably HEIC', // Page title
				'Reliably HEIC', // Menu title
				'manage_options',
				'reliably_heic',
				array($this, 'render_settings_page')
			);

			$this->setup_settings();
		}

		public function setup_settings() {

			$s = 'reliably-heic';
			$section_id = $s . '-id';

			add_settings_section(
				$section_id,
				'Configuration',
				function() { },
				$this->settings_page_name
			);

			
			register_setting(
				$this->settings_page_name,
				$this->setting_experimental_id, 
				array(
					'type'              => 'boolean',
					'description'       => $this->setting_experimental_description,
					'sanitize_callback' => 'rest_sanitize_boolean',
					'show_in_rest'      => true
				)
			);

			add_option($this->setting_experimental_id, false);

			add_settings_field(
				$this->setting_experimental_id,
				$this->setting_experimental_description,
				array($this, 'enable_setting_callback'),
				$this->settings_page_name,
				$section_id,
				array(
					'option_name' => $this->setting_experimental_id
				)
			);

			
		}

		function enable_setting_callback() {
			$id = $this->setting_experimental_id;

			$value = get_option($id);

			printf(
				'<input type="checkbox" id="%s" name="%s" %s/> <label for="%s">%s</label><p class="description">%s</p>',
				$id,
				$id,
				checked($value, '1', false),
				$id,
				'When ticked, intercepts HEIC file uploads from the <a href="media-new.php">Media/Add new</a> section, and tries to convert them to JPG in the browser.',
				'This is highly experimental, but if it works then you do not depend on your hosting company to update or install a recent version of ImageMagick.'
			);
		}

		/**
		 * Note:
		 *	The interceptable uploader seems to only be set up in media-new.php ('Add new').
		 *	So even if you CAN upload files with drag and drop in upload.php (the page which the top 'Media' links to),
		 *	I have not figured out how to intercept that uploader yet, thus HEIC conversion client side won't happen there.
		 *	Go to 'Add new' to get it working.
		 */
		public function add_js_to_media_new($hook) {
			
			if(!in_array($hook, ['media-new.php' /*, 'upload.php'*/])) { // Maybe something for the future
				return;
			}
			
			wp_enqueue_script('reliablyheic1', plugin_dir_url(__FILE__) . 'js/libs/libheif.js');
			wp_enqueue_script('reliablyheic2', plugin_dir_url(__FILE__) . 'js/HEIF2JPG.js');
			wp_enqueue_script('reliablyheic3', plugin_dir_url(__FILE__) . 'js/intercept_uploads.js');
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
			<?php
				// This feels like I'm doing something wrong, but I can't figure out
				// how to do it "the right WP way" (maybe there is no such thing).
				if(isset($_POST['submit'])) {
					$value = isset($_POST[$this->setting_experimental_id]);
					update_option($this->setting_experimental_id, $value);
				}
			?>
			
			<form method="post" action="options-general.php?page=reliably_heic" novalidate="novalidate">
			<?php
				do_settings_sections($this->settings_page_name);
				submit_button();
			?>
			</form>

			<h2>Troubleshooting</h2>
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

			$res_format = $im->setImageFormat('jpg');
			error_log('set format to jpg: ' . $res_format);
			
			$ok = $im->writeImage($output_path);
			error_log('and save image to ' . $output_path . ' = '. $ok);
			
		}

	}

}