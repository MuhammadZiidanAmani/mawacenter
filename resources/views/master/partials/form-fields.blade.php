@if ($tab === 'students')
    <label>NIS <input name="nis" required value="{{ old('nis') }}"></label><label>NISN <input name="nisn" value="{{ old('nisn') }}"></label>
    <label class="span-2">Nama Siswa <input name="name" required value="{{ old('name') }}"></label>
    <label>Tempat Lahir <input name="birth_place" value="{{ old('birth_place') }}"></label><label>Tanggal Lahir <input type="date" name="birth_date" value="{{ old('birth_date') }}"></label>
    <label>Jenis Kelamin <select name="gender" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
    <label>Unit Pendidikan <select name="education_unit_id" required data-student-unit><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label>Kelas <select name="school_class_id" required data-student-class><option value="">Pilih Kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}">{{ $class->name }}</option>@endforeach</select></label>
    <label>Tahun Pelajaran <select name="academic_year_id" required>@foreach($academicYears as $year)<option value="{{ $year->id }}">{{ $year->name }}</option>@endforeach</select></label>
    <label>Tanggal Masuk <input type="date" name="entry_date" required value="{{ old('entry_date', now()->toDateString()) }}"></label>
    <label>Nama Ayah <input name="father_name" value="{{ old('father_name') }}"></label><label>Nama Ibu <input name="mother_name" value="{{ old('mother_name') }}"></label>
    <label>No. WA Ayah <input name="father_whatsapp" value="{{ old('father_whatsapp') }}" placeholder="08xxxxxxxxxx" data-father-whatsapp></label>
    <label>No. WA Ibu <span class="student-whatsapp-copy"><input name="mother_whatsapp" value="{{ old('mother_whatsapp') }}" placeholder="08xxxxxxxxxx" data-mother-whatsapp><button type="button" class="button button-secondary" data-copy-father-whatsapp>Samakan dengan Ayah</button></span></label>
    <label>Provinsi <select name="province" data-student-region="province"><option value="">Pilih Provinsi</option>@if(old('province'))<option value="{{ old('province') }}" selected>{{ old('province') }}</option>@endif</select></label>
    <label>Kabupaten/Kota <select name="city" data-student-region="city" disabled><option value="">Pilih Kabupaten/Kota</option>@if(old('city'))<option value="{{ old('city') }}" selected>{{ old('city') }}</option>@endif</select></label>
    <label>Kecamatan <select name="district" data-student-region="district" disabled><option value="">Pilih Kecamatan</option>@if(old('district'))<option value="{{ old('district') }}" selected>{{ old('district') }}</option>@endif</select></label>
    <label>Desa <select name="village" data-student-region="village" disabled><option value="">Pilih Desa</option>@if(old('village'))<option value="{{ old('village') }}" selected>{{ old('village') }}</option>@endif</select></label>
    <label class="span-2">Alamat <input name="address" value="{{ old('address') }}"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked data-student-status><span></span> Siswa aktif</label>
    <div class="student-inactive-fields span-2" data-inactive-fields hidden>
        <label>Tanggal Keluar <input type="date" name="exit_date" value="{{ old('exit_date') }}"></label>
        <label>Alasan Nonaktif <input name="inactive_reason" value="{{ old('inactive_reason') }}" placeholder="Contoh: Lulus, pindah, mengundurkan diri"></label>
    </div>
@elseif ($tab === 'education-units')
    <label>Kode Unit <input name="code" required value="{{ old('code') }}" placeholder="Contoh: MTs"></label><label>Nama Unit <input name="name" required value="{{ old('name') }}" placeholder="Madrasah Tsanawiyah"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Unit pendidikan aktif</label>
@elseif ($tab === 'classes')
    <label>Unit Pendidikan <select name="education_unit_id" required><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(old('education_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label>Nama Kelas <input name="name" required value="{{ old('name') }}" placeholder="Contoh: VII A"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Kelas aktif</label>
@elseif ($tab === 'academic-years')
    <label class="span-2">Tahun Pelajaran <input name="name" required value="{{ old('name') }}" placeholder="2025/2026"></label>
    <label>Tanggal Mulai <input type="date" name="start_date" value="{{ old('start_date') }}"></label><label>Tanggal Selesai <input type="date" name="end_date" value="{{ old('end_date') }}"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1"><span></span> Jadikan tahun pelajaran aktif</label>
@elseif ($tab === 'fee-types')
    <label class="span-2">Jenis Pembayaran <input name="name" required value="{{ old('name') }}" placeholder="Contoh: SPP Bulanan"></label>
    <label>Kelompok Pembayaran <select name="payment_group" required><option value="daftar-ulang" @selected(old('payment_group') === 'daftar-ulang')>Daftar Ulang</option><option value="laundry" @selected(old('payment_group') === 'laundry')>Laundry</option><option value="lain-lain" @selected(old('payment_group', 'lain-lain') === 'lain-lain')>Lain-lain</option></select></label>
    <label>Unit Pendidikan <select name="education_unit_id" required data-student-unit><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(old('education_unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach</select></label>
    <label>Tahun Pelajaran <select name="academic_year_id">@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $activeAcademicYear?->id) == $year->id)>{{ $year->name }}</option>@endforeach</select></label>
    <div class="span-2 registration-class-field">
        <span>Pilih Kelas</span>
        <div class="registration-class-list" data-registration-class-list>
            @foreach($classes as $class)
                <label data-unit-id="{{ $class->education_unit_id }}" @if(old('education_unit_id') != $class->education_unit_id) hidden @endif>
                    <input type="checkbox" name="school_class_ids[]" value="{{ $class->id }}" @checked(in_array($class->id, old('school_class_ids', [])))>
                    <span>{{ $class->educationUnit?->code }} - {{ $class->name }}</span>
                </label>
            @endforeach
            <p data-registration-class-empty>Pilih unit pendidikan terlebih dahulu.</p>
        </div>
        <small>Ceklis kelas yang memakai jenis pembayaran ini.</small>
    </div>
    <label>Nominal <input type="text" inputmode="numeric" name="amount" required value="{{ old('amount') }}" placeholder="0" data-currency-input></label>
    <label>Periode Tagihan <select name="period" required><option>Bulanan</option><option>Tahunan</option><option>Sekali Bayar</option></select></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Jenis pembayaran aktif</label>
@elseif ($tab === 'spp-settings')
    <label class="span-2">Unit Pendidikan <select name="education_unit_id" required><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}">{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label class="span-2">Nominal SPP <input type="text" inputmode="numeric" name="amount" required value="{{ old('amount') }}" placeholder="0" data-currency-input></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Set SPP aktif</label>
@else
    <label class="span-2">Siswa
        <div class="student-search-picker" data-student-picker>
            <input type="search" placeholder="Ketik nama siswa atau NIS..." autocomplete="off" required data-student-search>
            <select name="student_id" required data-student-source><option value="">Pilih Siswa</option>@foreach($studentOptions as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select>
            <div class="student-search-results" data-student-results hidden></div>
        </div>
    </label>
    @php($selectedDiscountPayment = old('source_type', 'spp') === 'fee_type' && old('fee_type_id') ? 'fee_type:'.old('fee_type_id') : 'spp')
    <label>Jenis Pembayaran
        <select required data-discount-payment>
            <option value="spp" @selected($selectedDiscountPayment === 'spp')>SPP</option>
            @foreach($feeTypeOptions as $feeType)<option value="fee_type:{{ $feeType->id }}" @selected($selectedDiscountPayment === 'fee_type:'.$feeType->id)>{{ $feeType->name }}</option>@endforeach
        </select>
        <input type="hidden" name="source_type" value="{{ old('source_type', 'spp') }}" data-discount-source>
        <input type="hidden" name="fee_type_id" value="{{ old('fee_type_id') }}" data-discount-fee-type>
    </label>
    <label>Jenis Keringanan <select name="discount_type" required data-discount-type><option value="amount">Potongan Nominal</option><option value="percentage">Potongan Persentase</option></select></label>
    <label>Nilai Keringanan <input type="text" inputmode="numeric" name="discount_value" required value="{{ old('discount_value') }}" placeholder="Contoh: 300.000 atau 50" data-discount-value data-currency-input></label>
    <label>Tanggal Mulai <input type="date" name="start_date" required value="{{ old('start_date', now()->toDateString()) }}"></label>
    <label>Tanggal Selesai <input type="date" name="end_date" value="{{ old('end_date') }}"></label>
    <label class="span-2">Alasan Keringanan <input name="reason" value="{{ old('reason') }}" placeholder="Contoh: Beasiswa atau keringanan khusus"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Keringanan aktif</label>
@endif
