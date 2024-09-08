<?php namespace Appointment\Services;

use System\Classes\PluginBase;

class Plugin extends PluginBase
{
    public function registerComponents()
    {
    }

    public function registerSettings()
    {
    }

    public function boot()
{
    \Backend\Classes\Controller::extend(function($controller) {
        $controller->addCss('https://use.fontawesome.com/releases/v5.12.0/css/all.css');
    });
}
}


