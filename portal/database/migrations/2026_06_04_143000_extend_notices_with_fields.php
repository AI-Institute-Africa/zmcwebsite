<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Columns were already added in a partial migration run;
        // this marks the migration as applied without changing the table.
    }

    public function down(): void
    {
        // No reverse migration needed for this no-op.
    }
};
