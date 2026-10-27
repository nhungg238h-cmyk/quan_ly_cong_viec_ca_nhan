<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Không ràng buộc khóa ngoại: lịch sử vẫn giữ sau khi công việc bị xóa.
            $table->unsignedBigInteger('task_id')->nullable();
            $table->string('task_title', 255);
            $table->string('action', 20);
            $table->timestamp('created_at')->nullable();

            $table->index(['user_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_activities');
    }
};
