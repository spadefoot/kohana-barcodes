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
 * This class creates the URLs for accessing Google's Chart API with regards to bar codes.
 *
 * @package Barcode
 * @category Creator
 * @version 2012-01-09
 */
class GoogleBarcodeGenerator {

    /**
     * This function generates a URL to Google's Chart API so that a QR code can be generated
     * and used in an image tag.
     *
     * @access public
     * @static
     * @param $data string               an unencoded string of text (up to 2K in size)
     * @param $size integer              the width/height of the resulting image
     * @param $encoding string           the encoding method to be used
     * @param $loss string               the error correction level (i.e. 'L', 'M', 'Q', and 'H')
     * @param $margin integer            the width (in rows, not pixels) of the white border around
     *                                   the data portion of the chart
     *
     * @see http://google-code-updates.blogspot.com/2008/07/qr-codes-now-available-on-google-chart.html
     * @see http://code.google.com/apis/chart/docs/gallery/qr_codes.html
     */
    public static function qr_code($data, $size, $encoding = 'UTF-8', $loss = 'L', $margin = 4) {
        $data = urlencode($data);
        $encoding = 'UTF-8'; // temporarily overrides encoding
        return "http://chart.apis.google.com/chart?chs={$size}x{$size}&cht=qr&chl={$data}&choe={$encoding}&chld={$loss}|{$margin}";
    }

}
?>