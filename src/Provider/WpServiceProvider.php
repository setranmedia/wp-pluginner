<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;

use SetranMedia\WpPluginner\Factory\AdminFactory;
use SetranMedia\WpPluginner\Factories\ShortcodeFactory;
use SetranMedia\WpPluginner\Factories\WidgetFactory;

class WpServiceProvider extends ServiceProvider {

	public function register(){
		//Bind WpShortcode Class
		if (!$this->plugin->bound('plugin.shortcodes')) {
			$this->plugin->singleton('plugin.shortcodes', function () {
				return new ShortcodeFactory($this->plugin);
			});
		}
		//Bind WpWidget Class
		if (!$this->plugin->bound('plugin.widgets')) {
			$this->plugin->singleton('plugin.widgets', function () {
				return new WidgetFactory($this->plugin);
			});
		}
		//Bind WpAdmin Class
		if (!$this->app->bound('plugin.admin')) {
			$this->app->singleton('plugin.admin', function () {
				return new AdminFactory($this->plugin);
			});
		}
		//Loop Wordpress Directories
		foreach (array('shortcode', 'action', 'filter', 'hook', 'widget', 'menu') as $directoryName) {
			//Require All Php Files
			$files = $this->plugin['files']->glob($this->plugin->wp_property_path . "/{$directoryName}/*.php");

			foreach ($files as $file) {
				if (
					$directoryName == 'hook' &&
					in_array(basename($file, '.php'), ['activation','deactivation'])
				) {
					continue;
				}
				require_once $file;
			}
		}
	}
}
