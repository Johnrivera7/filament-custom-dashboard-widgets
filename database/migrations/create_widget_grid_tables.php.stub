<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('widget_grid_layouts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('panel_id')->default('admin')->index();
            $table->boolean('is_default')->default(false);
            $table->json('items');
            $table->timestamps();

            $table->index(['user_id', 'panel_id', 'is_default'], 'widget_grid_layouts_user_panel_default_index');
        });

        Schema::create('widget_grid_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('panel_id')->default('admin')->index();
            $table->string('name');
            $table->json('items');
            $table->boolean('is_shared')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('widget_grid_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('panel_id')->default('admin');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['panel_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('widget_grid_settings');
        Schema::dropIfExists('widget_grid_templates');
        Schema::dropIfExists('widget_grid_layouts');
    }
};
