<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SplitAdsIntoTypeTables extends Migration
{
    public function up()
    {
        // Create normal_ads table
        if (! Schema::hasTable('normal_ads')) {
            Schema::create('normal_ads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ad_id')->unique();
                $table->decimal('price_cash', 12, 2)->nullable();
                $table->unsignedBigInteger('installment_id')->nullable();
                $table->dateTime('start_time')->nullable();
                $table->dateTime('update_time')->nullable();
                $table->timestamps();

                $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
                if (Schema::hasTable('installments')) {
                    try {
                        $table->foreign('installment_id')->references('id')->on('installments')->onDelete('set null');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            });
        }

        // Create unique_ads table
        if (! Schema::hasTable('unique_ads')) {
            Schema::create('unique_ads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ad_id')->unique();
                $table->unsignedBigInteger('banner_image_id')->nullable();
                $table->string('banner_color', 30)->nullable();
                $table->boolean('is_auto_republished')->default(false);
                $table->boolean('is_verified_ad')->default(false);
                $table->timestamps();

                $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
                // banner_image_id -> media
                if (Schema::hasTable('media')) {
                    try {
                        $table->foreign('banner_image_id')->references('id')->on('media')->onDelete('set null');
                    } catch (\Exception $e) {
                        // ignore
                    }
                }
            });
        }

        // Create caishha_ads table
        if (! Schema::hasTable('caishha_ads')) {
            Schema::create('caishha_ads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ad_id')->unique();
                $table->unsignedInteger('offers_window_period')->nullable();
                $table->unsignedInteger('offers_count')->nullable();
                $table->unsignedInteger('sellers_visibility_period')->nullable();
                $table->timestamps();

                $table->foreign('ad_id')->references('id')->on('ads')->onDelete('cascade');
            });
        }

        // Migrate data from existing ads into type tables
        // Note: operate in batches if dataset is large; here we do row-by-row for clarity.
        $ads = DB::table('ads')->select('*')->get();
        foreach ($ads as $a) {
            $adId = $a->id;
            $type = $a->type ?? 'normal';

            if ($type === 'normal') {
                $exists = DB::table('normal_ads')->where('ad_id', $adId)->exists();
                if (! $exists) {
                    DB::table('normal_ads')->insert([
                        'ad_id' => $adId,
                        'price_cash' => $a->price_cash ?? null,
                        'installment_id' => $a->installment_id ?? null,
                        'start_time' => $a->start_time ?? null,
                        'update_time' => $a->update_time ?? null,
                        'created_at' => $a->created_at ?? now(),
                        'updated_at' => $a->updated_at ?? now(),
                    ]);
                }
            }

            if ($type === 'unique') {
                $exists = DB::table('unique_ads')->where('ad_id', $adId)->exists();
                if (! $exists) {
                    DB::table('unique_ads')->insert([
                        'ad_id' => $adId,
                        'banner_image_id' => $a->banner_image_id ?? null,
                        'banner_color' => $a->banner_color ?? null,
                        'is_auto_republished' => $a->is_auto_republished ?? 0,
                        'is_verified_ad' => $a->is_verified_ad ?? 0,
                        'created_at' => $a->created_at ?? now(),
                        'updated_at' => $a->updated_at ?? now(),
                    ]);
                }
            }

            if ($type === 'caishha') {
                $exists = DB::table('caishha_ads')->where('ad_id', $adId)->exists();
                if (! $exists) {
                    DB::table('caishha_ads')->insert([
                        'ad_id' => $adId,
                        'offers_window_period' => $a->offers_window_period ?? null,
                        'offers_count' => $a->offers_count ?? null,
                        'sellers_visibility_period' => $a->sellers_visibility_period ?? null,
                        'created_at' => $a->created_at ?? now(),
                        'updated_at' => $a->updated_at ?? now(),
                    ]);
                }
            }
        }

        // Remove moved columns from ads table where present - drop foreign keys first using direct ALTER statements
        $cols = ['price_cash', 'installment_id', 'start_time', 'update_time', 'banner_image_id', 'banner_color', 'is_auto_republished', 'offers_window_period', 'offers_count', 'sellers_visibility_period'];
        foreach ($cols as $c) {
            if (! Schema::hasColumn('ads', $c)) {
                continue;
            }

            // Query INFORMATION_SCHEMA for any foreign key constraints on this column and drop them
            try {
                $rows = DB::select(
                    'SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
                    ['ads', $c]
                );
                foreach ($rows as $r) {
                    $fkName = $r->CONSTRAINT_NAME ?? null;
                    if ($fkName) {
                        try {
                            DB::statement("ALTER TABLE `ads` DROP FOREIGN KEY `{$fkName}`");
                        } catch (\Exception $ex) {
                            // ignore individual failures
                        }
                    }
                }
            } catch (\Exception $ex) {
                // ignore if cannot query
            }
        }

        // Now drop the columns
        // SQLite (in-memory) does not support dropping columns via ALTER; skip in tests
        try {
            if (DB::getDriverName() === 'sqlite') {
                return;
            }
        } catch (\Exception $e) {
            // If unable to detect driver, proceed cautiously and attempt drop
        }

        Schema::table('ads', function (Blueprint $table) use ($cols) {
            foreach ($cols as $c) {
                if (Schema::hasColumn('ads', $c)) {
                    try {
                        $table->dropColumn($c);
                    } catch (\Exception $e) {
                        // ignore if cannot drop here
                    }
                }
            }
        });
    }

    public function down()
    {
        // Recreate moved columns on ads if missing (best-effort)
        if (Schema::hasTable('ads')) {
            Schema::table('ads', function (Blueprint $table) {
                if (! Schema::hasColumn('ads', 'price_cash')) {
                    $table->decimal('price_cash', 12, 2)->nullable();
                }
                if (! Schema::hasColumn('ads', 'installment_id')) {
                    $table->unsignedBigInteger('installment_id')->nullable();
                }
                if (! Schema::hasColumn('ads', 'start_time')) {
                    $table->dateTime('start_time')->nullable();
                }
                if (! Schema::hasColumn('ads', 'update_time')) {
                    $table->dateTime('update_time')->nullable();
                }
                if (! Schema::hasColumn('ads', 'banner_image_id')) {
                    $table->unsignedBigInteger('banner_image_id')->nullable();
                }
                if (! Schema::hasColumn('ads', 'banner_color')) {
                    $table->string('banner_color', 30)->nullable();
                }
                if (! Schema::hasColumn('ads', 'is_auto_republished')) {
                    $table->boolean('is_auto_republished')->default(false);
                }
                if (! Schema::hasColumn('ads', 'offers_window_period')) {
                    $table->unsignedInteger('offers_window_period')->nullable();
                }
                if (! Schema::hasColumn('ads', 'offers_count')) {
                    $table->unsignedInteger('offers_count')->nullable();
                }
                if (! Schema::hasColumn('ads', 'sellers_visibility_period')) {
                    $table->unsignedInteger('sellers_visibility_period')->nullable();
                }
            });
        }

        // Drop the new type tables
        Schema::dropIfExists('caishha_ads');
        Schema::dropIfExists('unique_ads');
        Schema::dropIfExists('normal_ads');
    }
}
