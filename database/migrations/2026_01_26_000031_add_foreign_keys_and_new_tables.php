<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysAndNewTables extends Migration
{
    public function up()
    {
        // Create cars table
        if (! Schema::hasTable('cars')) {
            Schema::create('cars', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('brand_id')->nullable();
                $table->unsignedBigInteger('model_id')->nullable();
                $table->smallInteger('year')->unsigned()->nullable();
                $table->string('color')->nullable();
                $table->string('body_type')->nullable();
                $table->string('fuel_type')->nullable();
                $table->unsignedTinyInteger('owners_count')->nullable();
                $table->decimal('price', 12, 2)->nullable();
                $table->boolean('is_customs_cleared')->default(true);
                $table->string('battery_range')->nullable();
                $table->string('battery_capacity')->nullable();
                $table->string('address')->nullable();
                $table->timestamps();

                $table->index('brand_id');
                $table->index('model_id');
            });
        }

        // Create installments table
        if (! Schema::hasTable('installments')) {
            Schema::create('installments', function (Blueprint $table) {
                $table->id();
                $table->decimal('original_price', 12, 2);
                $table->decimal('fees', 12, 2)->nullable();
                $table->decimal('deposit_amount', 12, 2)->nullable();
                $table->decimal('installment_amount', 12, 2)->nullable();
                $table->unsignedInteger('period_months')->nullable();
                $table->decimal('apr', 8, 4)->nullable();
                $table->timestamps();
            });
        }

        // Add foreign keys safely
        $this->addFkIfPossible('users', 'profile_image_id', 'media', 'id', 'users_profile_image_fk');
        $this->addFkIfPossible('users', 'country_id', 'countries', 'id', 'users_country_fk');
        $this->addFkIfPossible('users', 'city_id', 'cities', 'id', 'users_city_fk');

        $this->addFkIfPossible('user_role', 'user_id', 'users', 'id', 'user_role_user_fk');
        $this->addFkIfPossible('user_role', 'role_id', 'roles', 'id', 'user_role_role_fk');

        $this->addFkIfPossible('media', 'user_id', 'users', 'id', 'media_user_fk');

        $this->addFkIfPossible('models', 'brand_id', 'brands', 'id', 'models_brand_fk');

        $this->addFkIfPossible('ads', 'user_id', 'users', 'id', 'ads_user_fk');
        $this->addFkIfPossible('ads', 'banner_image_id', 'media', 'id', 'ads_banner_media_fk');
        $this->addFkIfPossible('ads', 'brand_id', 'brands', 'id', 'ads_brand_fk');
        $this->addFkIfPossible('ads', 'model_id', 'models', 'id', 'ads_model_fk');
        $this->addFkIfPossible('ads', 'city_id', 'cities', 'id', 'ads_city_fk');
        $this->addFkIfPossible('ads', 'country_id', 'countries', 'id', 'ads_country_fk');

        $this->addFkIfPossible('ad_media', 'ad_id', 'ads', 'id', 'ad_media_ad_fk');
        $this->addFkIfPossible('ad_media', 'media_id', 'media', 'id', 'ad_media_media_fk');

        $this->addFkIfPossible('user_packages', 'user_id', 'users', 'id', 'user_packages_user_fk');
        $this->addFkIfPossible('user_packages', 'package_id', 'packages', 'id', 'user_packages_package_fk');

        $this->addFkIfPossible('offers', 'ad_id', 'ads', 'id', 'offers_ad_fk');
        $this->addFkIfPossible('offers', 'user_id', 'users', 'id', 'offers_user_fk');

        $this->addFkIfPossible('caishha_offers', 'ad_id', 'ads', 'id', 'caishha_ad_fk');
        $this->addFkIfPossible('caishha_offers', 'user_id', 'users', 'id', 'caishha_user_fk');

        $this->addFkIfPossible('findit_requests', 'requester_id', 'users', 'id', 'findit_requester_fk');

        $this->addFkIfPossible('notifications', 'user_id', 'users', 'id', 'notifications_user_fk');

        $this->addFkIfPossible('reviews', 'user_id', 'users', 'id', 'reviews_user_fk');
        $this->addFkIfPossible('reviews', 'seller_id', 'users', 'id', 'reviews_seller_fk');
        $this->addFkIfPossible('reviews', 'ad_id', 'ads', 'id', 'reviews_ad_fk');

        $this->addFkIfPossible('reports', 'reported_by_user_id', 'users', 'id', 'reports_reporter_fk');

        $this->addFkIfPossible('auctions', 'ad_id', 'ads', 'id', 'auctions_ad_fk');
        $this->addFkIfPossible('auctions', 'winner_user_id', 'users', 'id', 'auctions_winner_fk');

        $this->addFkIfPossible('bids', 'auction_id', 'auctions', 'id', 'bids_auction_fk');
        $this->addFkIfPossible('bids', 'user_id', 'users', 'id', 'bids_user_fk');

        $this->addFkIfPossible('favorites', 'user_id', 'users', 'id', 'favorites_user_fk');
        $this->addFkIfPossible('favorites', 'ad_id', 'ads', 'id', 'favorites_ad_fk');
    }

    public function down()
    {
        // No-op: removing foreign keys may be risky in rollback; handle manually if required.
    }

    protected function addFkIfPossible($table, $column, $foreignTable, $foreignColumn, $fkName)
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable($foreignTable)) {
            return;
        }

        // If the column does not exist, skip
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        // Attempt to add FK if not already present
        try {
            Schema::table($table, function (Blueprint $t) use ($column, $foreignTable, $foreignColumn, $fkName) {
                // Avoid duplicate FK names in some DBs by checking existing indexes not available easily here.
                $t->foreign($column, $fkName)->references($foreignColumn)->on($foreignTable)->onUpdate('cascade')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // ignore errors (e.g., FK already exists or incompatible types); log in storage/logs if desired
        }
    }
}
