<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\user_management\Modules;

class MenuServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $menuData = [];

        try {
            // Check if model exists and DB is ready
            if (class_exists(Modules::class)) {
                $menuModules = Modules::where('is_active', 1)
                    ->where('is_menu', 1)
                    ->orderBy('parent_module_id')
                    ->orderBy('module_id')
                    ->get();

                // Normalize parent_module_id: convert empty or null to 0
                $menuModules = $menuModules->map(function ($module) {
                    if ($module->parent_module_id === null || $module->parent_module_id === '') {
                        $module->parent_module_id = 0;
                    }
                    return $module;
                });

                // Build the hierarchical tree
                $menuData = $this->buildMenuTree($menuModules);
            }
        } catch (\Exception $e) {
            // If table not found or DB not ready, fallback to empty menu
            $menuData = [];
        }

        // Share 'menuData' with all views globally
        View::share('menuData', $menuData);
    }

    /**
     * Build hierarchical tree from flat modules collection
     *
     * @param \Illuminate\Support\Collection $modules
     * @return array
     */
    protected function buildMenuTree($modules)
    {
        $grouped = $modules->groupBy('parent_module_id');

        $build = function ($parentId) use (&$build, $grouped) {
            $tree = [];

            if (isset($grouped[$parentId])) {
                $sortedModules = $grouped[$parentId]->sortBy(function ($module) {
                    // Treat 0 or missing sort as max to push at the end
                    if (!is_numeric($module->sort) || $module->sort == 0) {
                        return PHP_INT_MAX;
                    }
                    return (int) $module->sort;
                });

                foreach ($sortedModules as $module) {
                    $children = $build($module->module_id);
                    $item = $module->toArray();
                    if (!empty($children)) {
                        $item['submenu'] = $children;
                    }
                    $tree[] = (object) $item;
                }
            }

            return $tree;
        };

        return $build(0);
    }
}
