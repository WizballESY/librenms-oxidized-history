<?php

namespace WizballEsy\LibreNmsOxidizedHistory;

use App\View\Components\Device\PageTabs;
use Illuminate\Support\ServiceProvider;
use WizballEsy\LibreNmsOxidizedHistory\Contracts\HistoryProvider;
use WizballEsy\LibreNmsOxidizedHistory\Http\Controllers\HistoricalConfigTabController;
use WizballEsy\LibreNmsOxidizedHistory\Services\LocalGitHistoryProvider;

class LibreNmsOxidizedHistoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/oxidized-history.php', 'oxidized-history');

        $this->app->singleton(HistoryProvider::class, LocalGitHistoryProvider::class);
    }

    public function boot(): void
    {
        $viewPath = __DIR__ . '/../resources/views';

        $this->loadViewsFrom($viewPath, 'librenms-oxidized-history');

        /*
         * LibreNMS DeviceController renders device tab views by looking for:
         *   view('device.tabs.<tab-slug>')
         *
         * Because that is not namespaced, we prepend this package view path so
         * resources/views/device/tabs/historical-config.blade.php can be found
         * without modifying LibreNMS core views.
         */
        if ($this->app->bound('view')) {
            $this->app['view']->getFinder()->prependLocation($viewPath);
        }

        if (class_exists(PageTabs::class)) {
            $this->insertDeviceTabAfter(
                'showconfig',
                'historical-config',
                HistoricalConfigTabController::class
            );
        }
    }

    private function insertDeviceTabAfter(string $afterSlug, string $slug, string $controllerClass): void
    {
        $tabs = PageTabs::$tabsClasses;
        $newTabs = [];

        foreach ($tabs as $key => $class) {
            $newTabs[$key] = $class;

            if ($key === $afterSlug) {
                $newTabs[$slug] = $controllerClass;
            }
        }

        if (! isset($newTabs[$slug])) {
            $newTabs[$slug] = $controllerClass;
        }

        PageTabs::$tabsClasses = $newTabs;
    }

}
