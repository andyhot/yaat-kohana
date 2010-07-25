<?php defined('SYSPATH') OR die('No direct access allowed.');

class Yaat_Controller extends Controller {

	public function js() {
		// Get the filename
		$file = implode('/', $this->uri->segment_array(1));
		$ext = strrchr($file, '.');

		if ($ext !== FALSE) {
			$file = substr($file, 0, -strlen($ext));
			$ext = substr($ext, 1);
		}
		
		if ($ext!='js') {
			$file = "yaat" . substr($file, 3);
		} else {
			$file = "yaat" . substr($file, 2);
		}

		try {
			//echo $file . " - " . $ext;
			// Attempt to display the output
			echo new View($file, NULL, $ext);
		} catch (Kohana_Exception $e) {
			Event::run('system.404');
		}
	}
	
	public function css() {
		$this->js();		
	}
}