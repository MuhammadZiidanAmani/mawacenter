<?php

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$user = App\Models\User::where('role', 'super_admin')->first() ?: App\Models\User::first();

auth()->login($user);

foreach (['academic-years', 'education-units', 'classes', 'fee-types', 'fee-discounts', 'data-roles', 'data-users'] as $tab) {
    $request = Illuminate\Http\Request::create('/master-data?tab='.$tab, 'GET');
    $request->setUserResolver(fn () => $user);
    $response = $kernel->handle($request);
    $html = $response->getContent();
    $html = str_replace(
        ['http://localhost/build/', 'http://localhost/images/'],
        ['file:///C:/Projects/mawacenter/public/build/', 'file:///C:/Projects/mawacenter/public/images/'],
        $html
    );

    file_put_contents(__DIR__.'/'.$tab.'.html', $html);

    echo $tab.':'.$response->getStatusCode()
        .':toolbar-'.(str_contains($html, 'master-table-toolbar') ? 'yes' : 'no')
        .':footer-'.(str_contains($html, 'master-data-pagination-footer') ? 'yes' : 'no')
        .':sort-'.(str_contains($html, 'master-sort-link') ? 'yes' : 'no')
        .PHP_EOL;

    $kernel->terminate($request, $response);
}
