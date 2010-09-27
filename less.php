<?php
	/**
	* http://lesscss.org/docs
	*/
	class PHPLess {
		private static $instance;
		private $content;
		private $file;
		private $parsed = array();
	
		public function file($file) {
			$file = dirname($_SERVER['SCRIPT_FILENAME']) . '/' . $file;
			
			if (($this->content = @file_get_contents($file)) === false) {
				throw new Exception('Could not load the requested file: ' . $file);
			}
			
			$this->file = $file;
			
			return $this;
		}
		
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
		
		public function parse($content = '') {
			if (empty($content)) {
				$content = &$this->content;
			}
			
			$content = $this->removeComments($content);
			$content = $this->processAt($content);
			
			$content = $this->parseDefinitions($content, $this->parsed['styles']);
			$this->parsed = array_merge($this->parsed, $this->processDefinitions($content));
		}
		
		private function parseDefinitions($content, &$arr) {
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
				
				$def = $this->fixDefinitions(substr(trim($match), 1, strlen($match) - 2));
				
				$def = $this->parseDefinitions($def, $arr[$k]['children']);
				
				if ($totalMatches == ($key + 1)) {
					$unusedAfter = trim(substr($content, ($start + strlen($match)), strlen($content) - ($start + strlen($match))));
					$unusedAfter = $this->processImport($unusedAfter);
					$parentDef .= $unusedAfter;
				}
				
				if (!empty($def)) {
					$arr[$k] = $this->processDefinitions($def);
				}
				
				if (empty($arr[$k]['children'])) {
					unset($arr[$k]['children']);
				}
				
				$pos = $start + strlen($match);
			}
			
			if (!empty($matches[0])) {
				return $parentDef;
			} else {
				return $content;
			}
		}
		
		private function processDefinitions($definitions) {
			$return = array();
			$defs = array();
			$vars = array();
			$definitions = explode(';', trim($this->fixDefinitions($definitions), ';'));
			foreach($definitions as $key => &$item) {
				list($key, $item) = explode(':', trim($item));
				
				$item = trim($item);
			
				// handle global variables within definitions
				// if (strpos($item, '@') === 0) {
				// 							$var = str_replace('@', '', $item);
				// 							$item = (!empty($this->variables[$var])) ? $this->variables[$var] : $item;
				// 						}
				if (strpos($key, '@') !== false) {
					$vars = array_merge((array)$vars, array(trim($key) => trim($item)));
				} else {
					$defs = array_merge((array)$defs, array(trim($key) => trim($item)));
				}
			}
			
			if (!empty($defs)) {
				$return['definitions'] = $defs;
			}
			
			if (!empty($vars)) {
				$return['variables'] = $vars;
			}
			
			return $return;
		}
		
		private function fixDefinitions($def) {
			return preg_replace('#[\n\r]#', ' ', trim(trim($def)));
		}
		
		private function processAt($content) {
			$content = preg_replace('#((@charset|@font-face) ([^;]*));#', '', $content);
			
			// @import
			//$content = $this->processImport($content);
			
			// @variables
			//$content = $this->processVariables($content);
			
			return $content;
		}
		
		/**
		 * includes and processes @import style sheets
		 */
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
		
		private function removeComments($content) {
			//return preg_replace('/((?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:\/\/.*))/', '', $content);
			
			// much simpler version below. Not sure why it needs to be as advanced as it is above.
			// Daniel, maybe you could enlighten me?
			return preg_replace('#(/\*.*?\*/)#s', '', $content);
		}
		
		public function output() {
			$this->parse();
			
			echo "<pre>";
			var_dump($this->parsed);
			exit;
		}
	}
?>