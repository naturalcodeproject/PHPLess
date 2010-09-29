<?php
	/**
	* 
	*/
	class PHPLess_Selector {
		private $name = null;
		private $definitions = array();
		private $variables = array();
		private $children = array();
		
		function __construct($selector, $content) {
			var_dump($selector, $content);
		}
		
		private function prepareDefinitions($def) {
			return preg_replace('#[\n\r]#', ' ', trim(trim($def)));
		}
	}
	
?>