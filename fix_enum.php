<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    DB::statement("ALTER TABLE requisitions MODIFY hec_financial_decision ENUM('no_financial_implication','proposed_funding','reject','no_objection','objection','no_objection_cfo_ceo') NULL");
    echo "SUCCESS: hec_financial_decision enum updated!\n";

    // Verify the change
    $column = DB::select("SHOW COLUMNS FROM requisitions WHERE Field = 'hec_financial_decision'");
    if (!empty($column)) {
        echo "Column type is now: " . $column[0]->Type . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
