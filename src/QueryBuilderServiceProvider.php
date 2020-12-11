<?php
namespace Ningwei\QueryBuilder;
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    function boot() {

    }
    function register()
    {
        $this->app->bind(QueryBuilderRequest::class, function ($app) {
            return QueryBuilderRequest::fromRequest($app['request']);
        });
    }
    function provides()
    {
        return [
            QueryBuilderRequest::class
        ];
    }
}