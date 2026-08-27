<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->boolean('is_resolved')->default(false)->after('overall_comment');
            $table->timestamp('resolved_at')->nullable()->after('is_resolved');
        });

        DB::table('feedbacks')
            ->whereNotNull('admin_reply')
            ->update([
                'is_resolved' => true,
                'resolved_at' => DB::raw('COALESCE(admin_replied_at, updated_at, created_at)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropColumn(['is_resolved', 'resolved_at']);
        });
    }
};
