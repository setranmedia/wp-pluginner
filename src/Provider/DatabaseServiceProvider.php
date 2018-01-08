<?php

namespace SetranMedia\WpPluginner\Provider;

use SetranMedia\WpPluginner\Foundation\ServiceProvider;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;

if ( ! defined( 'ABSPATH' ) ) exit;

class DatabaseServiceProvider extends ServiceProvider {
    public function register()
    {
        global $wpdb;
        $capsule = new Capsule;
        $capsule->addConnection([
            'driver'    => 'mysql',
            'host'      => DB_HOST,
            'database'  => DB_NAME,
            'username'  => DB_USER,
            'password'  => DB_PASSWORD,
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => $wpdb->prefix,
        ]);
        $capsule->setEventDispatcher(new Dispatcher($this->plugin));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        $this->plugin->singleton('db', function () use ($capsule) {
            return $capsule->getDatabaseManager();
        });
    }

}
