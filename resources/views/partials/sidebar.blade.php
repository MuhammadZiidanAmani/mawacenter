@php
    $activeMenu = $activeMenu ?? '';
    $activeStudentMenu = $activeStudentMenu ?? '';
    $activeMasterMenu = $activeMasterMenu ?? '';
    $activePaymentMenu = $activePaymentMenu ?? '';
    $activeReportMenu = $activeReportMenu ?? '';
    $sidebarSvg = fn ($path, $class = '') => '<svg class="icon '.$class.'" viewBox="0 0 24 24" aria-hidden="true">'.$path.'</svg>';
    $sidebarIcons = [
        'grid' => '<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/>',
        'database' => '<ellipse cx="12" cy="5" rx="7" ry="3"/><path d="M5 5v6c0 1.7 3.1 3 7 3s7-1.3 7-3V5M5 11v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/>',
        'receipt' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
        'wallet' => '<path d="M4 6h16v14H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h13v2"/><path d="M15 11h7v5h-7a2.5 2.5 0 0 1 0-5Z"/>',
        'card' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h2"/>',
        'chart' => '<path d="M4 20V10m6 10V4m6 16v-7m4 7H2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M19 5l-2 2M7 17l-2 2"/>',
        'role' => '<path d="M12 3 5 6v5c0 4.5 3 8.1 7 10 4-1.9 7-5.5 7-10V6l-7-3Z"/><path d="M9 12l2 2 4-5"/>',
        'chevron' => '<path d="m9 18 6-6-6-6"/>',
        'arrow-up' => '<path d="M12 19V5m0 0-5 5m5-5 5 5"/>',
        'switch' => '<path d="M7 7h11m0 0-4-4m4 4-4 4M17 17H6m0 0 4-4m-4 4 4 4"/>',
    ];
    $sidebarIcon = fn ($name, $class = '') => $sidebarSvg($sidebarIcons[$name], $class);
    $canAccess = fn (string $permission) => auth()->user()?->hasPermission($permission) ?? false;
    $studentOpen = $activeMenu === 'students';
    $masterOpen = $activeMenu === 'master';
    $reportOpen = $activeMenu === 'reports';
@endphp
<aside class="sidebar" data-sidebar>
    <div class="brand"><div class="brand-mark"><img src="{{ asset('images/mawa-center-mark.png') }}" alt="Logo Ma'wa Center"></div><div><strong>MA'WA <span>CENTER</span></strong><small>Manajemen Keuangan</small></div><button class="icon-button sidebar-close" data-sidebar-close>×</button></div>
    <nav class="sidebar-nav">
        @if($canAccess('dashboard'))
        <a href="{{ route('dashboard') }}" class="nav-item {{ $activeMenu === 'dashboard' ? 'active' : '' }}">{!! $sidebarIcon('grid') !!}<span>Dashboard</span></a>
        @endif
        @if($canAccess('students'))
        <div class="nav-group nested-nav {{ $studentOpen ? 'open' : '' }}">
            <button type="button" class="nav-item nav-parent {{ $studentOpen ? 'active' : '' }}" data-nav-toggle aria-expanded="{{ $studentOpen ? 'true' : 'false' }}">{!! $sidebarIcon('users') !!}<span>Manajemen Siswa</span>{!! $sidebarIcon('chevron', 'nav-chevron') !!}</button>
            <div class="nav-submenu">
                <a href="{{ route('student-management.students.index') }}" class="{{ $activeStudentMenu === 'data-siswa' ? 'active' : '' }}">{!! $sidebarIcon('users') !!}<span>Data Siswa</span></a>
                <a href="{{ route('student-management.identity-cleanup.index') }}" class="{{ $activeStudentMenu === 'rapikan-identitas' ? 'active' : '' }}">{!! $sidebarIcon('role') !!}<span>Rapikan Identitas</span></a>
                <a href="{{ route('student-management.class-transfer.index') }}" class="{{ $activeStudentMenu === 'pindah-kelas' ? 'active' : '' }}">{!! $sidebarIcon('switch') !!}<span>Pindah Kelas</span></a>
                <a href="{{ route('student-management.class-promotion.index') }}" class="{{ $activeStudentMenu === 'naik-kelas' ? 'active' : '' }}">{!! $sidebarIcon('arrow-up') !!}<span>Naik Kelas</span></a>
                <a href="{{ route('student-management.alumni.index') }}" class="{{ $activeStudentMenu === 'alumni' ? 'active' : '' }}">{!! $sidebarIcon('calendar') !!}<span>Data Alumni</span></a>
            </div>
        </div>
        @endif
        @if($canAccess('payments'))
        <a href="{{ route('finance.payments.index') }}" class="nav-item {{ $activeMenu === 'payment' ? 'active' : '' }}">{!! $sidebarIcon('card') !!}<span>Pembayaran</span></a>
        @endif
        @if($canAccess('bills'))
        <a href="{{ route('finance.bills.index') }}" class="nav-item {{ $activeMenu === 'bills' ? 'active' : '' }}">{!! $sidebarIcon('receipt') !!}<span>Tagihan</span>{!! $sidebarIcon('chevron', 'nav-chevron') !!}</a>
        @endif
        @if($canAccess('reports'))
        <div class="nav-group nested-nav {{ $reportOpen ? 'open' : '' }}">
            <button type="button" class="nav-item nav-parent {{ $reportOpen ? 'active' : '' }}" data-nav-toggle aria-expanded="{{ $reportOpen ? 'true' : 'false' }}">{!! $sidebarIcon('chart') !!}<span>Laporan</span>{!! $sidebarIcon('chevron', 'nav-chevron') !!}</button>
            <div class="nav-submenu">
                <a href="{{ route('reports.index') }}" class="{{ $activeReportMenu === 'report' ? 'active' : '' }}">{!! $sidebarIcon('chart') !!}<span>Laporan Pembayaran</span></a>
                @if($canAccess('payments'))
                <a href="{{ route('finance.payments.history') }}" class="{{ $activeReportMenu === 'history' ? 'active' : '' }}">{!! $sidebarIcon('receipt') !!}<span>Riwayat Pembayaran</span></a>
                @endif
            </div>
        </div>
        @endif
        @if($canAccess('master'))
        <div class="nav-group master-nav {{ $masterOpen ? 'open' : '' }}">
            <button type="button" class="nav-item nav-parent {{ $masterOpen ? 'active' : '' }}" data-master-nav-toggle aria-expanded="{{ $masterOpen ? 'true' : 'false' }}">{!! $sidebarIcon('database') !!}<span>Data Master</span>{!! $sidebarIcon('chevron', 'nav-chevron') !!}</button>
            <div class="nav-submenu">
                @foreach ([
                    'academic-years' => ['Tahun Pelajaran', 'calendar'],
                    'education-units' => ['Unit Pendidikan', 'grid'],
                    'classes' => ['Kelas', 'database'],
                    'fee-types' => ['Kategori Pembayaran', 'receipt'],
                    'fee-discounts' => ['Keringanan Biaya', 'wallet'],
                    'data-roles' => ['Data Role', 'role'],
                    'data-users' => ['Data User', 'users'],
                ] as $key => $item)
                    <a href="{{ route('master.index', ['tab' => $key]) }}" class="{{ $activeMasterMenu === $key ? 'active' : '' }}">{!! $sidebarIcon($item[1]) !!}<span>{{ $item[0] }}</span></a>
                @endforeach
            </div>
        </div>
        @endif
        @if($canAccess('settings'))
        <a href="{{ route('settings.index') }}" class="nav-item {{ $activeMenu === 'settings' ? 'active' : '' }}">{!! $sidebarIcon('settings') !!}<span>Pengaturan</span>{!! $sidebarIcon('chevron', 'nav-chevron') !!}</a>
        @endif
    </nav>
</aside>
