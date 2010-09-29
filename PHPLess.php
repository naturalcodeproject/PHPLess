<?php
	/**
	* 
	*/
	include "PHPLess/Sheet.php";
	
	class PHPLess {
		private static $instance;
		private $content;
		private $file;
		
		public static function getInstance($file = '') {
			if (!isset(self::$instance)) {
				// create the instance
				$class = __CLASS__;
				self::$instance = new $class;
				unset($class);
			}
			
			if (!empty($file)) {
				// if there is a file then set it up
				self::$instance->file($file);
			}
			
			return self::$instance;
		}
		
		public function file($file) {
			$file = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $file;
			
			if (($this->content = @file_get_contents($file)) === false) {
				throw new Exception('Could not load the requested file: ' . $file);
			}
			
			$this->file = $file;
			
			return $this;
		}
		
		public function output() {
			$parser = new PHPLess_Sheet($this->content);
			
			echo "<pre>";
				var_dump($parser->toCSS());
			echo "</pre>";
			exit;
		}
	}
	
?>