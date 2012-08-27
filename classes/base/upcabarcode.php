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
 * This class generates a UPC-A barcode.
 *
 * @package Barcode
 * @category Creator
 * @version 2012-08-27
 *
 * @see http://snipplr.com/view/12870/
 * @see http://snipplr.com/view.php?codeview&id=12870
 */
abstract class Base_UPCABarcode extends Kohana_Object implements Barcode_Interface {

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
        if (!is_string($data) || !preg_match('/^[0-9]{11}[0-9]?$/i', $data)) {
            throw new Kohana_InvalidArgument_Exception('Message: Unable to encode :data for barcode. Reason: Invalid data string passed.', array('data' => $data));
        }
        if (strlen($data) == 12) {
            $data = substr($data, 0, -1);
        }
        $this->data = strtoupper($data);
    }

    /**
     * This function controls which properties are accessible.
     *
     * @access public
     * @param string $key        the name of the property
     * @return mixed             the value of the property
     */
    public function __get($key) {
        switch ($key) {
            case 'file':
                $file = (is_null($this->file)) ? '/barcode/upca/' . urlencode($this->data) : $this->file;
                return $file;
            default:
                return NULL;
        }
    }

    /**
     * This function sends back the bar code image.
     *
     * @access public
     * @param $file_name                 the file name
     */
    public function output($file_name = NULL) {
        // Caches the data string
        $code = $this->data;

        // Computes the EAN-13 checksum digit
        $code .= UPCABarcode::checksum($code);
        
        // Creates the bar encoding using a binary string
        $bars = self::$patterns['end'][0];
        for ($x = 0; $x < 6; $x++) {
            $bars .= self::$patterns['left'][$code[$x]];
        }
        $bars .= self::$patterns['center'][0];
        for ($x = 6; $x < 12; $x++) {
            $bars .= self::$patterns['right'][$code[$x]];
        }
        $bars .= self::$patterns['end'][0];
        
        // Generates the barcode image
        $pixels = 2;
        $height = 100;
        $image = imagecreate($pixels * 95 + 30, $height + 30);
        $fg = imagecolorallocate($image, 0, 0, 0);
        $bg = imagecolorallocate($image, 255, 255, 255);
        imagefilledrectangle($image, 0, 0, $pixels * 95 + 30, $height + 30, $bg);
        $length = strlen($bars);
        for ($x = 0; $x < $length; $x++) {
            $sh = (($x < 10) || ($x >= 45 && $x < 50) || ($x >= 85)) ? 10 : 0;
            $color = ($bars[$x] == '1') ? $fg : $bg;
            imagefilledrectangle($image, ($x * $pixels) + 15, 5, ($x + 1) * $pixels + 14, $height + 5 + $sh, $color);
        }
        
        // Adds the human readable label
        imagestring($image, 4, 5, $height - 5, $code[0], $fg);
        for ($x = 0; $x < 5; $x++) {
            imagestring($image, 5, $pixels * (13 + $x * 6) + 15, $height + 5, $code[$x + 1], $fg);
            imagestring($image, 5, $pixels * (53 + $x * 6) + 15, $height + 5, $code[$x + 6], $fg);
        }
        imagestring($image, 4, $pixels * 95 + 17, $height - 5, $code[11], $fg);

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
        $file = (is_null($this->file)) ? '/barcode/upca/' . urlencode($this->data) : $this->file;
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
        file_put_contents($file, file_get_contents("/barcode/upca/{$data}"));
        $this->file = $file;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * This function acts as a lookup table for matching UPC-A patterns.
     *
     * @access protected
     * @static
     * @return array                            the lookup table
     */
    protected static $patterns = array(
        'center' => array('01010'),
        'end' => array('101'),
        'left' => array(
            '0001101',
            '0011001',
            '0010011',
            '0111101',
            '0100011',
            '0110001',
            '0101111',
            '0111011',
            '0110111',
            '0001011',
        ),
        'right' => array(
            '1110010',
            '1100110',
            '1101100',
            '1000010',
            '1011100',
            '1001110',
            '1010000',
            '1000100',
            '1001000',
            '1110100',
        ),
    );

    /**
     * This function computes the checksum for the specified data string.
     *
     * @access public
     * @static
     * @param $data string                      the data string to be evaluated
     * @return mixed                            the checksum for the specified data string
     */
    public static function checksum($data) { // Computes the EAN-13 Checksum digit
        $ncode = '0' . $data;
        $even = 0;
        $odd = 0;
        for ($x = 0; $x < 12; $x++) {
            if ($x % 2) { $odd += $ncode[$x]; }
            else { $even += $ncode[$x]; }
        }
        $checksum = (10 - (($odd * 3 + $even) % 10)) % 10;
        return $checksum;
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
        $barcode = new UPCABarcode($data);
        return $barcode->render($attributes);
    }

	/**
	 * This function returns an instance of this class.
	 *
	 * @access public
	 * @static
     * @param $data string                      the data string to be encoded
	 * @return UPCABarcode		                an instance of this class
	 */
    public static function factory($data) {
        return new UPCABarcode($data);
    }

}
?>