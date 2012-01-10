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

include_once(MODPATH.'barcode/vendor/google_chart_api/GoggleBarcodeGenerator.php');

/**
 * This class generates a Quick Response (QR) barcode.
 *
 * @package Barcode
 * @category Creator
 * @version 2012-01-09
 *
 * @see http://en.wikipedia.org/wiki/QR_code
 */
abstract class Base_QRBarcode extends Kohana_Object implements Barcode_Interface {

	/**
	 * This variable stores the file's URI.
	 *
	 * @access protected
	 * @var array
	 */
    protected $file = NULL;

	/**
	 * This variable stores the height and width of the image.
	 *
	 * @access protected
	 * @var array
	 */
    protected $size = NULL;

    /**
     * Initializes this bar code creator.
     *
     * @access public
     * @param $data string                      the data string to be encoded
     * @param $size integer                     the size of the barcode
     * @param $margin integer                   the margin around the barcode
     */
    public function __construct($data, $size = 150, $margin = 4) {
        $this->file = GoogleBarcodeGenerator::qr_code($data, $size, 'UTF-8', 'L', $margin);
        $this->size = $size;
    }

    /**
     * This function controllers which properties are accessible.
     *
     * @access public
     * @param string $key                       the name of the property
     * @return mixed                            the value of the property
     */
    public function __get($key) {
        switch ($key) {
            case 'file':
                return $this->file;
            case 'width':
            case 'height':
                return $this->size;
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
        $barcode = file_get_contents($this->file);
        header("Cache-Control: no-cache, must-revalidate");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
        header('Content-Type: image/png');
        if (is_string($file_name)) {
            header("Content-Disposition: attachment; filename=\"{$file_name}\"");
        }
        echo $barcode;
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
        $properties = '';
        if (is_array($attributes)) {
            foreach ($attributes as $key => $val) {
                $properties .= "{$key}=\"{$val}\" ";
            }
        }
        $html = "<img src=\"{$this->file}\" {$properties}/>";
        return $html;
    }

    /**
     * This function saves the image of the QR code to disk.
     *
     * @access public
     * @param string $file                      the URI for where the image will be stored
     */
    public function save($file) {
        file_put_contents($file, file_get_contents($this->file));
        $this->file = $file;
    }

    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
        $barcode = new QRBarcode($data);
        return $barcode->render($attributes);
    }

	/**
	 * This function returns an instance of this class.
	 *
	 * @access public
	 * @static
     * @param $data string                      the data string to be encoded
     * @param $size integer                     the size of the barcode
     * @param $margin integer                   the margin around the barcode
	 * @return QRBarcode		                an instance of this class
	 */
    public static function factory($data, $size = 150, $margin = 4) {
        return new QRBarcode($data, $size, $margin);
    }

}
?>