<?php
use Illuminate\Support\ServiceProvider;

class QueryBuilderServiceProvider extends ServiceProvider
{
    function boot() {

    }
    function register()
    {
        parent::register(); // TODO: Change the autogenerated stub
    }
    function provides()
    {
        return [
            QueryBuilderRequest::class
        ];
    }
}