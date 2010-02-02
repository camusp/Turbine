<?php


	/**
	 * Implements a reset stylesheet
	 * 
	 * Usage: Simply include the plugin in @cssp
	 * Status: Stable
	 * 
	 * @param mixed &$parsed
	 * @return void
	 */
	function resetstyle(&$parsed){
		// The reset stylesheet
		$reset_stylesheet = "*\n	margin:0;padding:0;text-decoration:none;border:0;outline:0;font-weight:normal;font-style:inherit;font-size:100%;font-family:inherit;vertical-align:baseline;line-height:inherit;color:inherit;background:none;text-align:inherit;quotes:'''';list-style:none;border-collapse:collapse;border-spacing:0;outline:none";
		// Parse the stylesheet
		$cssp = new Cssp();
		$cssp->load_string($reset_stylesheet);
		$cssp->parse();
		// Merge the reset array into the existing $parsed
		$parsed['global'] = array_merge($cssp->parsed['global'], $parsed['global']);
	}


	/**
	 * Register the plugin
	 */
	register_plugin('before_compile', 0, 'resetstyle');


?>