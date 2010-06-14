<?php

	/**
	 * Easy and extended transform
	 * Adds vendor-specific versions of transform
	 * 
	 * Usage:     Use any currently known transform-property wihtout vendor-prefixes,  
	 *            BUT NOT YET! transform-origin.
	 *            For IE you need to define width and height dimensions and use the same units (px/em)
	 *            for dimensions and translation
	 * Example 1: width: 100px; height: 100px; transform: translate(2px, 2px);
	 * Example 1: width: 20em; height: 20em; transform: translate(1em, 2em);
	 * Example 3: width: 100px; height: 100px; transform: translate(2px, 2px) rotate(90deg);
	 * Example 4:width: 100px; height: 100px;  transform: skew(15deg,25deg);
	 * Status:    Beta
	 * Version:   1.0
	 * 
	 * @param mixed &$parsed
	 * @return void
	 */
	function transform(&$parsed){
		global $browser, $cssp;
		foreach($parsed as $block => $css){
			foreach($parsed[$block] as $selector => $styles){
				$width = 0;
				$height = 0;
				$width_unit = 'px';
				$height_unit = 'px';
				$rotate_x = 0;
				$rotate_y = 0;
				$translate_x = 0;
				$translate_y = 0;
				$translate_x_unit = 'px';
				$translate_y_unit = 'px';
				$scale_x = 1;
				$scale_y = 1;
				if(isset($parsed[$block][$selector]['transform'])){
					$num_values = count($parsed[$block][$selector]['transform']);
					for($i = 0; $i < $num_values; $i++){
						$value = $parsed[$block][$selector]['transform'][$i];
						$newproperties = array(
							'-moz-transform' => array($value),
							'-o-transform' => array($value),
							'-webkit-transform' => array($value)
						);
						$cssp->insert_properties($newproperties, $block, $selector, null, 'transform');
					}

					// Applying the matrix-filter for IE
					if($browser->engine == 'ie' && floatval($browser->engine_version) < 9){
						// If we have width & height defined, the replicate transforms with the help of IE's matrix-filter
						if(isset($parsed[$block][$selector]['width'][0]) && isset($parsed[$block][$selector]['height'][0])){
							$num_values = count($parsed[$block][$selector]['transform']);
							for($i = 0; $i < $num_values; $i++){
								$value = $parsed[$block][$selector]['transform'][$i];
								// If we are dealing with a matrix-transformation
								if(preg_match('/matrix\(\D*([0-9\-]+)\s([0-9\-]+)\s([0-9\-]+)\s([0-9\-]+)\s([0-9\-]+)\s([0-9\-]+)\D*\)/i',$value,$matches) == 1){
									$matrix_a = $matches[1];
									$matrix_b = $matches[2];
									$matrix_c = $matches[3];
									$matrix_d = $matches[4];
								}
								else{
									// If we are dealing with a translation
									if(preg_match('/translate\(\D*([0-9\.\-]+)([a-z%]*),\s*([0-9\.\-]+)([a-z%]*)\D*\)/i',$value,$matches) == 1){
										$translate_x = $matches[1];
										$translate_x_unit = $matches[2];
										$translate_y = $matches[3];
										$translate_y_unit = $matches[4];
									}
									// If we are dealing with a rotation
									if(preg_match('/rotate\(\D*([0-9\-]+)\D*\)/i',$value,$matches) == 1){
										$rotate_x = $matches[1];
										$rotate_y = $matches[1];
									}
									// If we are dealing with a scaling on the X-axis
									if(preg_match('/scaleX\(\D*([0-9\.]+)\D*\)/i',$value,$matches) == 1){
										$scale_x = $matches[1];
									}
									// If we are dealing with a scaling on the Y-axis
									if(preg_match('/scaleY\(\D*([0-9\.]+)\D*\)/i',$value,$matches) == 1){
										$scale_y = $matches[1];
									}
									// If we are dealing with a scaling on both axis
									if(preg_match('/scale\(\D*([0-9\.]+)\D*\)/i',$value,$matches) == 1){
										$scale_x = $matches[1];
										$scale_y = $matches[1];
									}
									// If we are dealing with a skew-transform on the X-axis
									if(preg_match('/skewX*\(\D*([0-9\-]+)\D*\)/i',$value,$matches) == 1){
										$rotate_x = $matches[1] * -1;
									}
									// If we are dealing with a skew-transform on the Y-axis
									if(preg_match('/skewY\(\D*([0-9\-]+)\D*\)/i',$value,$matches) == 1){
										$rotate_y = $matches[1] * -1;
									}
									// If we are dealing with a skew-transform on the both axis
									if(preg_match('/skew\(\D*([0-9\-]+)\D*,\D*([0-9\-]+)\D*\)/i',$value,$matches) == 1){
										$rotate_x = $matches[1] * -1;
										$rotate_y = $matches[2] * -1;
									}
								}
							}
							// Convert translation, rotation, scale and skew into matrix values
							$radian_x = deg2rad(floatval($rotate_x));
							$radian_y = deg2rad(floatval($rotate_y));
							$matrix_a = floatval(number_format(cos($radian_x),8,'.',''));
							$matrix_b = -1 * floatval(number_format(sin($radian_x),8,'.',''));
							$matrix_c = floatval(number_format(sin($radian_y),8,'.',''));
							$matrix_d = floatval(number_format(cos($radian_y),8,'.',''));
							$filter = 'progid:DXImageTransform.Microsoft.Matrix(Dx=1.0,Dy=1.0,M11='.($matrix_a * floatval($scale_x)).',M12='.($matrix_b * floatval($scale_x)).',M21='.($matrix_c * floatval($scale_y)).',M22='.($matrix_d * floatval($scale_y)).',sizingMethod=\'auto expand\')';
							
							// Adjust transforms for IEs, needs to come in first
							$newproperties = array();
							// If position-property is not set, or static set it
							if(!isset($parsed[$block][$selector]['position']) || $parsed[$block][$selector]['position'][0] == 'static'){
								$newproperties['position'] = array('relative');
								$cssp->insert_properties($newproperties, $block, $selector, null, 'transform');
								CSSP::comment($parsed[$block][$selector], 'position', 'Added by transform plugin');
							}
							$newproperties = array();
							// If position-property is not set, or not set to absolute
							if($parsed[$block][$selector]['position'][0] != 'absolute'){
								// Translation on x-axis
								if($translate_x > 0 && !isset($parsed[$block][$selector]['left']))
								{
									$newproperties['left'] = array($translate_x.$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'left', 'Added by transform plugin');
								}
								elseif($translate_x < 0 && !isset($parsed[$block][$selector]['right']))
								{
									$newproperties['right'] = array(abs($translate_x).$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'right', 'Added by transform plugin');
								}
								elseif($translate_x != 0 && !isset($parsed[$block][$selector]['margin-left']) && !isset($parsed[$block][$selector]['margin-right']))
								{
									$newproperties['margin-left'] = array($translate_x.$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-left', 'Added by transform plugin');
									$newproperties['margin-right'] = array((-1 * $translate_x).$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-right', 'Added by transform plugin');
								}

								// Translation on y-axis
								if($translate_y > 0 && !isset($parsed[$block][$selector]['top']))
								{
									$newproperties['top'] = array($translate_y.$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'top', 'Added by transform plugin');
								}
								elseif($translate_y < 0 && !isset($parsed[$block][$selector]['bottom']))
								{
									$newproperties['bottom'] = array(abs($translate_y).$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'bottom', 'Added by transform plugin');
								}
								elseif($translate_y != 0 && !isset($parsed[$block][$selector]['margin-left']) && !isset($parsed[$block][$selector]['margin-right']))
								{
									$newproperties['margin-top'] = array($translate_y.$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-top', 'Added by transform plugin');
									$newproperties['margin-bottom'] = array((-1 * $translate_y).$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-bottom', 'Added by transform plugin');
								}
							}
							// If position-property is set to absolute
							else
							{
								if($translate_x != 0 && !isset($parsed[$block][$selector]['margin-left']) && !isset($parsed[$block][$selector]['margin-right']))
								{
									$newproperties['margin-left'] = array($translate_x.$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-left', 'Added by transform plugin');
									$newproperties['margin-right'] = array((-1 * $translate_x).$translate_x_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-right', 'Added by transform plugin');
								}
								if($translate_y != 0 && !isset($parsed[$block][$selector]['margin-left']) && !isset($parsed[$block][$selector]['margin-right']))
								{
									$newproperties['margin-top'] = array($translate_y.$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-top', 'Added by transform plugin');
									$newproperties['margin-bottom'] = array((-1 * $translate_y).$translate_y_unit);
									CSSP::comment($parsed[$block][$selector], 'margin-bottom', 'Added by transform plugin');
								}
							}
							$cssp->insert_properties($newproperties, $block, $selector, null, 'transform');
						}
						
						//IE8-compliance (note: value inside apostrophes!)
						//If -ms-filter-property not yet set
						if(!isset($parsed[$block][$selector]['-ms-filter'])){
							$parsed[$block][$selector]['-ms-filter'] = array($filter);
						}
						//If -ms-filter-property already set
						else{
							//Needs its filter-value to be put in first place!
							array_unshift($parsed[$block][$selector]['-ms-filter'],$filter);
						}
						CSSP::comment($parsed[$block][$selector], '-ms-filter', 'Added by transform plugin');
						
						//Legacy IE-compliance
						//If filter-property not yet set
						if(!isset($parsed[$block][$selector]['filter'])){
							$parsed[$block][$selector]['filter'] = array($filter);
						}
						//If filter-property already set
						else{
							//Needs its filter-value to be put in first place!
							array_unshift($parsed[$block][$selector]['filter'],$filter);
						}
						CSSP::comment($parsed[$block][$selector], 'filter', 'Added by transform plugin');

						//Set hasLayout
						$parsed[$block][$selector]['zoom'] = array('1');
						CSSP::comment($parsed[$block][$selector], 'zoom', 'Added by transform plugin');

						//Include behavior to compansate for IEs auto expand feature
						$htc_path = rtrim(dirname($_SERVER['SCRIPT_NAME']),'/').'/plugins/transform/transform.htc';
						$parsed[$block][$selector]['behavior'] = array('url('.$htc_path.')');
						CSSP::comment($parsed[$block][$selector], 'behavior', 'Added by transform plugin');
					}
				}
			}
		}
	}


	/**
	 * Register the plugin, needs be the last one
	 */
	$cssp->register_plugin('before_glue', 9999, 'transform');


?>
