<?php namespace Appointment\Patients;

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
    \View::addNamespace('appointment.patients', base_path('plugins/appointment/patients/mail'));
}



    public function register()
{
    // Регистрация пути для шаблонов email
    \App::singleton('mailer', function ($app) {
        $mailer = new \Illuminate\Mail\Mailer(
            $app['view'],
            $app['swift.mailer'],
            $app['events']
        );

        $mailer->setLogger($app['log']);
        $mailer->setQueue($app['queue']);

        $mailer->getViewFactory()->addNamespace('appointment.patients', base_path() . '/plugins/appointment/patients/mail');

        return $mailer;
    });
}

}
