<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Copyright 2011-2012 Spadefoot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This class generates a Code 39 barcode.
 *
 * @package Barcode
 * @category Creator
 * @version 2012-08-27
 */
abstract class Base_Code39Barcode extends Kohana_Object implements Barcode_Interface {

	/**
	 * This variable stores the file's URI.
	 *
	 * @access protected
	 * @var array
	 */
	protected $file = NULL;

	/**
	 * This variable stores the data that will be used to generate
	 * the barcode.
	 *
	 * @access protected
	 * @var string
	 */
	protected $data = NULL;

	/**
	 * Initializes this barcode creator.
	 *
	 * @access public
	 * @param $data string                      the data string to be encoded
	 */
	public function __construct($data) {
		if (!is_string($data) || !preg_match('/^[- *$%.\/+a-z0-9]+$/i', $data)) {
			throw new Kohana_InvalidArgument_Exception('Message: Unable to encode :data for barcode. Reason: Invalid data string passed.', array('data' => $data));
		}
		$this->data = strtoupper($data);
	}

	/**
	 * This function controls which properties are accessible.
	 *
	 * @access public
	 * @param string $key                       the name of the property
	 * @return mixed                            the value of the property
	 */
	public function __get($key) {
		switch ($key) {
			case 'file':
				$file = (is_null($this->file)) ? '/barcode/code39/' . urlencode($this->data) : $this->file;
				return $file;
			default:
				return NULL;
		}
	}

	/**
	 * This function sends back the bar code image.
	 *
	 * @access public
	 * @param $file_name                        the file name
	 */
	public function output($file_name = NULL) {
		// Generates the barcode image
		$data = '*' . $this->data . '*';
		$length = strlen($data);
		$width = $length * 16;
		$height = 33;
		$padding = array(0, 0, 18, 0); // Follows CSS padding: top right bottom left
		$image = imagecreate(($padding[1] + $padding[3]) + $width, ($padding[0] + $padding[2]) + $height);
		$black = imagecolorallocate($image, 0, 0, 0);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefilledrectangle($image, 0, 0, ($padding[1] + $padding[3]) + $width, ($padding[0] + $padding[2]) + $height, $white);
		$x1 = 0;
		for ($i = 0; $i < $length; $i++) {
			$code = self::$values[$data[$i]];
			for ($j = 0; $j < 9; $j++) {
				switch ($code[$j]) { // symbol
					case 'B': // wide
						$x2 = $x1 + 2;
						$color = $black;
					break;
					case 'b': // narrow
						$x2 = $x1 + 0;
						$color = $black;
					break;
					case 'W': // wide
						$x2 = $x1 + 2;
						$color = $white;
					break;
					case 'w': // narrow
						$x2 = $x1 + 0;
						$color = $white;
					break;
				}
				imagefilledrectangle($image, $padding[3] + $x1, $padding[0], $padding[3] + $x2, $padding[0] + $height, $color);
				$x1 = $x2 + 1;
			}
			$x1 += 1;
		}

		// Adds the human readable label
		$offset = array(16, 1); // x, y
		$adjustment = 5;
		$font = 5;
		$length = strlen($this->data);
		for ($x = 1; $x <= $length; $x++) {
			imagestring($image, $font, $padding[3] + ($x * $offset[0]) + $adjustment, $padding[0] + $height + $offset[1], $data[$x], $black);
		}

		// Outputs the header and content
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		header('Content-Type: image/png');
		if (is_string($file_name)) {
			header("Content-Disposition: attachment; filename=\"{$file_name}\"");
		}
		imagepng($image);
		imagedestroy($image);
		exit();
	}

	/**
	 * This function renders the HTML image tag for displaying the bar code.
	 *
	 * @access public
	 * @param array $attributes                 any additional attributes to be added
	 *                                          to the HTML image tag
	 * @return string                           the HTML image tag
	 */
	public function render($attributes = array()) {
		$file = (is_null($this->file)) ? '/barcode/code39/' . urlencode($this->data) : $this->file;
		$properties = '';
		if (is_array($attributes)) {
			foreach ($attributes as $key => $val) {
				$properties .= "{$key}=\"{$val}\" ";
			}
		}
		$html = "<img src=\"{$file}\" {$properties}/>";
		return $html;
	}

	/**
	 * This function saves the image of the QR code to disk.
	 *
	 * @access public
	 * @param string $file                      the URI for where the image will be stored
	 */
	public function save($file) {
		$data = urlencode($this->data);
		file_put_contents($file, file_get_contents("/barcode/code39/{$data}"));
		$this->file = $file;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * This function acts as a lookup table for matching Code 39 patterns.
	 *
	 * @access protected
	 * @static
	 * @return array                            the lookup table
	 */
	protected static $patterns = array(
		'bwbWBwBwb' => '0',
		'BwbWbwbwB' => '1',
		'bwBWbwbwB' => '2',
		'BwBWbwbwb' => '3',
		'bwbWBwbwB' => '4',
		'BwbWBwbwb' => '5',
		'bwBWBwbwb' => '6',
		'bwbWbwBwB' => '7',
		'BwbWbwBwb' => '8',
		'bwBWbwBwb' => '9',
		'BwbwbWbwB' => 'A',
		'bwBwbWbwB' => 'B',
		'BwBwbWbwb' => 'C',
		'bwbwBWbwB' => 'D',
		'BwbwBWbwb' => 'E',
		'bwBwBWbwb' => 'F',
		'bwbwbWBwB' => 'G',
		'BwbwbWBwb' => 'H',
		'bwBwbWBwb' => 'I',
		'bwbwBWBwb' => 'J',
		'BwbwbwbWB' => 'K',
		'bwBwbwbWB' => 'L',
		'BwBwbwbWb' => 'M',
		'bwbwBwbWB' => 'N',
		'BwbwBwbWb' => 'O',
		'bwBwBwbWb' => 'P',
		'bwbwbwBWB' => 'Q',
		'BwbwbwBWb' => 'R',
		'bwBwbwBWb' => 'S',
		'bwbwBwBWb' => 'T',
		'BWbwbwbwB' => 'U',
		'bWBwbwbwB' => 'V',
		'BWBwbwbwb' => 'W',
		'bWbwBwbwB' => 'X',
		'BWbwBwbwb' => 'Y',
		'bWBwBwbwb' => 'Z',
		'bWbwbwBwB' => '-',
		'BWbwbwBwb' => '.',
		'bWBwbwBwb' => ' ',
		'bWbWbWbwb' => '$',
		'bWbWbwbWb' => '/',
		'bWbwbWbWb' => '+',
		'bwbWbWbWb' => '%',
		'bWbwBwBwb' => '*',
	);

	/**
	 * This function acts as a lookup table for matching Code 39 code sets.
	 *
	 * @access protected
	 * @static
	 * @return array                            the lookup table
	 */
	protected static $values = array(
		'0' => 'bwbWBwBwb',
		'1' => 'BwbWbwbwB',
		'2' => 'bwBWbwbwB',
		'3' => 'BwBWbwbwb',
		'4' => 'bwbWBwbwB',
		'5' => 'BwbWBwbwb',
		'6' => 'bwBWBwbwb',
		'7' => 'bwbWbwBwB',
		'8' => 'BwbWbwBwb',
		'9' => 'bwBWbwBwb',
		'A' => 'BwbwbWbwB',
		'B' => 'bwBwbWbwB',
		'C' => 'BwBwbWbwb',
		'D' => 'bwbwBWbwB',
		'E' => 'BwbwBWbwb',
		'F' => 'bwBwBWbwb',
		'G' => 'bwbwbWBwB',
		'H' => 'BwbwbWBwb',
		'I' => 'bwBwbWBwb',
		'J' => 'bwbwBWBwb',
		'K' => 'BwbwbwbWB',
		'L' => 'bwBwbwbWB',
		'M' => 'BwBwbwbWb',
		'N' => 'bwbwBwbWB',
		'O' => 'BwbwBwbWb',
		'P' => 'bwBwBwbWb',
		'Q' => 'bwbwbwBWB',
		'R' => 'BwbwbwBWb',
		'S' => 'bwBwbwBWb',
		'T' => 'bwbwBwBWb',
		'U' => 'BWbwbwbwB',
		'V' => 'bWBwbwbwB',
		'W' => 'BWBwbwbwb',
		'X' => 'bWbwBwbwB',
		'Y' => 'BWbwBwbwb',
		'Z' => 'bWBwBwbwb',
		'-' => 'bWbwbwBwB',
		'.' => 'BWbwbwBwb',
		' ' => 'bWBwbwBwb',
		'$' => 'bWbWbWbwb',
		'/' => 'bWbWbwbWb',
		'+' => 'bWbwbWbWb',
		'%' => 'bwbWbWbWb',
		'*' => 'bWbwBwBwb',
	);

	/**
	 * This function computes the checksum for the specified data string.
	 *
	 * @access public
	 * @static
	 * @param $data string                      the data string to be evaluated
	 * @return mixed                            the checksum for the specified data string
	 * @see                                     http://en.wikipedia.org/wiki/Code_39
	 */
	public static function checksum($data) {
		$checksum = 0;
		$length = strlen($data);
		$charset = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-. $/+%';
		for ($i = 0; $i < $length; $i++) {
			$checksum += strpos($charset, $data[$i]);
		}
		return substr($charset, ($checksum % 43), 1);
	}

	/**
	 * This function will read an image with a Code 39 Barcode and will decode it.
	 *
	 * @access public
	 * @static
	 * @param string $file                      the image's URI
	 * @return string                           the decode value
	 */
	public static function decode($file) {
		$image = NULL;
		if (Text::has_suffix($file, '.png')) {
			$image = imagecreatefrompng($file);
		}
		else if (Text::has_suffix($file, '.jpg')) {
			$image = imagecreatefromjpeg($file);
		}
		else {
			throw new Kohana_InvalidArgument_Exception('Message: Unrecognized file format. Reason: File name must have either a png or jpg extension.', array('file' => $file));
		}

		$width = imagesx($image);
		$height = imagesy($image);

		$y = floor($height / 2); // finds middle

		$pixels = array();

		for ($x = 0; $x < $width; $x++) {
			$rgb = imagecolorat($image, $x, $y);
			$r = ($rgb >> 16) & 0xFF;
			$g = ($rgb >> 8) & 0xFF;
			$b = $rgb & 0xFF;
			$pixels[$x] = ((($r + $g + $b) / 3) < 128.0) ? 1 : 0;
		}

		$code = array();
		$bw = array();

		$i = 0;

		$code[0] = 1;
		$bw[0] = 'b';

		for ($x = 1; $x < $width; $x++) {
			if ($pixels[$x] == $pixels[$x - 1]) {
				$code[$i]++;
			}
			else {
				$code[++$i] = 1;
				$bw[$i] = ($pixels[$x] == 1) ? 'b' : 'w';
			}
		}

		$max = 0;

		for ($x = 1; $x < (count($code) - 1); $x++) {
			if ($code[$x] > $max) {
				$max = $code[$x];
			}
		}

		$code_string = '';

		for ($x = 1; $x < (count($code) - 1); $x++) {
			$code_string .= ($code[$x] > ($max / 2) * 1.5) ? strtoupper($bw[$x]) : $bw[$x];
		}

		// parse code string
		$msg = '';

		for ($x = 0; $x < strlen($code_string); $x += 10) {
			$msg .= self::$patterns[substr($code_string, $x, 9)];
		}

		return $msg;
	}

	/**
	 * This function will encode a data string.
	 *
	 * @access public
	 * @static
	 * @param $data string                      the data string to be encoded
	 * @param array $attributes                 any additional attributes to be added
	 *                                          to the HTML image tag
	 * @return string                           the HTML image tag
	 */
	public static function encode($data, $attributes = array()) {
		$barcode = new Code39Barcode($data);
		return $barcode->render($attributes);
	}

	/**
	 * This function returns an instance of this class.
	 *
	 * @access public
	 * @static
	 * @param $data string                      the data string to be encoded
	 * @return Code39Barcode			        an instance of this class
	 */
	public static function factory($data) {
		return new Code39Barcode($data);
	}

}
?>