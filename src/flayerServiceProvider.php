<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 05/05/2019 17:45:39
 * @last modified     : 23/12/2019 21:56:59
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

/**
 *
 * layer service provider (for Laravel)
 *
 *
 * @package     flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/html/
 * @link        https://appsw.dev
 * @link        https://justtest.app
 * @version     "1.0.0" 
 * @since       "1.0.0" 
 */
class flayerServiceProvider extends ServiceProvider
{
    /**
     * Register service
     *
     * @return void
     */
    public function register()
    {
        include_once "libs/utilities.php";
        //$this->app->bind(fdo::class);

        $this->app->booting(function() {
            $loader = AliasLoader::getInstance();
            $loader->alias('config', config::class);
            $loader->alias('container', container::class);
            $loader->alias('crypto', crypto::class);
            $loader->alias('data', data::class);
            $loader->alias('flayer', flayer::class);
            $loader->alias('http', http::class);
            config::load("flayer");
        });
    }

    /**
     * Load service on start-up
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('flayer', function () {
            return new flayer;
        });
    }

    public function provides()
    {
        // return ['flayer'];
    }
}
