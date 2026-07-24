<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('username', 'wali-paud-240289')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}
auth()->login($user);

$request = Illuminate\Http\Request::create('/login', 'GET');
$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
