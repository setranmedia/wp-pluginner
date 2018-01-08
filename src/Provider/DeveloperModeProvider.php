<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;
use SetranMedia\WpPluginner\Controller\DevController;

class DeveloperModeProvider extends ServiceProvider {

	public function register(){

		$this->plugin['view']->addLocation(realpath(__DIR__ . '/../resource/view/'));

		$namespace = $this->plugin['config']->get('plugin.namespace');
		$slug = str_slug($namespace . '_dev');

		if (is_admin()) {
			//Setup Admin Panel
			$this->plugin['plugin.admin']->addMenu([
				'page_title' => $namespace,
				'menu_title' => $namespace,
				'capability' => 'manage_options', //$capability
				'menu_slug' => $slug, //$menu_slug
				'controller' => 'SetranMedia\WpPluginner\Controller\DevController@show'
			]);
		}
		$this->plugin['plugin.admin']->addBarNode(
			$slug,
			$namespace,
			null,
			'?page=' . $slug . '&tab=config',
			null,
			array('class' => 'fooBar'),
			100
		);
		if ($this->plugin['config']->get('plugin.cache_enabled')) {
			$this->plugin['plugin.admin']->addBarNode(
				$slug . '_flush_objects',
				'Flush Objects',
				$slug,
				'#',
				null,
				array(
					'class' => $slug . '_flush_objects'
				)
			);
		}
		if ($this->plugin['config']->get('plugin.session_enabled')) {
			$this->plugin['plugin.admin']->addBarNode(
				$slug . '_flush_sessions',
				'Flush Sessions',
				$slug,
				'#',
				null,
				array(
					'class' => $slug . '_flush_sessions'
				)
			);
		}
		if ($this->plugin['config']->get('view.compiled')) {
			$this->plugin['plugin.admin']->addBarNode(
				$slug . '_flush_views',
				'Flush Views',
				$slug,
				'#',
				null,
				array(
					'class' => $slug . '_flush_views'
				)
			);
		}
		$this->plugin['plugin.admin']->addBarNode(
			$slug . '_config',
			'Configuration',
			$slug,
			admin_url('admin.php?page=' . $slug . '&tab=config')
		);


		//Add Framework Cache Ajax Script to Footer
		add_action('wp_footer', function () use ($namespace, $slug){
			echo $this->plugin['view']->make('admin.framework.scripts', array('slug' => $slug));
		}, 99);
		add_action('admin_footer', function () use ($namespace, $slug) {
			echo $this->plugin['view']->make('admin.framework.scripts', array('slug' => $slug));
		}, 99);
		//Add Framework Cache Ajax Controller
		add_action("wp_ajax_" . $slug, function () {
			$controller = new DevController($this->plugin);
			return $controller->show();
		});
	}
}
