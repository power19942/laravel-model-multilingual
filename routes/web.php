<?php

use App\Article;
use App\Artist;
use App\ArtistTranslation;
use App\Product;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath', 'localize']
], function () {
    /** ADD ALL LOCALIZED ROUTES INSIDE THIS GROUP **/
    Route::get('/', function () {
        return LaravelLocalization::getSupportedLanguagesKeys();
    });

    Route::get('create', function () {
        Artist::find(4)->translations()->saveMany([
            new ArtistTranslation([
                'locale' => 'ar',
                'name' => 'خالد'
            ]),
            new ArtistTranslation([
                'locale' => 'en',
                'name' => 'khaled'
            ])
        ]);
        return 'ok';
    });

    Route::get('test', function () {
        // $ar = new Artist();
        // $ar->name='omar';
        // $ar->save();
        // return 'ok';
        return Artist::all();
    });

    Route::get(LaravelLocalization::transRoute('routes.about'), function () {
        return 'about';
    });


    Route::get('toriom', function () {
        $product = Product::create([
            'name' => 'iphone',
            'price' => 1000
        ]);

        $product->fillTranslation([
            App::getLocale() => [
                'name' => 'اي فون'
            ]

        ]);
        return [App::getLocale() => [
            'name' => 'اي فون'
        ]];
        return app()->getLocale();
    });

    Route::get('toriom/get', function () {
        // return Product::WithTranslations()->get();
        $product = Product::all();
        return Product::translate($product);
    });
});
