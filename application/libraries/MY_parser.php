<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2019, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package	CodeIgniter
 * @author	EllisLab Dev Team
 * @copyright	Copyright (c) 2008 - 2014, EllisLab, Inc. (https://ellislab.com/)
 * @copyright	Copyright (c) 2014 - 2019, British Columbia Institute of Technology (https://bcit.ca/)
 * @license	https://opensource.org/licenses/MIT	MIT License
 * @link	https://codeigniter.com
 * @since	Version 1.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Extended Parser Class
 *
 * @package	CodeIgniter
 * @subpackage	Libraries
 * @category	Parser
 * @author	Shivasis Biswal
 * @useful 	CodeIgniter v3.13
 */

class MY_Parser extends CI_Parser {
	
	// Define supported filters
    	protected $filters = [];														  
	private $blocks = [];
	private $block_stack = [];
	private $template_data = [];													  
	private $plugins = [];
	private $cache_enabled = TRUE;


    public function __construct() {
        parent::__construct();
	
        // Initialize the filters
        $this->filters = [
            'upper' => 'strtoupper',
            'lower' => 'strtolower',
            'capitalize' => 'ucwords',
            'trim' => 'trim',
            'length' => 'strlen',
            'reverse' => 'strrev',
            'md5' => 'md5',
            'nl2br' => 'nl2br',
            'esc' => 'htmlspecialchars',
            'absolute' => 'abs',
            'round' => 'round',
            'limit_chars' => function($value, $limit) { return substr($value, 0, $limit); },
            'limit_words' => function($value, $limit) {
                $words = explode(' ', $value);
                return implode(' ', array_slice($words, 0, $limit));
            },
            'highlight' => function($value, $term) {
                return str_replace($term, "<strong>{$term}</strong>", $value);
            },
			'date' => function($value, $format) {
            return date($format, strtotime($value));
			},
			'date_modify' => function($value, $modify) {
				$date = new DateTime($value);
				$date->modify($modify);
				return $date->format('Y-m-d H:i:s');
			},
			'default' => function($value, $default) {
				return empty($value) ? $default : $value;
			},
        ];
		
    }
	

	public function parse($template, $data, $return = FALSE) {
        
		try {
			if ($template == '') {
				throw new Exception('Template name is empty.');
			}
		} catch (Exception $e) {
			$this->log_error($e->getMessage());
			return FALSE;
		}
		
		// Merge passed data with the internal template data
    	$data = array_merge($this->template_data, $data);
		
		// Sanitize data before parsing
		$data = array_map([$this, 'sanitize_input'], $data);
		
		
		// Cache key based on template and data
		if ($this->cache_enabled) {
			$cache_key = $this->get_cache_key($template, $data);
			$cached_output = $this->get_cached_output($cache_key);
			if ($cached_output !== FALSE) {
				if ($return) {
					return $cached_output;
				} else {
					$CI =& get_instance();
					$CI->output->append_output($cached_output);
					return;
				}
			}
		}
		

		// Load the template content
    	$template_content = $this->_load_template($template, $data);

        // Extract and protect the no-parse areas
        $no_parse_blocks = $this->_extract_noparse($template_content);

        // Parse loops and single variables first
        foreach ($data as $key => $val) {
            if (is_array($val) || is_object($val) && !($val instanceof stdClass)) {
                $template_content = $this->_parse_pair($key, $val, $template_content);
            } else {
                $template_content = $this->_parse_single($key, $val, $template_content);
            }
        }
		
		// Call apply_plugins in the parse method
		 $template_content = $this->apply_plugins($template_content, $data);

        // Parse conditionals
        $template_content = $this->_parse_conditionals($template_content, $data);

        // Restore the no-parse areas
        $template_content = $this->_restore_noparse($template_content, $no_parse_blocks);

        // Ensure the template content is a string and replace any remaining placeholders
        $template_content = (string)$template_content;
		

		// Existing parse logic
		if ($this->cache_enabled) {
			$this->cache_output($cache_key, $template_content);
		}

        if ($return) {
            return $template_content;
        } else {
            $CI->output->append_output($template_content);
        }
    }
															  
	public function set_data($data) {
		$this->template_data = array_merge($this->template_data, $data);
	}

	public function assign($key, $value) {
		$this->template_data[$key] = $value;
	}
															  
	// Add a method to register custom filters
    public function add_filter($name, $callback) {
        if (is_callable($callback)) {
            $this->filters[$name] = $callback;
        }
    }
															  
	public function register_plugin($name, $callback) {
		if (is_callable($callback)) {
			$this->plugins[$name] = $callback;
		}
	}
															  
	public function enable_cache($enabled) {
    	$this->cache_enabled = $enabled;
	}		
															  
	public function sanitize_input($data) {
		if (is_array($data)) {
			return array_map([$this, 'sanitize_input'], $data);
		}
		return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
	}
															  
	private function _load_template($template, $data) {
		$CI =& get_instance();
		$template_content = $CI->load->view($template, $data, TRUE);

		// Process blocks
		$this->_parse_blocks($template_content);

		// Process includes and extends
		$this->_process_includes($template_content, $data);
		$this->_process_extends($template_content, $data);

		return $template_content;
	}
															  
	
	private function apply_plugins($template_content, $data) {
		foreach ($this->plugins as $plugin) {
			$template_content = call_user_func($plugin, $template_content, $data);
		}
		return $template_content;
	}
															  
	private function get_cache_key($template, $data) {
		return md5($template . serialize($data));
	}

	private function get_cached_output($cache_key) {
		$cache_file = APPPATH . 'cache/' . $cache_key . '.cache';
		if (file_exists($cache_file)) {
			return file_get_contents($cache_file);
		}
		return FALSE;
	}

	private function cache_output($cache_key, $content) {
		$cache_file = APPPATH . 'cache/' . $cache_key . '.cache';
		file_put_contents($cache_file, $content);
	}

															  
    private function _extract_noparse(&$template) {
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');
		$pattern = '/' . $left . '\s*noparse\s*' . $right . '(.*?)' . $left . '\s*\/\s*noparse\s*' . $right . '/s';
        $no_parse_blocks = [];
        
        $template = preg_replace_callback($pattern, function ($matches) use (&$no_parse_blocks) {
            $key = '{{ noparse_' . count($no_parse_blocks) . ' }}';
            $no_parse_blocks[$key] = $matches[1];
            return $key;
        }, $template);

        return $no_parse_blocks;
    }

    private function _restore_noparse($template, $no_parse_blocks) {
        foreach ($no_parse_blocks as $key => $content) {
            $template = str_replace($key, $content, $template);
        }
        return $template;
    }

	
   protected function _parse_conditionals($content, $data) {
		$l_delim = preg_quote($this->l_delim, '/');
		$r_delim = preg_quote($this->r_delim, '/');
		$pattern = '/' . $l_delim . '\s*if\s+(.*?)\s*' . $r_delim . '(.*?)(' . $l_delim . '\s*elseif\s+(.*?)\s*' . $r_delim . '(.*?))*(' . $l_delim . '\s*else\s*' . $r_delim . '(.*?))?' . $l_delim . '\s*endif\s*' . $r_delim . '/s';

		$content = preg_replace_callback($pattern, function($matches) use ($data) {
			$condition = $this->evaluate_condition($matches[1], $data);
			$if_content = $matches[2];
			$elseif_conditions = isset($matches[3]) ? array_slice($matches, 3, -3) : [];
			$else_content = isset($matches[7]) ? $matches[7] : '';

			if ($condition) {
				return $if_content;
			} else {
				for ($i = 0; $i < count($elseif_conditions); $i += 5) {
					if (isset($elseif_conditions[$i + 3]) && isset($elseif_conditions[$i + 4])) {
						$elseif_condition = $this->evaluate_condition($elseif_conditions[$i + 3], $data);
						$elseif_content = $elseif_conditions[$i + 4];
						if ($elseif_condition) {
							return $elseif_content;
						}
					}
				}
				return $else_content;
			}
		}, $content);

		return $content;
	}

	
	protected function evaluate_condition($condition, $data) {
		$condition = preg_replace_callback('/\b([a-zA-Z_][a-zA-Z0-9_\.\->]*)\b/', function($matches) use ($data) {
			$value = $this->get_value($matches[1], $data);
			if (is_string($value)) {
				return "'" . addslashes($value) . "'";
			} elseif (is_numeric($value)) {
				return $value;
			}
			return 'false';
		}, $condition);

		return eval('return ' . $condition . ';');
	}


   protected function get_value($key, $data) {
		if (strpos($key, '.') !== false) {
			$keys = explode('.', $key);
		} elseif (strpos($key, '->') !== false) {
			$keys = explode('->', $key);
		} else {
			$keys = [$key];
		}

		$value = $data;

		foreach ($keys as $k) {
			if (is_array($value) && isset($value[$k])) {
				$value = $value[$k];
			} elseif (is_object($value) && isset($value->$k)) {
				$value = $value->$k;
			} else {
				return null;  // If a key doesn't exist, return null
			}
		}

		return $value;
	}

	
	protected function _parse_single($key, $val, $string) {
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');

		// If $val is an array or object, recursively parse nested keys
		if (is_array($val) || is_object($val)) {
			foreach ($val as $prop => $value) {
				$separators = ['.', '->'];
				foreach ($separators as $separator) {
					$nested_key = $key . $separator . $prop;
					$string = $this->_parse_single($nested_key, $value, $string);
				}
			}
		} else {
			
			// $pattern = '/' . $left . '\s*' . preg_quote($key, '/') . '\s*' . $right . '/';	
			// Pattern to match variable with filters, e.g., {key|upper|lower} with optional spaces
    		 $pattern = '/' . $left . '\s*' . preg_quote($key, '/') . '\s*(?:\|\s*([\w|\s:,-]*)\s*)?' . $right . '/';


			// Callback to apply filters
			$callback = function ($matches) use ($val) {
				$filters = isset($matches[1]) ? explode('|', $matches[1]) : [];
				return $this->apply_filters($val, $filters);
			};
			
			// Replace the placeholder with the resolved value
			// $string = preg_replace($pattern, (string)$val, $string);
			$string = preg_replace_callback($pattern, $callback, $string);
		}

		return $string;
	}
	
	
	protected function apply_filters($value, $filters) {
		foreach ($filters as $filter) {
			// Check if the filter contains a colon
			if (strpos($filter, ':') !== false) {
				// Split the filter into the filter name and its parameters
				list($filter_name, $filter_params) = explode(':', $filter, 2);
				$filter_params = array_map('trim', explode(',', $filter_params));
			} else {
				$filter_name = $filter;
				$filter_params = [];
			}

			if (isset($this->filters[$filter_name])) {
				// Prepend the value to the parameters
				array_unshift($filter_params, $value);
				// Call the filter with parameters
				$value = call_user_func_array($this->filters[$filter_name], $filter_params);
			}
		}
		return $value;
	}

	
	protected function _parse_pair($variable, $data, $string) {
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');

		// New pattern to support {% item in items %}
		$new_pattern = '/' . $left . '\s*(\w+)\s+in\s+' . preg_quote($variable, '/') . '\s*' . $right . '(.*?)' 
			. $left . '\s*end\s+' . preg_quote($variable, '/') . '\s*' . $right . '/s';

		// Old pattern for backward compatibility
		$old_pattern = '/' . $left . '\s*' . preg_quote($variable, '/') . '\s*' . $right . '(.*?)' 
			. $left . '\s*\/' . preg_quote($variable, '/') . '\s*' . $right . '/s';

		// Handle the new syntax first
		if (preg_match($new_pattern, $string, $match)) {
			$loop_variable = $match[1];  // The loop variable (e.g., "item")
			$body = $match[2];           // The loop body
			$result = $this->_process_pair_body($data, $body, $loop_variable);
			return preg_replace($new_pattern, $result, $string, 1);
		}

		// Handle the old syntax
		if (preg_match($old_pattern, $string, $match)) {
			$body = $match[1];  // The loop body
			$result = $this->_process_pair_body($data, $body);
			return preg_replace($old_pattern, $result, $string, 1);
		}

		return $string;
	}

	protected function _process_pair_body($data, $body, $loop_variable = null) {
		$str = '';
		if (is_array($data) || is_object($data)) {
			
			$index = 0; 		// Current iteration index

			foreach ($data as $row) {
				
				// Ensure $row is an array
				$row_array = is_object($row) ? get_object_vars($row) : (array)$row;

				$extra = [
					'index' => $index,
					'i' => $index + 1,
				];
				$row_extra = array_merge($extra, $row_array);
				
				// Inject the loop variable into the row data (for new syntax)
            	if ($loop_variable !== null) {
                	$row_extra = [$loop_variable => $row_extra];
            	}

				// Parse the loop body
				$temp = $body;
				$temp = $this->_parse_conditionals($temp, $row_extra);
				$temp = $this->_parse_row($row_extra, $temp);
				
				// Replace the loop variable in the body (for new syntax)
				if ($loop_variable !== null) {
					$temp = preg_replace('/\b' . preg_quote($loop_variable, '/') . '\b/', $loop_variable, $temp);
				}

				$str .= $temp;

				$index++;
			}
		}
		return $str;
	}
	

	protected function _parse_row($row, $template) {
		if (is_array($row) || is_object($row)) {
			foreach ($row as $key => $val) {
				if (is_array($val) || is_object($val)) {
					$nested_keys = [$key . '.', $key . '->'];
					foreach ($nested_keys as $nested_key) {
						$template = $this->_parse_row_with_key($nested_key, $val, $template);
					}
				} else {
					$template = $this->_parse_single($key, $val, $template);
				}
			}
		}
		return $template;
	}

	protected function _parse_row_with_key($prefix, $row, $template) {
		if (is_array($row) || is_object($row)) {
			foreach ($row as $key => $val) {
				$full_key = $prefix . $key;
				if (is_array($val) || is_object($val)) {
					$separators = ['.', '->'];
					foreach($separators as $separator){
						$template = $this->_parse_row_with_key($full_key . $separator, $val, $template);
					}
				} else {
					$template = $this->_parse_single($full_key, $val, $template);
				}
			}
		}
		return $template;
	}
	
	private function _process_includes(&$template, $data) {
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');
		$pattern = '/' . $left . '\s*include\s*:\s*([\w\.]+)\s*' . $right . '/';

		$template = preg_replace_callback($pattern, function($matches) use ($data) {
			$filename = $matches[1];
			if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
				$filename .= '.php';
			}
			return $this->_load_template($filename, $data);
		}, $template);
	}

	private function _process_extends(&$template, $data) {
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');
		$pattern = '/' . $left . '\s*extend\s*:\s*([\w\.]+)\s*' . $right . '/';
		if (preg_match($pattern, $template, $matches)) {
			$filename = $matches[1];
			if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
				$filename .= '.php';
			}
			$base_template = $this->_load_template($filename, $data);
			$template = preg_replace($pattern, $base_template, $template);
		}
	}
													  
															  
	private function _start_block($block_name) {
		array_push($this->block_stack, $block_name);
		ob_start();
	}

	private function _end_block() {
		$block_name = array_pop($this->block_stack);
		$this->blocks[$block_name] = ob_get_clean();
	}

	private function _get_block($block_name) {
		if (isset($this->blocks[$block_name])) {
			return $this->blocks[$block_name];
		}
		return '';
	}

	private function _parse_blocks(&$template) {
		
		$left = preg_quote($this->l_delim, '/');
		$right = preg_quote($this->r_delim, '/');
		$pattern = '/' . $left . '\s*block\s+(\w+)\s*' . $right . '(.*?)' . $left . '\s*endblock\s*' . $right . '/s';

		$template = preg_replace_callback($pattern, function($matches) {
			$block_name = $matches[1];
			$block_content = $matches[2];
			if (in_array($block_name, $this->block_stack)) {
				return '';
			}
			return $this->_get_block($block_name);
		}, $template);
	}
															  
	private function log_error($message) {
    	log_message('error', 'MY_Parser: ' . $message);
	}

} // end of MY_Parser class
