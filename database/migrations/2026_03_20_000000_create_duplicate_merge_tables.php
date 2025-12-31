<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duplicate_groups', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->string('match_key', 50);
            $table->string('match_value', 255);
            $table->timestamps();

            $table->unique(['entity_type', 'match_key', 'match_value'], 'dup_groups_entity_key_value_unique');
        });

        Schema::create('duplicate_group_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('duplicate_group_id')->constrained('duplicate_groups')->cascadeOnDelete();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('entity_id');
            $table->timestamps();

            $table->unique(['duplicate_group_id', 'entity_id'], 'dup_group_items_group_entity_unique');
            $table->index(['entity_type', 'entity_id'], 'dup_group_items_entity_index');
        });

        Schema::create('entity_merges', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 50);
            $table->unsignedBigInteger('winner_id');
            $table->unsignedBigInteger('loser_id');
            $table->json('field_resolution')->nullable();
            $table->json('relations_moved')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['entity_type', 'winner_id'], 'entity_merges_winner_index');
            $table->index(['entity_type', 'loser_id'], 'entity_merges_loser_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_merges');
        Schema::dropIfExists('duplicate_group_items');
        Schema::dropIfExists('duplicate_groups');
    }
};
