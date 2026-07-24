<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(App\Models\User::all() as $u) {
    echo $u->username . ' : ' . $u->role . PHP_EOL;
}
