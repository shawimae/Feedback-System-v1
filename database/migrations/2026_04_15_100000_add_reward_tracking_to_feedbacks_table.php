<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->string('reward_status')->default('not_eligible')->after('admin_replied_at');
            $table->unsignedInteger('reward_points_pending')->default(0)->after('reward_status');
            $table->unsignedInteger('reward_points_awarded')->default(0)->after('reward_points_pending');
            $table->string('review_claim_name')->nullable()->after('reward_points_awarded');
            $table->text('review_claim_notes')->nullable()->after('review_claim_name');
            $table->timestamp('review_claimed_at')->nullable()->after('review_claim_notes');
            $table->timestamp('reward_approved_at')->nullable()->after('review_claimed_at');
            $table->timestamp('reward_notified_at')->nullable()->after('reward_approved_at');
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn([
                'reward_status',
                'reward_points_pending',
                'reward_points_awarded',
                'review_claim_name',
                'review_claim_notes',
                'review_claimed_at',
                'reward_approved_at',
                'reward_notified_at',
            ]);
        });
    }
};
