<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = App\Models\User::where('username', 'wali-paud-240289')->first();
auth()->login($user);

$request = Illuminate\Http\Request::create('/logout', 'GET');
$response = app()->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Location: " . $response->headers->get('Location') . "\n";
echo "Content: " . substr($response->getContent(), 0, 100) . "\n";
