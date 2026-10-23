<?php

namespace QdrantLabs\LaravelScout;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class QdrantServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/scout-qdrant.php', 'scout-qdrant');

        $this->app->singleton(QdrantClient::class, function ($app) {
            return new QdrantClient(
                $app->make(HttpFactory::class),
                $app['config']->get('scout-qdrant', []),
            );
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->callAfterResolving(EngineManager::class, function (EngineManager $manager) {
            $manager->extend('qdrant', function ($app) {
                return new QdrantEngine(
                    $app->make(QdrantClient::class),
                    $app['config']->get('scout-qdrant', []),
                    $app['config']->get('scout.soft_delete', false),
                );
            });
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/scout-qdrant.php' => $this->app->configPath('scout-qdrant.php'),
            ], 'scout-qdrant-config');
        }
    }
}
