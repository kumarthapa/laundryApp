<?php

namespace App\Http\Controllers\Api;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\products\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardApiController extends Controller
{
    public function summary(Request $request)
    {
        $cacheSeconds = (int) $request->query('cache_seconds', 5);

        $cacheKey = 'dashboard.v4';

        $data = Cache::remember($cacheKey, $cacheSeconds, function () {

            /* ---------------------------------------------
             * TOTAL PRODUCTS
             * --------------------------------------------- */
            $productQuery = Product::query();
            $productQuery = LocaleHelper::commonWhereLocationCheck($productQuery, 'products');
            $totalProducts = $productQuery->count();

            /* ---------------------------------------------
             * TOTAL TAGS + MAPPED + UNMAPPED
             * --------------------------------------------- */
            $tagQuery = DB::table('rfid_tags as r');
            $tagQuery = LocaleHelper::commonWhereLocationCheck($tagQuery, 'r');

            $totalTags = (clone $tagQuery)->count();
            $mappedTags = (clone $tagQuery)->whereNotNull('product_id')->count();
            $unmappedTags = $totalTags - $mappedTags;

            /* ---------------------------------------------
             * INVENTORY ACTIVITY: INWARD + OUTWARD
             * --------------------------------------------- */
            $activityQuery = DB::table('inventory_activity as a')
                ->leftJoin('products as p', 'p.id', '=', 'a.product_id');

            $activityQuery = LocaleHelper::commonWhereLocationCheck($activityQuery, 'p');

            $totalInward = (clone $activityQuery)->sum('inward');
            $totalOutward = (clone $activityQuery)->sum('outward');

            /* ---------------------------------------------
             * RETURN DATA FOR APP
             * --------------------------------------------- */
            return [
                'kpis' => [
                    'total_tags' => $totalTags,
                    'total_products' => $totalProducts,
                    'total_tags_mapped' => $mappedTags,
                    'total_tags_unmapped' => $unmappedTags,
                    'total_inward' => (int) $totalInward,
                    'total_outward' => (int) $totalOutward,
                ],

                // app may expect stages key
                'stages' => [],
                'recent_activities' => [], // now intentionally empty
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard summary data.',
            'data' => $data,
        ]);
    }
}
