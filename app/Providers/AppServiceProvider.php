<?php

namespace App\Providers;

use App\Models\FinditMatch;
use App\Models\FinditRequest;
use App\Models\Review;
use App\Models\Report;
use App\Models\Package;
use App\Observers\ReviewObserver;
use App\Policies\FinditRequestPolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ReportPolicy;
use App\Policies\PackagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Map short morph types to model classes so polymorphic relations resolve correctly
        Relation::morphMap([
            'ad' => \App\Models\Ad::class,
            'user' => \App\Models\User::class,
            'dealer' => \App\Models\User::class,
            'seller' => \App\Models\User::class,
        ]);

        // Register policies
        Gate::policy(FinditRequest::class, FinditRequestPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(Package::class, PackagePolicy::class);

        // Explicit route model binding for FinditMatch
        Route::model('match', FinditMatch::class);

        // Register observers
        Review::observe(ReviewObserver::class);

        // Configure rate limiters for reviews and reports
        RateLimiter::for('review', function ($request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('report', function ($request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
