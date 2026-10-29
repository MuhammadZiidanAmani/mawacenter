@php
    $masterFormRecord = $editingMaster ?? null;
    $masterField = fn ($field, $default = '') => old($field, $masterFormRecord?->{$field} ?? $default);
    $masterDate = function ($field, $default = '') use ($masterFormRecord) {
        $oldValue = old($field);
        if ($oldValue !== null) {
            return $oldValue;
        }

        $value = $masterFormRecord?->{$field};

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($value ? substr((string) $value, 0, 10) : $default);
    };
    $masterIsActive = (string) old('is_active', $masterFormRecord ? ($masterFormRecord->is_active ? '1' : '0') : '1') === '1';
@endphp
@switch($formTab)
@case('students')
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
        $selectedIntakeStatus = old('intake_status', $studentForm?->intake_status ?? \App\Models\Student::INTAKE_RETURNING);
        $isActiveStudent = (string) old('is_active', $studentForm ? ($studentForm->is_active ? '1' : '0') : '1') === '1';
    @endphp
    @unless($studentForm)
    <section class="student-form-section student-form-source span-2">
        <div class="student-form-section-heading">
            <h2>Sumber Data Siswa</h2>
            <p>Pilih data yang sudah ada jika siswa akan didaftarkan ke unit pendidikan lain.</p>
        </div>
        <label data-existing-student-field>Siswa yang sudah ada (opsional)
            <div class="student-search-picker" data-student-picker data-student-optional>
                <input type="search" placeholder="Ketik nama atau NIS siswa..." autocomplete="off" data-student-search>
                <select name="existing_student_id" data-student-source data-existing-student @error('existing_student_id') aria-invalid="true" aria-describedby="student-existing-error" @enderror><option value="">Input sebagai siswa baru</option>@foreach($existingStudentOptions as $existingStudent)<option value="{{ $existingStudent->id }}" @selected(old('existing_student_id') == $existingStudent->id)>{{ $existingStudent->schoolClass?->educationUnit?->code ?? '-' }} - {{ $existingStudent->nis }} - {{ $existingStudent->name }}</option>@endforeach</select>
                <div class="student-search-results" data-student-results hidden></div>
            </div>
            <small>Identitas, alamat, dan data orang tua akan diambil otomatis.</small>
            @error('existing_student_id')<small class="master-field-error" id="student-existing-error">{{ $message }}</small>@enderror
        </label>
    </section>
    @endunless
    <section class="student-form-section span-2">
        <div class="student-form-section-heading">
            <h2>Identitas Siswa</h2>
            <p>Isi identitas dasar sesuai dokumen resmi siswa.</p>
        </div>
        <div class="student-form-grid">
            <label>NIS
                <input name="nis" required value="{{ $studentField('nis') }}" @error('nis') aria-invalid="true" aria-describedby="student-nis-error" @enderror>
                @error('nis')<small class="master-field-error" id="student-nis-error">{{ $message }}</small>@enderror
            </label>
            <label>NISN
                <input name="nisn" value="{{ $studentField('nisn') }}" @error('nisn') aria-invalid="true" aria-describedby="student-nisn-error" @enderror>
                @error('nisn')<small class="master-field-error" id="student-nisn-error">{{ $message }}</small>@enderror
            </label>
            <div class="student-form-grid student-form-grid-nested span-2" data-new-student-fields>
                <label class="span-2">Nama Siswa
                    <input name="name" required value="{{ $studentField('name') }}" @error('name') aria-invalid="true" aria-describedby="student-name-error" @enderror>
                    @error('name')<small class="master-field-error" id="student-name-error">{{ $message }}</small>@enderror
                </label>
                <label>Tempat Lahir
                    <input name="birth_place" value="{{ $studentField('birth_place') }}" @error('birth_place') aria-invalid="true" aria-describedby="student-birth-place-error" @enderror>
                    @error('birth_place')<small class="master-field-error" id="student-birth-place-error">{{ $message }}</small>@enderror
                </label>
                <label>Tanggal Lahir
                    <input type="date" name="birth_date" value="{{ $studentDate('birth_date') }}" @error('birth_date') aria-invalid="true" aria-describedby="student-birth-date-error" @enderror>
                    @error('birth_date')<small class="master-field-error" id="student-birth-date-error">{{ $message }}</small>@enderror
                </label>
                <label>Jenis Kelamin
                    <select name="gender" required @error('gender') aria-invalid="true" aria-describedby="student-gender-error" @enderror><option value="L" @selected($selectedGender === 'L')>Laki-laki</option><option value="P" @selected($selectedGender === 'P')>Perempuan</option></select>
                    @error('gender')<small class="master-field-error" id="student-gender-error">{{ $message }}</small>@enderror
                </label>
            </div>
        </div>
    </section>

    <section class="student-form-section span-2">
        <div class="student-form-section-heading">
            <h2>Data Akademik</h2>
            <p>Tentukan unit, kelas, tahun pelajaran, dan awal kewajiban siswa.</p>
        </div>
        <div class="student-form-grid">
            <label>Unit Pendidikan
                <select name="education_unit_id" required data-student-unit @error('education_unit_id') aria-invalid="true" aria-describedby="student-unit-error" @enderror><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected((string) $selectedUnitId === (string) $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select>
                @error('education_unit_id')<small class="master-field-error" id="student-unit-error">{{ $message }}</small>@enderror
            </label>
            <label>Kelas
                <select name="school_class_id" required data-student-class @error('school_class_id') aria-invalid="true" aria-describedby="student-class-error" @enderror><option value="">Pilih Kelas</option>@foreach($classes as $class)<option value="{{ $class->id }}" data-unit-id="{{ $class->education_unit_id }}" @selected((string) $selectedClassId === (string) $class->id)>{{ $class->name }}</option>@endforeach</select>
                @error('school_class_id')<small class="master-field-error" id="student-class-error">{{ $message }}</small>@enderror
            </label>
            <label>Tahun Pelajaran
                <select name="academic_year_id" required @error('academic_year_id') aria-invalid="true" aria-describedby="student-year-error" @enderror>@foreach($academicYears as $year)<option value="{{ $year->id }}" @selected((string) $selectedYearId === (string) $year->id)>{{ $year->name }}</option>@endforeach</select>
                @error('academic_year_id')<small class="master-field-error" id="student-year-error">{{ $message }}</small>@enderror
            </label>
            <label>Tanggal Masuk
                <input type="date" name="entry_date" required value="{{ $studentDate('entry_date', now()->toDateString()) }}" @error('entry_date') aria-invalid="true" aria-describedby="student-entry-date-error" @enderror>
                @error('entry_date')<small class="master-field-error" id="student-entry-date-error">{{ $message }}</small>@enderror
            </label>
            <label>Mulai Tagihan Khusus
                <input type="date" name="billing_start_date" value="{{ $studentDate('billing_start_date') }}" @error('billing_start_date') aria-invalid="true" aria-describedby="student-billing-start-error" @enderror>
                @error('billing_start_date')<small class="master-field-error" id="student-billing-start-error">{{ $message }}</small>@enderror
            </label>
            <label>Status Masuk
                <select name="intake_status" required @error('intake_status') aria-invalid="true" aria-describedby="student-intake-status-error" @enderror>
                    @foreach(\App\Models\Student::INTAKE_LABELS as $key => $label)
                        <option value="{{ $key }}" @selected($selectedIntakeStatus === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('intake_status')<small class="master-field-error" id="student-intake-status-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="student-form-section span-2" data-new-student-fields>
        <div class="student-form-section-heading">
            <h2>Data Orang Tua</h2>
            <p>Lengkapi nama dan nomor WhatsApp yang dapat dihubungi.</p>
        </div>
        <div class="student-form-grid">
            <label>Nama Ayah
                <input name="father_name" value="{{ $studentField('father_name') }}" @error('father_name') aria-invalid="true" aria-describedby="student-father-name-error" @enderror>
                @error('father_name')<small class="master-field-error" id="student-father-name-error">{{ $message }}</small>@enderror
            </label>
            <label>Nama Ibu
                <input name="mother_name" value="{{ $studentField('mother_name') }}" @error('mother_name') aria-invalid="true" aria-describedby="student-mother-name-error" @enderror>
                @error('mother_name')<small class="master-field-error" id="student-mother-name-error">{{ $message }}</small>@enderror
            </label>
            <label>No. WA Ayah
                <input name="father_whatsapp" value="{{ $studentField('father_whatsapp') }}" placeholder="08xxxxxxxxxx" data-father-whatsapp @error('father_whatsapp') aria-invalid="true" aria-describedby="student-father-whatsapp-error" @enderror>
                @error('father_whatsapp')<small class="master-field-error" id="student-father-whatsapp-error">{{ $message }}</small>@enderror
            </label>
            <label>No. WA Ibu
                <span class="student-whatsapp-copy"><input name="mother_whatsapp" value="{{ $studentField('mother_whatsapp') }}" placeholder="08xxxxxxxxxx" data-mother-whatsapp @error('mother_whatsapp') aria-invalid="true" aria-describedby="student-mother-whatsapp-error" @enderror><button type="button" class="button button-secondary" data-copy-father-whatsapp>Samakan dengan Ayah</button></span>
                @error('mother_whatsapp')<small class="master-field-error" id="student-mother-whatsapp-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="student-form-section span-2" data-new-student-fields>
        <div class="student-form-section-heading">
            <h2>Alamat</h2>
            <p>Gunakan alamat domisili siswa yang masih berlaku.</p>
        </div>
        <div class="student-form-grid">
            <label>Provinsi
                <select name="province" data-student-region="province" @error('province') aria-invalid="true" aria-describedby="student-province-error" @enderror><option value="">Pilih Provinsi</option>@if($studentField('province'))<option value="{{ $studentField('province') }}" selected>{{ $studentField('province') }}</option>@endif</select>
                @error('province')<small class="master-field-error" id="student-province-error">{{ $message }}</small>@enderror
            </label>
            <label>Kabupaten/Kota
                <select name="city" data-student-region="city" disabled @error('city') aria-invalid="true" aria-describedby="student-city-error" @enderror><option value="">Pilih Kabupaten/Kota</option>@if($studentField('city'))<option value="{{ $studentField('city') }}" selected>{{ $studentField('city') }}</option>@endif</select>
                @error('city')<small class="master-field-error" id="student-city-error">{{ $message }}</small>@enderror
            </label>
            <label>Kecamatan
                <select name="district" data-student-region="district" disabled @error('district') aria-invalid="true" aria-describedby="student-district-error" @enderror><option value="">Pilih Kecamatan</option>@if($studentField('district'))<option value="{{ $studentField('district') }}" selected>{{ $studentField('district') }}</option>@endif</select>
                @error('district')<small class="master-field-error" id="student-district-error">{{ $message }}</small>@enderror
            </label>
            <label>Desa
                <select name="village" data-student-region="village" disabled @error('village') aria-invalid="true" aria-describedby="student-village-error" @enderror><option value="">Pilih Desa</option>@if($studentField('village'))<option value="{{ $studentField('village') }}" selected>{{ $studentField('village') }}</option>@endif</select>
                @error('village')<small class="master-field-error" id="student-village-error">{{ $message }}</small>@enderror
            </label>
            <label class="span-2">Alamat Lengkap
                <input name="address" value="{{ $studentField('address') }}" @error('address') aria-invalid="true" aria-describedby="student-address-error" @enderror>
                @error('address')<small class="master-field-error" id="student-address-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="student-form-section span-2">
        <div class="student-form-section-heading">
            <h2>Status Siswa</h2>
            <p>Nonaktifkan hanya ketika siswa keluar, pindah, atau lulus.</p>
        </div>
        <label class="student-status-toggle">
            <input type="checkbox" name="is_active" value="1" @checked($isActiveStudent) data-student-status>
            <span><strong>Siswa aktif</strong><small>Siswa aktif tetap tersedia pada proses akademik dan keuangan.</small></span>
        </label>
        <div class="student-form-grid student-inactive-fields" data-inactive-fields hidden>
            <label>Tanggal Keluar
                <input type="date" name="exit_date" value="{{ $studentDate('exit_date') }}" @error('exit_date') aria-invalid="true" aria-describedby="student-exit-date-error" @enderror>
                @error('exit_date')<small class="master-field-error" id="student-exit-date-error">{{ $message }}</small>@enderror
            </label>
            <label>Alasan Nonaktif
                <input name="inactive_reason" value="{{ $studentField('inactive_reason') }}" placeholder="Contoh: Lulus, pindah, mengundurkan diri" @error('inactive_reason') aria-invalid="true" aria-describedby="student-inactive-reason-error" @enderror>
                @error('inactive_reason')<small class="master-field-error" id="student-inactive-reason-error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>
    @break
@case('education-units')
    <label>Kode Unit <input name="code" required value="{{ old('code') }}" placeholder="" autocomplete="off"></label><label>Nama Unit <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Unit pendidikan aktif</label>
    @break
@case('classes')
    <label>Unit Pendidikan <select name="education_unit_id" required><option value="">Pilih Unit Pendidikan</option>@foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected(old('education_unit_id') == $unit->id)>{{ $unit->code }} - {{ $unit->name }}</option>@endforeach</select></label>
    <label>Nama Kelas <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" checked><span></span> Kelas aktif</label>
    @break
@case('academic-years')
    <label class="span-2">Tahun Pelajaran <input name="name" required value="{{ old('name') }}" placeholder="" autocomplete="off"></label>
    <label>Tanggal Mulai <input type="date" name="start_date" value="{{ old('start_date') }}"></label><label>Tanggal Selesai <input type="date" name="end_date" value="{{ old('end_date') }}"></label>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1"><span></span> Jadikan tahun pelajaran aktif</label>
    @break
@case('fee-types')
    @php
        $selectedPaymentGroup = $masterField('payment_group', 'spp');
        $selectedClassLevel = $masterField('class_level');
        $selectedClassScope = old('class_scope', $selectedClassLevel ? 'level' : 'all');
        $selectedCreatesBill = (string) old('creates_bill', $masterFormRecord ? ($masterFormRecord->creates_bill ? '1' : '0') : '1');
    @endphp
    <div class="fee-type-simple span-2">
        <label class="fee-type-simple-field">
            <span>Nama Pembayaran</span>
            <input name="name" required value="{{ $masterField('name') }}" placeholder="" autocomplete="off">
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
            <input type="text" inputmode="numeric" name="amount" required value="{{ $masterField('amount') }}" placeholder="0" data-currency-input>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short">
            <span>Unit Pendidikan</span>
            <select name="education_unit_id" required data-student-unit>
                <option value="">Pilih Unit</option>
                @foreach($educationUnits as $unit)<option value="{{ $unit->id }}" @selected((string) $masterField('education_unit_id') === (string) $unit->id)>{{ $unit->code }}</option>@endforeach
            </select>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short fee-type-simple-academic-year">
            <span>Tahun Pelajaran</span>
            <select name="academic_year_id" required>
                @foreach($academicYears as $year)<option value="{{ $year->id }}" @selected((string) $masterField('academic_year_id', $activeAcademicYear?->id) === (string) $year->id)>{{ $year->name }}</option>@endforeach
            </select>
        </label>

        <div class="fee-type-simple-field fee-type-simple-class-scope">
            <span>Berlaku untuk</span>
            <select name="class_scope" data-registration-scope-select>
                <option value="all" @selected($selectedClassScope === 'all')>Semua Tingkat</option>
                <option value="level" @selected($selectedClassScope === 'level')>Tingkat Tertentu</option>
            </select>
            <small data-fee-scope-help>Kategori akan berlaku untuk seluruh tingkat pada unit yang dipilih.</small>
        </div>

        <label class="fee-type-simple-field fee-type-simple-student-scope">
            <span>Sasaran Siswa</span>
            <select name="student_scope">
                @foreach(\App\Models\FeeType::STUDENT_SCOPE_LABELS as $key => $label)
                    <option value="{{ $key }}" @selected($masterField('student_scope', \App\Models\FeeType::STUDENT_SCOPE_ALL) === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label class="fee-type-simple-field fee-type-simple-short" data-registration-level-field hidden>
            <span>Tingkat</span>
            <select name="class_level" data-registration-level-select>
                <option value="">Pilih Tingkat</option>
                @foreach($classLevels as $level)
                    <option value="{{ $level['key'] }}" data-unit-id="{{ $level['education_unit_id'] }}" @selected($selectedClassLevel === $level['key'])>{{ $level['label'] }}</option>
                @endforeach
            </select>
            <small>Pilih tingkat, bukan rombel.</small>
        </label>

        <div class="fee-type-simple-field fee-billing-choice" data-fee-billing-choice hidden>
            <span>Pencatatan Pembayaran</span>
            <div class="fee-type-simple-scope">
                <label><input type="radio" name="creates_bill" value="1" @checked($selectedCreatesBill === '1')><span>Dijadikan Tagihan Wajib</span></label>
                <label><input type="radio" name="creates_bill" value="0" @checked($selectedCreatesBill === '0')><span>Pembayaran Opsional</span></label>
            </div>
        </div>

        <label class="fee-type-simple-field fee-type-simple-short" data-fee-period-field hidden>
            <span>Periode Tagihan</span>
            <select name="period" required data-fee-period><option @selected($masterField('period', 'Bulanan') === 'Bulanan')>Bulanan</option><option @selected($masterField('period') === 'Tahunan')>Tahunan</option><option @selected($masterField('period') === 'Sekali Bayar')>Sekali Bayar</option></select>
        </label>

        <div class="fee-type-simple-note" data-fee-behavior-summary>
            <strong data-fee-behavior-title>Tagihan Wajib</strong>
            <span data-fee-behavior-description>SPP akan masuk ke tagihan siswa setiap bulan.</span>
        </div>

        <label class="switch-field fee-type-simple-status"><input type="checkbox" name="is_active" value="1" @checked($masterIsActive)><span></span> Kategori pembayaran aktif</label>
    </div>
    @break
@case('data-roles')
    @php
        $selectedPermissions = old('permissions', $masterFormRecord?->permissions ?? []);
    @endphp
    <label>Kode Role <input name="key" required value="{{ $masterField('key') }}" placeholder="contoh: admin_unit"></label>
    <label>Nama Role <input name="name" required value="{{ $masterField('name') }}" placeholder="Contoh: Admin Unit"></label>
    <label class="span-2">Deskripsi <input name="description" value="{{ $masterField('description') }}" placeholder="Keterangan singkat role"></label>
    <div class="role-permission-field span-2">
        <span>Hak Akses</span>
        <div class="role-permission-grid">
            @foreach($permissionOptions as $key => $label)
                <label>
                    <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selectedPermissions, true))>
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>
    <label class="switch-field span-2"><input type="checkbox" name="is_active" value="1" @checked($masterIsActive)><span></span> Role aktif</label>
    @break
@case('data-users')
    @php
        $selectedUserUnitIds = old('education_unit_ids', $masterFormRecord instanceof \App\Models\User ? $masterFormRecord->educationUnits->pluck('id')->all() : []);
        $selectedGuardianStudentIds = old('guardian_student_ids', $masterFormRecord instanceof \App\Models\User ? $masterFormRecord->guardianStudents->pluck('id')->all() : []);
    @endphp
    <label>Nama User <input name="name" required value="{{ $masterField('name') }}" placeholder="Nama lengkap user"></label>
    <label>Username <input name="username" required value="{{ $masterField('username') }}" placeholder="username"></label>
    <label>Email <input type="email" name="email" required value="{{ $masterField('email') }}" placeholder="email@domain.com"></label>
    <label>Role
        <select name="role" required>
            <option value="">Pilih Role</option>
            @foreach($roleOptions as $key => $label)<option value="{{ $key }}" @selected($masterField('role') === $key)>{{ $label }}</option>@endforeach
        </select>
    </label>
    <label class="span-2">Akses Unit Pendidikan (wajib untuk Petugas dan Bendahara)
        <select name="education_unit_ids[]" multiple size="5">
            @foreach($educationUnits as $unit)
                <option value="{{ $unit->id }}" @selected(in_array($unit->id, $selectedUserUnitIds))>{{ $unit->code }} - {{ $unit->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="span-2" data-guardian-student-picker data-search-url="{{ route('master.data-users.students.search') }}">Akses Wali Santri
        <input type="search" placeholder="Cari nama, NIS, atau NISN siswa..." autocomplete="off" data-guardian-student-search>
        <span class="student-search-results" data-guardian-student-results hidden></span>
        <select name="guardian_student_ids[]" multiple size="7" data-guardian-student-selected>
            @foreach($studentOptions as $student)
                <option value="{{ $student->id }}" @selected(in_array($student->id, $selectedGuardianStudentIds))>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>
            @endforeach
        </select>
        <small>Ketik minimal 2 karakter lalu pilih siswa. Gunakan Ctrl/Cmd untuk menghapus pilihan dari daftar.</small>
    </label>
    <label class="span-2">Password <input type="password" name="password" autocomplete="new-password" placeholder="Wajib saat tambah, kosongkan saat edit jika tidak diganti"></label>
    @break
@default
    @php
        $selectedDiscountStudentId = $masterField('student_id');
        $selectedDiscountSource = $masterField('source_type', 'spp');
        $selectedDiscountFeeTypeId = $masterField('fee_type_id');
    @endphp
    <div class="fee-discount-simple span-2">
        <div class="fee-discount-form-section fee-discount-student-section">
            <label class="fee-discount-simple-field fee-discount-student-field">
                <span>Siswa</span>
                <div class="student-search-picker" data-student-picker>
                    <input type="search" placeholder="Ketik nama siswa atau NIS..." autocomplete="off" required data-student-search>
                    <select name="student_id" required data-student-source aria-hidden="true" tabindex="-1"><option value="">Pilih Siswa</option>@foreach($studentOptions as $student)<option value="{{ $student->id }}" @selected((string) $selectedDiscountStudentId === (string) $student->id)>{{ $student->schoolClass?->educationUnit?->code ?? '-' }} - {{ $student->nis }} - {{ $student->name }}</option>@endforeach</select>
                    <div class="student-search-results" data-student-results hidden></div>
                </div>
                <small>Ketik minimal 2 huruf, lalu pilih siswa dari hasil pencarian.</small>
            </label>
        </div>

        <div class="fee-discount-form-section fee-discount-detail-section">
            @php
                $selectedDiscountPayment = $selectedDiscountSource === 'fee_type' && $selectedDiscountFeeTypeId
                    ? 'fee_type:'.$selectedDiscountFeeTypeId
                    : 'spp';
            @endphp
            <div class="fee-discount-detail-grid">
                <label class="fee-discount-simple-field">
                    <span>Kategori Pembayaran</span>
                    <select required data-discount-payment>
                        <option value="spp" @selected($selectedDiscountPayment === 'spp')>SPP</option>
                        @foreach($feeTypeOptions as $feeType)<option value="fee_type:{{ $feeType->id }}" @selected($selectedDiscountPayment === 'fee_type:'.$feeType->id)>{{ $feeType->name }}</option>@endforeach
                    </select>
                    <input type="hidden" name="source_type" value="{{ $selectedDiscountSource }}" data-discount-source>
                    <input type="hidden" name="fee_type_id" value="{{ $selectedDiscountFeeTypeId }}" data-discount-fee-type>
                </label>
                <label class="fee-discount-simple-field"><span>Jenis Keringanan</span><select name="discount_type" required data-discount-type><option value="amount" @selected($masterField('discount_type', 'amount') === 'amount')>Potongan Nominal</option><option value="percentage" @selected($masterField('discount_type') === 'percentage')>Potongan Persentase</option></select></label>
                <label class="fee-discount-simple-field"><span>Nilai Keringanan</span><input type="text" inputmode="numeric" name="discount_value" required value="{{ $masterField('discount_value') }}" placeholder="Contoh: 300.000 atau 50" data-discount-value data-currency-input></label>
                <label class="fee-discount-simple-field"><span>Tanggal Mulai</span><input type="date" name="start_date" required value="{{ $masterDate('start_date', now()->toDateString()) }}"></label>
                <label class="fee-discount-simple-field"><span>Tanggal Selesai</span><input type="date" name="end_date" value="{{ $masterDate('end_date') }}"></label>
                <label class="fee-discount-simple-field fee-discount-reason-field"><span>Alasan Keringanan</span><input name="reason" value="{{ $masterField('reason') }}" placeholder="Contoh: Beasiswa atau keringanan khusus"></label>
                <label class="switch-field fee-discount-simple-status"><input type="checkbox" name="is_active" value="1" @checked($masterIsActive)><span></span> Keringanan aktif</label>
            </div>
        </div>
    </div>
@endswitch
