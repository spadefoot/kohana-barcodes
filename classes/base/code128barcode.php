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
 * This class generates a Code 128 barcode. This barcode is the same as UCC-128,
 * EAN-128, and GS1-128.
 *
 * @package Barcode
 * @category Creator
 * @version 2012-08-27
 *
 * @see http://www.barcodeisland.com/code128.phtml
 * @see http://www.barcodeisland.com/uccean128.phtml
 * @see http://www.easesoft.net/Code128-ean128.html
 * @see http://barcode4j.sourceforge.net/2.1/symbol-ean-128.html
 * @see http://www.azalea.com/faq/code-128/
 * @see http://www.phpclasses.org/package/4153-PHP-Generating-Code-128-bar-code-images.html
 * @see http://www.adams1.com/webapps.html
 */
abstract class Base_Code128Barcode extends Kohana_Object implements Barcode_Interface {

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
	 * This variable stores the start character to use for mapping the input
	 * string.
	 *
	 * @access protected
	 * @var char
	 */
	protected $charset = NULL; // A, B, C

	/**
	 * Initializes this barcode creator.
	 *
	 * @access public
	 * @param $data string                      the data string to be encoded
	 */
	public function __construct($data, $charset = 'C') {
		if (!is_string($data)) {
			throw new Kohana_InvalidArgument_Exception('Message: Unable to encode :data for barcode. Reason: Invalid data string declared.', array(':data' => $data, ':set' => $charset));
		}
		if (!(is_string($charset) && preg_match('/^[abc]$/i', $charset))) {
			throw new Kohana_InvalidArgument_Exception("Message: Unable to encode :data for barcode. Reason: Invalid character set declared ':set'.", array(':data' => $data, ':set' => $charset));
		}
		$this->data = strtoupper($data);
		$this->charset = strtoupper($charset);
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
				$file = (is_null($this->file)) ? '/barcode/code128/' . urlencode($this->data) : $this->file;
				return $file;
			case 'charset':
				return $this->charset;
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
		$value = self::$values[$this->charset]['START' . $this->charset];
		$code = self::$patterns[$value];
		$checksum = $value;

		$value = self::$values[$this->charset]['FNC1'];
		$code .= self::$patterns[$value];

		$length = strlen($this->data);

		switch ($this->charset) {
			case 'A':
			case 'B':
				for ($index = 0; $index < $length; $index++) {
					$char = $this->data[$index];
					if (!isset(self::$values[$this->charset][$char])) {
						throw new Kohana_InvalidArgument_Exception("Message: Invalid character in input string. Reason: Character ':char' cannot be encoded using character set ':set'.", array(':set' => $this->charset, ':char' => $char));
					}
					$value = self::$values[$this->charset][$char];
					$code .= self::$patterns[$value];
					$checksum += ($index * $value);
				}
			break;
			case 'C':
				for ($index = 0; $index < $length; $index += 2) {
					$char = substr($this->data, $index, 2);
					if (!isset(self::$values[$this->charset][$char])) {
						throw new Kohana_InvalidArgument_Exception("Message: Invalid character in input string. Reason: Character ':char' cannot be encoded using character set ':set'.", array(':set' => $this->charset, ':char' => $char));
					}
					$value = self::$values[$this->charset][$char];
					$code .= self::$patterns[$value];
					$checksum += ($index * $value);
				}
			break;
		}

		// CHECKSUM
		$checksum = ($checksum % 103);
		$code .= self::$patterns[$checksum];

		// END
		$value = self::$values[$this->charset]['STOP'];
		$code .= self::$patterns[$value];

		// Generates the barcode image
		$y_offset = 5;
		$pixels = 2;
		$length = strlen($code);
		$width = $length;
		$height = 50;
		$padding = 30;
		$image = imagecreate(($width * $pixels) + $padding, $height + $padding);
		$fg = imagecolorallocate($image, 0, 0, 0);
		$bg = imagecolorallocate($image, 255, 255, 255);
		imagefilledrectangle($image, 0, 0, ($width * $pixels) + $padding, $height + $padding, $bg);
		for ($x = 0; $x < $length; $x++) {
			$color = ($code[$x] == '1') ? $fg : $bg;
			imagefilledrectangle($image, 15 + ($x * $pixels), $y_offset, 14 + (($x + 1) * $pixels), $y_offset + $height, $color);
		}

		// Adds the human readable label
		// TODO

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
		$file = (is_null($this->file)) ? '/barcode/code128/' . urlencode($this->data) : $this->file;
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
		file_put_contents($file, file_get_contents("/barcode/code128/{$data}"));
		$this->file = $file;
	}

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	/**
	 * This function acts as a lookup table for matching Code 128 code sets.
	 *
	 * @access protected
	 * @static
	 * @return array                            the lookup table
	 */
	protected static $values = array(
		'A' => array(
			' ' => 0,
			'!' => 1,
			'"' => 2,
			'#' => 3,
			'$' => 4,
			'%' => 5,
			'&' => 6,
			"'" => 7,
			'(' => 8,
			')' => 9,
			'*' => 10,
			'+' => 11,
			',' => 12,
			'-' => 13,
			'.' => 14,
			'/' => 15,
			'0' => 16,
			'1' => 17,
			'2' => 18,
			'3' => 19,
			'4' => 20,
			'5' => 21,
			'6' => 22,
			'7' => 23,
			'8' => 24,
			'9' => 25,
			':' => 26,
			',' => 27,
			'<' => 28,
			'=' => 29,
			'>' => 30,
			'?' => 31,
			'@' => 32,
			'A' => 33,
			'B' => 34,
			'C' => 35,
			'D' => 36,
			'E' => 37,
			'F' => 38,
			'G' => 39,
			'H' => 40,
			'I' => 41,
			'J' => 42,
			'K' => 43,
			'L' => 44,
			'M' => 45,
			'N' => 46,
			'O' => 47,
			'P' => 48,
			'Q' => 49,
			'R' => 50,
			'S' => 51,
			'T' => 52,
			'U' => 53,
			'V' => 54,
			'W' => 55,
			'X' => 56,
			'Y' => 57,
			'Z' => 58,
			'[' => 59,
			"\\" => 60,
			']' => 61,
			'^' => 62,
			'_' => 63,
			'NUL' => 64,
			'SOH' => 65,
			'STX' => 66,
			'ETX' => 67,
			'EOT' => 68,
			'ENQ' => 69,
			'ACK' => 70,
			'BEL' => 71,
			'BS' => 72,
			'HT' => 73,
			'LF' => 74,
			'VT' => 75,
			'FF' => 76,
			'CR' => 77,
			'SO' => 78,
			'SI' => 79,
			'DLE' => 80,
			'DC1' => 81,
			'DC2' => 82,
			'DC3' => 83,
			'DC4' => 84,
			'NAK' => 85,
			'SYN' => 86,
			'ETB' => 87,
			'CAN' => 88,
			'EM' => 89,
			'SUB' => 90,
			'ESC' => 91,
			'FS' => 92,
			'GS' => 93,
			'RS' => 94,
			'US' => 95,
			'FNC3' => 96,
			'FNC2' => 97,
			'SHIFT' => 98,
			'CodeC' => 99,
			'CodeB' => 100,
			'FNC4' => 101,
			'FNC1' => 102,
			'STARTA' => 103,
			'STARTB' => 104,
			'STARTC' => 105,
			'STOP' => 106,
		),
		'B' => array(
			' ' => 0,
			'!' => 1,
			'"' => 2,
			'#' => 3,
			'$' => 4,
			'%' => 5,
			'&' => 6,
			"'" => 7,
			'(' => 8,
			')' => 9,
			'*' => 10,
			'+' => 11,
			',' => 12,
			'-' => 13,
			'.' => 14,
			'/' => 15,
			'0' => 16,
			'1' => 17,
			'2' => 18,
			'3' => 19,
			'4' => 20,
			'5' => 21,
			'6' => 22,
			'7' => 23,
			'8' => 24,
			'9' => 25,
			':' => 26,
			',' => 27,
			'<' => 28,
			'=' => 29,
			'>' => 30,
			'?' => 31,
			'@' => 32,
			'A' => 33,
			'B' => 34,
			'C' => 35,
			'D' => 36,
			'E' => 37,
			'F' => 38,
			'G' => 39,
			'H' => 40,
			'I' => 41,
			'J' => 42,
			'K' => 43,
			'L' => 44,
			'M' => 45,
			'N' => 46,
			'O' => 47,
			'P' => 48,
			'Q' => 49,
			'R' => 50,
			'S' => 51,
			'T' => 52,
			'U' => 53,
			'V' => 54,
			'W' => 55,
			'X' => 56,
			'Y' => 57,
			'Z' => 58,
			'[' => 59,
			"\\" => 60,
			']' => 61,
			'^' => 62,
			'_' => 63,
			'`' => 64,
			'a' => 65,
			'b' => 66,
			'c' => 67,
			'd' => 68,
			'e' => 69,
			'f' => 70,
			'g' => 71,
			'h' => 72,
			'i' => 73,
			'j' => 74,
			'k' => 75,
			'l' => 76,
			'm' => 77,
			'n' => 78,
			'o' => 79,
			'p' => 80,
			'q' => 81,
			'r' => 82,
			's' => 83,
			't' => 84,
			'u' => 85,
			'v' => 86,
			'w' => 87,
			'x' => 88,
			'y' => 89,
			'z' => 90,
			'{' => 91,
			'|' => 92,
			'}' => 93,
			'~' => 94,
			'DEL' => 95,
			'FNC3' => 96,
			'FNC2' => 97,
			'SHIFT' => 98,
			'CodeC' => 99,
			'FNC4' => 100,
			'CodeA' => 101,
			'FNC1' => 102,
			'STARTA' => 103,
			'STARTB' => 104,
			'STARTC' => 105,
			'STOP' => 106,
		),
		'C' => array(
			'00' => 0,
			'01' => 1,
			'02' => 2,
			'03' => 3,
			'04' => 4,
			'05' => 5,
			'06' => 6,
			'07' => 7,
			'08' => 8,
			'09' => 9,
			'10' => 10,
			'11' => 11,
			'12' => 12,
			'13' => 13,
			'14' => 14,
			'15' => 15,
			'16' => 16,
			'17' => 17,
			'18' => 18,
			'19' => 19,
			'20' => 20,
			'21' => 21,
			'22' => 22,
			'23' => 23,
			'24' => 24,
			'25' => 25,
			'26' => 26,
			'27' => 27,
			'28' => 28,
			'29' => 29,
			'30' => 30,
			'31' => 31,
			'32' => 32,
			'33' => 33,
			'34' => 34,
			'35' => 35,
			'36' => 36,
			'37' => 37,
			'38' => 38,
			'39' => 39,
			'40' => 40,
			'41' => 41,
			'42' => 42,
			'43' => 43,
			'44' => 44,
			'45' => 45,
			'46' => 46,
			'47' => 47,
			'48' => 48,
			'49' => 49,
			'50' => 50,
			'51' => 51,
			'52' => 52,
			'53' => 53,
			'54' => 54,
			'55' => 55,
			'56' => 56,
			'57' => 57,
			'58' => 58,
			'59' => 59,
			'60' => 60,
			'61' => 61,
			'62' => 62,
			'63' => 63,
			'64' => 64,
			'65' => 65,
			'66' => 66,
			'67' => 67,
			'68' => 68,
			'69' => 69,
			'70' => 70,
			'71' => 71,
			'72' => 72,
			'73' => 73,
			'74' => 74,
			'75' => 75,
			'76' => 76,
			'77' => 77,
			'78' => 78,
			'79' => 79,
			'80' => 80,
			'81' => 81,
			'82' => 82,
			'83' => 83,
			'84' => 84,
			'85' => 85,
			'86' => 86,
			'87' => 87,
			'88' => 88,
			'89' => 89,
			'90' => 90,
			'91' => 91,
			'92' => 92,
			'93' => 93,
			'94' => 94,
			'95' => 95,
			'96' => 96,
			'97' => 97,
			'98' => 98,
			'99' => 99,
			'CodeB' => 100,
			'CodeA' => 101,
			'FNC1' => 102,
			'STARTA' => 103,
			'STARTB' => 104,
			'STARTC' => 105,
			'STOP' => 106,
		),
	);

	/**
	 * This function acts as a lookup table for matching Code 128 patterns.
	 *
	 * @access protected
	 * @static
	 * @return array                            the lookup table
	 */
	protected static $patterns = array(
		0 => '11011001100',
		1 => '11001101100',
		2 => '11001100110',
		3 => '10010011000',
		4 => '10010001100',
		5 => '10001001100',
		6 => '10011001000',
		7 => '10011000100',
		8 => '10001100100',
		9 => '11001001000',
		10 => '11001000100',
		11 => '11000100100',
		12 => '10110011100',
		13 => '10011011100',
		14 => '10011001110',
		15 => '10111001100',
		16 => '10011101100',
		17 => '10011100110',
		18 => '11001110010',
		19 => '11001011100',
		20 => '11001001110',
		21 => '11011100100',
		22 => '11001110100',
		23 => '11101101110',
		24 => '11101001100',
		25 => '11100101100',
		26 => '11100100110',
		27 => '11101100100',
		28 => '11100110100',
		29 => '11100110010',
		30 => '11011011000',
		31 => '11011000110',
		32 => '11000110110',
		33 => '10100011000',
		34 => '10001011000',
		35 => '10001000110',
		36 => '10110001000',
		37 => '10001101000',
		38 => '10001100010',
		39 => '11010001000',
		40 => '11000101000',
		41 => '11000100010',
		42 => '10110111000',
		43 => '10110001110',
		44 => '10001101110',
		45 => '10111011000',
		46 => '10111000110',
		47 => '10001110110',
		48 => '11101110110',
		49 => '11010001110',
		50 => '11000101110',
		51 => '11011101000',
		52 => '11011100010',
		53 => '11011101110',
		54 => '11101011000',
		55 => '11101000110',
		56 => '11100010110',
		57 => '11101101000',
		58 => '11101100010',
		59 => '11100011010',
		60 => '11101111010',
		61 => '11001000010',
		62 => '11110001010',
		63 => '10100110000',
		64 => '10100001100',
		65 => '10010110000',
		66 => '10010000110',
		67 => '10000101100',
		68 => '10000100110',
		69 => '10110010000',
		70 => '10110000100',
		71 => '10011010000',
		72 => '10011000010',
		73 => '10000110100',
		74 => '10000110010',
		75 => '11000010010',
		76 => '11001010000',
		77 => '11110111010',
		78 => '11000010100',
		79 => '10001111010',
		80 => '10100111100',
		81 => '10010111100',
		82 => '10010011110',
		83 => '10111100100',
		84 => '10011110100',
		85 => '10011110010',
		86 => '11110100100',
		87 => '11110010100',
		88 => '11110010010',
		89 => '11011011110',
		90 => '11011110110',
		91 => '11110110110',
		92 => '10101111000',
		93 => '10100011110',
		94 => '10001011110',
		95 => '10111101000',
		96 => '10111100010',
		97 => '11110101000',
		98 => '11110100010',
		99 => '10111011110',
		100 => '10111101110',
		101 => '11101011110',
		102 => '11110101110',
		103 => '11010000100',
		104 => '11010010000',
		105 => '11010011100',
		106 => '1100011101011',
	);

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
		$barcode = new Code128Barcode($data);
		return $barcode->render($attributes);
	}

	/**
	 * This function returns an instance of this class.
	 *
	 * @access public
	 * @static
	 * @param $data string                      the data string to be encoded
	 * @return Code128Barcode		            an instance of this class
	 */
	public static function factory($data) {
		return new Code128Barcode($data);
	}

}
?>