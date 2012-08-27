<?php defined('SYSPATH') OR die('No direct access allowed.');

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
 * This class is used to output a barcode image so that the barcode can be
 * used within an HTML image tag.  This class should NOT be called directly;
 * rather, the individual barcode classes should be used instead.
 *
 * @package Barcode
 * @category Controller
 * @version 2012-01-09
 */
class Controller_Barcode extends Controller {

	public function before() {
		parent::before();
		$this->auto_render = FALSE;
	}

	public function action_index() {
		$this->request->redirect('error/404');
	}

	public function action_code39() {
		$data = $this->request->param('id');
		$barcode = new Code39Barcode($data);
		$barcode->output();
	}

	public function action_code128() {
		$data = $this->request->param('id');
		$barcode = new Code128Barcode($data);
		$barcode->output();
	}

	public function action_qrcode() {
		$data = $this->request->param('id');
		$barcode = new QRBarcode($data);
		$barcode->output();
	}

	public function action_upca() {
		$data = $this->request->param('id');
		$barcode = new UPCABarcode($data);
		$barcode->output();
	}

}
?>