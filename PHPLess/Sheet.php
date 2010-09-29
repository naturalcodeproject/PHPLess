<?php
	/**
	* 
	*/
	include 'Selector.php';
	
	class PHPLess_Sheet {
		private $variables = array();
		private $selectors = array();
		
		public function __construct($content) {
			$this->selectors = $this->parse($content);
		}
		
		public function parse($content) {
			$content = preg_replace('#((@charset|@font-face) ([^;]*));#', '', $content);
			$content = preg_replace('#(/\*.*?\*/)#s', '', $content);
			$content = trim($content);
			
			$this->parseSelectors($content);
			echo "<PRE>";
			var_dump($this->selectors);
			exit;
		}
		
		private function parseSelectors($content, &$arr) {
			preg_match_all('#\{(?:[^{}]++|(?R))*\}#m', $content, $matches, PREG_OFFSET_CAPTURE);
			
			$parentDef = null;
			$pos = 0;
			$totalMatches = count($matches[0]);
			foreach($matches[0] as $key => $match) {
				list($match, $start) = $match;
				
				$k = preg_replace('#[\s]+#', ' ', preg_replace('#[\n\r]#', ' ', trim(substr($content, $pos, ($start - $pos)))));
				
				if (($cut = strrpos($k, ';')) !== false) {
					$unusedBefore = trim(substr($k, 0, $cut+1));
					$unusedBefore = $this->processImport($unusedBefore);
					$parentDef .= $unusedBefore;
					$k = trim(substr($k, $cut+1, strlen($k) - $cut));
				}
				
				$def = substr(trim($match), 1, strlen($match) - 2);
				
				$items = $this->processSelectorPath($k);
				foreach($items as $selector) {
					$this->selectors[$selector] = '';
				}
				
				//$selector = new PHPLess_Selector($k, $def);
				
				if ($totalMatches == ($key + 1)) {
					$unusedAfter = trim(substr($content, ($start + strlen($match)), strlen($content) - ($start + strlen($match))));
					$unusedAfter = $this->processImport($unusedAfter);
					$parentDef .= $unusedAfter;
				}
				
				$pos = $start + strlen($match);
			}
			
			if (!empty($matches[0])) {
				return $parentDef;
			} else {
				return $content;
			}
		}
		
		private function processSelectorPath($selectors) {
			$return = array();
			if (preg_match('#^(@media)#im', trim($selectors))) {
				$selectors = preg_replace('#^(@media)#im', '', trim($selectors));
				$return[] = '@media';
			} else {
				$selectors = trim($selectors);
			}
			
			$selectors = explode(',', trim($selectors));
			
			foreach($selectors as $item) {
				$return[] = trim($item);
			}
			
			
			return $return;
		}
		
		private function processImport($content) {
			if (strpos($content, '@import') !== false) {
				$matches = array();
				preg_match_all('#(@import\s+url\((?:\'|"|)([^)\'"]*)(?:\'|"|)\);)#i', $content, $matches, PREG_SET_ORDER);
				
				foreach($matches as $match) {
					$content = str_replace($match[0], '', $content);
					
					if (strpos($match[2], 'http') !== 0) {
						$match[2] = dirname($this->file) . '/' . $match[2];
					}
					
					if (($import = @file_get_contents($match[2])) === false) {
						// can't find the file. Skip it.
						continue;
					}
					
					$this->parse($import);
				}
			}
			
			return $content;
		}
		
		public function toCSS() {
			return $this->parsed;
		}
	}
?>