<?php

return [
    'transfer_enabled' => (bool) env('PAYMENTS_TRANSFER_ENABLED', true),
    'transfer_proof_upload_enabled' => (bool) env('PAYMENTS_TRANSFER_PROOF_UPLOAD_ENABLED', false),
];
