<?php
/*
 * @creator           : Gordon Lim <honwei189@gmail.com>
 * @created           : 05/05/2019 17:45:39
 * @last modified     : 06/06/2020 15:27:51
 * @last modified by  : Gordon Lim <honwei189@gmail.com>
 */

namespace honwei189\Flayer;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

/**
 *
 * layer service provider (for Laravel)
 *
 *
 * @package     Flayer
 * @subpackage
 * @author      Gordon Lim <honwei189@gmail.com>
 * @link        https://github.com/honwei189/flayer/
 * @version     "1.0.0"
 * @since       "1.0.0"
 */
class FlayerServiceProvider extends ServiceProvider
{
    /**
     * Register service
     *
     * @return void
     */
    public function register()
    {
        include_once "Helpers.php";
        //$this->app->bind(fdo::class);

        $this->app->booting(function() {
            $loader = AliasLoader::getInstance();
            $loader->alias('config', Config::class);
            $loader->alias('container', Container::class);
            $loader->alias('crypto', Crypto::class);
            $loader->alias('data', Data::class);
            $loader->alias('Flayer', Flayer::class);
            $loader->alias('http', Http::class);
            config::load("flayer");
        });

        $this->app->singleton(Flayer::class, function ($app) {
            return new Flayer;
        });
    }

    /**
     * Load service on start-up
     *
     * @return void
     */
    public function boot()
    {
        $this->app->singleton('Flayer', function () {
            return new Flayer;
        });
    }

    public function provides()
    {
        return [Flayer::class];
    }
}
