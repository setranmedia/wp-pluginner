<?php

namespace SetranMedia\WpPluginner\Model;

use SetranMedia\WpPluginner\Support\ServiceProvider;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use Exception;

if ( ! defined( 'ABSPATH' ) ) exit;

class ModelServiceProvider extends ServiceProvider {
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
        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }

}
