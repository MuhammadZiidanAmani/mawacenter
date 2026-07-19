@if ($tab === 'students')
    @php
        $studentForm = $editingStudent ?? null;
        $studentField = fn ($field, $default = '') => old($field, $studentForm?->{$field} ?? $default);
        $studentDate = function ($field, $default = '') use ($studentForm) {
            $oldValue = old($field);
            if ($oldValue !== null) {
                return $oldValue;
            }

            $value = $studentForm?->{$field};
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return $value ? substr((string) $value, 0, 10) : $default;
        };
        $selectedUnitId = old('education_unit_id', $studentForm?->schoolClass?->education_unit_id);
        $selectedClassId = old('school_class_id', $studentForm?->school_class_id);
        $selectedYearId = old('academic_year_id', $studentForm?->academic_year_id ?? $activeAcademicYear?->id);
        $selectedGender = old('gender', $studentForm?->gender ?? 'L');
        $isActiveStudent = (string) old('is_active', $studentForm ? ($studentForm->is_active ? '1' : '0') : '1') === '1';
    @endphp
    @unless($studentForm)
    <div class="span-2 student-existing-choice">
        <strong>Data siswa</strong>
        <p>Pilih siswa yang sudah ada jika siswa akan didaftarkan ke unit pendidikan lain.</p>
    </div>
    <label class="span-2" data-existing-student-field>Siswa yang sudah ada (opsional)
        <div class="student-search-picker" data-student-picker data-student-optional>
            <input type="search" placeholder="Ketik nama atau NIS siswa..." autocomplete="off" data-student-search>
            <select name="existing_student_id" data-student-source data-existing-student><option value="">Input sebagai siswa baru</option>@foreach($existingStudentOptions as $existingStudent)<option value="{{ $existingStudent->id }}" @selected(old('existing_student_id') == $existingStudent->id)>{{ $existingStudent->schoolClass?->educationUnit?->code ?? '-' }} - {{ $existingStudent->nis }} - {{ $existingStudent->name }}</option>@endforeach</select>
            <div class="student-search-results" data-student-results hidden></div>
        </div>
        <small>Identitas, alamat, dan data orang tua akan diambil otomatis.</small>
    </label>
    @endunless
    <label>NIS <input name="nis" required value="{{ $studentField('nis') }}"></label>
    <label>NISN <input name="nisn" value="{{ $studentField('nisn') }}"></label>
    <div class="student-personal-fields span-2" data-new-student-fields>
    <label class="span-2">Nama Siswa <input name="name" required value="{{ $studentField('name') }}"></label>
    <label>Tempat Lahir <input name="birth_place" value="{{ $studentField('birth_place') }}"></label><label>Tanggal Lahir <input type="date" name="birth_date" value="{{ $studentDate('birth_date') }}"></label>
    <label>Jenis Kelamin <select name="gender" required><option value="L" @selected($selectedGender === 'L')>Laki-laki</option><option value="P" @selected($selectedGender === 'P')>Perempuan</option></select></label>
    </div>
    <label>Unit Pendidikan <select name="education_unit_id" required data-student-unit><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected((string) $selectedUnitId === (string) $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label>Kelas <select name="school_class_id" required data-student-class><option value="">Pilih Kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>@endforeach</select></label>
    <label>Tahun Pelajaran <select name="academic_year_id" required>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected((string) $selectedYearId === (string) $year->id)>{{ $year->name }}</option>@endforeach</select></label>
    <label>Tanggal Masuk <input type="date" name="entry_date" required value="{{ $studentDate('entry_date', now()->toDateString()) }}"></label>
    <label>Mulai Tagihan Khusus <input type="date" name="billing_start_date" value="{{ $studentDate('billing_start_date') }}"></label>
    <div class="student-personal-fields span-2" data-new-student-fields>
    <label>Nama Ayah <input name="father_name" value="{{ $studentField('father_name') }}"></label><label>Nama Ibu <input name="mother_name" value="{{ $studentField('mother_name') }}"></label>
    <label>No. WA Ayah <input name="father_whatsapp" value="{{ $studentField('father_whatsapp') }}" placeholder="08xxxxxxxxxx" data-father-whatsapp></label>
    <label>No. WA Ibu <span class="student-whatsapp-copy"><input name="mother_whatsapp" value="{{ $studentField('mother_whatsapp') }}" placeholder="08xxxxxxxxxx" data-mother-whatsapp><button type="button" class="button button-secondary" data-copy-father-whatsapp>Samakan dengan Ayah</button></span></label>
    <label>Provinsi <select name="province" data-student-region="province"><option value="">Pilih Provinsi</option>@if($studentField('province'))<option value="{{ $studentField('province') }}" selected>{{ $studentField('province') }}</option>@endif</select></label>
    <label>Kabupaten/Kota <select name="city" data-student-region="city" disabled><option value="">Pilih Kabupaten/Kota</option>@if($studentField('city'))<option value="{{ $studentField('city') }}" selected>{{ $studentField('city') }}</option>@endif</select></label>
    <label>Kecamatan <select name="district" data-student-region="district" disabled><option value="">Pilih Kecamatan</option>@if($studentField('district'))<option value="{{ $studentField('district') }}" selected>{{ $studentField('district') }}</option>@endif</select></label>
    <label>Desa <select name="village" data-student-region="village" disabled><option value="">Pilih Desa</option>@if($studentField('village'))<option value="{{ $studentField('village') }}" selected>{{ $studentField('village') }}</option>@endif</select></label>
    <label class="span-2">Alamat <input name="address" value="{{ $studentField('address') }}"></label>
    </div>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" @checked($isActiveStudent) data-student-status><span></span> Siswa aktif</label>
    <div class="student-inactive-fields span-2" data-inactive-fields hidden>
        <label>Tanggal Keluar <input type="date" name="exit_date" value="{{ $studentDate('exit_date') }}"></label>
        <label>Alasan Nonaktif <input name="inactive_reason" value="{{ $studentField('inactive_reason') }}" placeholder="Contoh: Lulus, pindah, mengundurkan diri"></label>
    </div>
@elseif ($tab === 'education-units')
    <label>Kode Unit <input name="code" required value="{{ old('code') }}" placeholder="" autocomplete="off"></label><label>Nama Unit <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Unit pendidikan aktif</label>
@elseif ($tab === 'classes')
    <label>Unit Pendidikan <select name="education_unit_id" required><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(old('education_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label>Nama Kelas <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Kelas aktif</label>
@elseif ($tab === 'academic-years')
    <label class="span-2">Tahun Pelajaran <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label>Tanggal Mulai <input type="date" name="start_date" value="{{ old('start_date') }}"></label><label>Tanggal Selesai <input type="date" name="end_date" value="{{ old('end_date') }}"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1"><span></span> Jadikan tahun pelajaran aktif</label>
@elseif ($tab === 'fee-types')
    @php($selectedPaymentGroup = old('payment_group', 'spp'))
    <div class="fee-type-simple span-2">
        <label class="fee-type-simple-field">
            <span>Nama Pembayaran</span>
            <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off">
        </label>

        <label class="fee-type-simple-field fee-type-simple-short">
            <span>Kategori Pembayaran</span>
            <select name="payment_group" required data-fee-category>
                <option value="spp" @selected($selectedPaymentGroup === 'spp')>SPP</option>
                <option value="daftar-ulang" @selected($selectedPaymentGroup === 'daftar-ulang')>Daftar Ulang</option>
                <option value="laundry" @selected($selectedPaymentGroup === 'laundry')>Laundry</option>
                <option value="lain-lain" @selected($selectedPaymentGroup === 'lain-lain')>Lain-lain</option>
            </select>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short">
            <span>Nominal</span>
            <input type="text" inputmode="numeric" name="amount" required value="{{ old('amount') }}" placeholder="0" data-currency-input>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short">
            <span>Unit Pendidikan</span>
            <select name="education_unit_id" required data-student-unit>
                <option value="">Pilih Unit</option>
                @foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(old('education_unit_id') == $unit->id)>{{ $unit->code }}</option>@endforeach
            </select>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short">
            <span>Tahun Pelajaran</span>
            <select name="academic_year_id" required>
                @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected(old('academic_year_id', $activeAcademicYear?->id) == $year->id)>{{ $year->name }}</option>@endforeach
            </select>
        </label>

        <div class="fee-type-simple-field">
            <span>Berlaku untuk</span>
            <select name="class_scope" data-registration-scope-select>
                <option value="all" @selected(old('class_scope', 'all') === 'all')>Semua Tingkat</option>
                <option value="level" @selected(old('class_scope') === 'level')>Tingkat Tertentu</option>
            </select>
            <small data-fee-scope-help>Kategori akan berlaku untuk seluruh tingkat pada unit yang dipilih.</small>
        </div>

        <label class="fee-type-simple-field fee-type-simple-short" data-registration-level-field hidden>
            <span>Tingkat</span>
            <select name="class_level" data-registration-level-select>
                <option value="">Pilih Tingkat</option>
                @foreach($classLevels as $level)
                    <option value="{{ $level['key'] }}" data-unit-id="{{ $level['education_unit_id'] }}" @selected(old('class_level') === $level['key'])>{{ $level['label'] }}</option>
                @endforeach
            </select>
            <small>Pilih tingkat, bukan rombel.</small>
        </label>

        <div class="fee-type-simple-field fee-billing-choice" data-fee-billing-choice hidden>
            <span>Pencatatan Pembayaran</span>
            <div class="fee-type-simple-scope">
                <label><input type="radio" name="creates_bill" value="1" @checked(old('creates_bill', '1') === '1')><span>Dijadikan Tagihan Wajib</span></label>
                <label><input type="radio" name="creates_bill" value="0" @checked(old('creates_bill') === '0')><span>Pembayaran Opsional</span></label>
            </div>
        </div>

        <label class="fee-type-simple-field fee-type-simple-short" data-fee-period-field hidden>
            <span>Periode Tagihan</span>
            <select name="period" required data-fee-period><option>Bulanan</option><option>Tahunan</option><option>Sekali Bayar</option></select>
        </label>

        <div class="fee-type-simple-note" data-fee-behavior-summary>
            <strong data-fee-behavior-title>Tagihan Wajib</strong>
            <span data-fee-behavior-description>SPP akan masuk ke tagihan siswa setiap bulan.</span>
        </div>

        <label class="switch-field fee-type-simple-status"><input type="checkbox" name="is_active" value="1" checked><span></span> Kategori pembayaran aktif</label>
    </div>
@elseif ($tab === 'data-roles')
    <label>Kode Role <input name="key" required value="{{ old('key') }}" placeholder="contoh: admin_unit"></label>
    <label>Nama Role <input name="name" required value="{{ old('name') }}" placeholder="Contoh: Admin Unit"></label>
    <label class="span-2">Deskripsi <input name="description" value="{{ old('description') }}" placeholder="Keterangan singkat role"></label>
    <div class="role-permission-field span-2">
        <span>Hak Akses</span>
        <div class="role-permission-grid">
            @foreach($permissionOptions as $key => $label)
                <label>
                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, old('permissions', []), true))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Role aktif</label>
@elseif ($tab === 'data-users')
    <label>Nama User <input name="name" required value="{{ old('name') }}" placeholder="Nama lengkap user"></label>
    <label>Username <input name="username" required value="{{ old('username') }}" placeholder="username"></label>
    <label>Email <input type="email" name="email" required value="{{ old('email') }}" placeholder="email@domain.com"></label>
    <label>Role
        <select name="role" required>
            <option value="">Pilih Role</option>
            @foreach($roleOptions as $key => $label)<option value="{{ $key }}" @selected(old('role') === $key)>{{ $label }}</option>@endforeach
        </select>
    </label>
    <label class="span-2">Akses Unit Pendidikan
        <select name="education_unit_ids[]" multiple size="5">
            @foreach($educationUnits as $unit)
                <option value="{{ $unit->id }}" @selected(in_array($unit->id, old('education_unit_ids', [])))>{{ $unit->code }} - {{ $unit->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="span-2">Akses Wali Santri
        <select name="guardian_student_ids[]" multiple size="7">
            @foreach($studentOptions as $student)
                <option value="{{ $student->id }}" @selected(in_array($student->id, old('guardian_student_ids', [])))>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="span-2">Password <input type="password" name="password" autocomplete="new-password" placeholder="Wajib saat tambah, kosongkan saat edit jika tidak diganti"></label>
@else
    <div class="fee-discount-simple span-2">
    <label class="fee-discount-simple-field fee-discount-student-field">
        <span>Siswa</span>
        <div class="student-search-picker" data-student-picker>
            <input type="search" placeholder="Ketik nama siswa atau NIS..." autocomplete="off" required data-student-search>
            <select name="student_id" required data-student-source><option value="">Pilih Siswa</option>@foreach($studentOptions as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select>
            <div class="student-search-results" data-student-results hidden></div>
        </div>
    </label>
    @php($selectedDiscountPayment = old('source_type', 'spp') === 'fee_type' && old('fee_type_id') ? 'fee_type:'.old('fee_type_id') : 'spp')
    <label class="fee-discount-simple-field">
        <span>Kategori Pembayaran</span>
        <select required data-discount-payment>
            <option value="spp" @selected($selectedDiscountPayment === 'spp')>SPP</option>
            @foreach($feeTypeOptions as $feeType)<option value="fee_type:{{ $feeType->id }}" @selected($selectedDiscountPayment === 'fee_type:'.$feeType->id)>{{ $feeType->name }}</option>@endforeach
        </select>
        <input type="hidden" name="source_type" value="{{ old('source_type', 'spp') }}" data-discount-source>
        <input type="hidden" name="fee_type_id" value="{{ old('fee_type_id') }}" data-discount-fee-type>
    </label>
    <label class="fee-discount-simple-field"><span>Jenis Keringanan</span><select name="discount_type" required data-discount-type><option value="amount">Potongan Nominal</option><option value="percentage">Potongan Persentase</option></select></label>
    <label class="fee-discount-simple-field"><span>Nilai Keringanan</span><input type="text" inputmode="numeric" name="discount_value" required value="{{ old('discount_value') }}" placeholder="Contoh: 300.000 atau 50" data-discount-value data-currency-input></label>
    <label class="fee-discount-simple-field"><span>Tanggal Mulai</span><input type="date" name="start_date" required value="{{ old('start_date', now()->toDateString()) }}"></label>
    <label class="fee-discount-simple-field"><span>Tanggal Selesai</span><input type="date" name="end_date" value="{{ old('end_date') }}"></label>
    <label class="fee-discount-simple-field fee-discount-reason-field"><span>Alasan Keringanan</span><input name="reason" value="{{ old('reason') }}" placeholder="Contoh: Beasiswa atau keringanan khusus"></label>
    <label class="switch-field fee-discount-simple-status"><input type="checkbox" name="is_active" value="1" checked><span></span> Keringanan aktif</label>
    </div>
@endif
