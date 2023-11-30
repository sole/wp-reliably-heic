<?php
class RHEIC_HEIC2JPG {

	public static function save_image_for_browser($input_path, $output_path) {
		$im = new Imagick();
		if(!$im->readImage($input_path)) {
			throw new Exception('The image at ' . $input_path . ' could not be read with ImageMagick');
		}

		$res_format = $im->setImageFormat('jpg');
		error_log('set format to jpg: ' . $res_format);
		
		$ok = $im->writeImage($output_path);
		error_log('and save image to ' . $output_path . ' = '. $ok);
		
	}

	public static function run_through_system_requirements() {
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
			$callable = array('RHEIC_HEIC2JPG', $check['func']);
			$result = call_user_func($callable);
			if(isset($result['error'])) {
				$check['error'] = $result['error'];
			} else {
				$check['ok'] = true;
			}
		}

		return $checks;
	}

	protected static function validate_imagemagick() {
		$result = array();
		if(class_exists('Imagick')) {
			$result['ok'] = true;
		} else {
			$result['error'] = 'Imagick class does not exist. Did you activate the ImageMagick PHP module in your server? (<tt>extension=imagick.so</tt> in <tt>php.ini</tt>)';
		}
		return $result;
	}

	protected static function validate_imagemagick_decode_heic() {
		$result = array();
		if(!class_exists('Imagick')) {
			$result['error'] = 'Irrelevant error if you already got an ImageMagick error';
		} else {
			$im = new Imagick();
			$v = Imagick::getVersion();
			$test_image = 'test.heic';
			$test_image_path = dirname(__FILE__) . '/assets/internal/' . $test_image;
	
			try {
				$im->readImage($test_image_path);
			} catch(ImagickException $ie) {
				$result['error'] = 'ImageMagick is present (version <tt>'. $v['versionString'] .'</tt>) but HEIC images cannot be opened: <tt>' . $ie->getMessage() . '</tt>';
			}
		}
		return $result;
	}
}
