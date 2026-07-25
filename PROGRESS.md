# PROGRESS — Simulasi Ujian PKSK Tahun 6 Fasa 2

> Fail ini dikemas kini selepas SETIAP fasa. Sesi baharu boleh sambung kerja
> hanya dengan membaca fail ini — tidak perlu baca semula semua kod.

**Kemas kini terakhir:** 2026-07-25 · **Status:** Fasa 1–3 + 5 SIAP · 95 ujian lulus

---

## ⚠️ PENTING — Dua penyimpangan daripada brief asal (sudah diluluskan pengguna)

Brief asal menganggap projek ini Laravel 12 + Livewire 3 + Breeze + Filament + Pest.
**Semua anggapan itu salah.** Keadaan sebenar telah disahkan:

| Brief anggap | Realiti projek |
|---|---|
| Livewire 3 | **Livewire 4.3.3** — komponen SFC `⚡`, `Route::livewire()` |
| Filament dipasang | **Tidak dipasang langsung** |
| Pest | **Tidak dipasang** — PHPUnit 12.5, gaya kelas |
| Breeze | **Fortify 1.37 + Flux UI 2.15** (`laravel/livewire-starter-kit`) |
| Git sedia ada | **Tiada `.git`** — baru di-`init` dalam sesi ini |

Pengguna telah memilih (pada 2026-07-25):

1. **TIADA dependency composer/npm baharu.** Admin CRUD dibina guna **Livewire 4 SFC + Flux**, BUKAN Filament.
2. **Ujian guna PHPUnit 12 gaya kelas**, BUKAN Pest.
3. Redirect ikut peranan = bind `LoginResponse` Fortify.
4. Soalan Bahagian B dikocok bercampur pada posisi 31–100 (Bahasa Inggeris TIDAK berblok).
5. Percubaan ulangan tanpa had.

**Kesan pada penomboran fasa yang diminta:** fasa "(4) Filament resources" sebenarnya
ialah **"(4) Admin CRUD Livewire + Flux"**, dan "(6) Pest tests" ialah
**"(6) PHPUnit tests"**. Jangan pasang Filament atau Pest.

---

## Persekitaran

- Laravel **13.22**, PHP **8.4**, SQLite (`database/database.sqlite`)
- **PHPStan level 7** (larastan) meliputi `app/ bootstrap/app.php config/ database/ routes/` — **bukan** `resources/`
- Pint preset `laravel`. `composer test` = pint + phpstan + `artisan test`
- ⚠️ PHPStan perlu `--memory-limit=1G` (had lalai 128M tidak mencukupi). Jalankan:
  `vendor\bin\phpstan analyse --no-progress --memory-limit=1G`
- ⚠️ `node_modules` dan `public/build/` TIADA. `tests/TestCase.php` panggil `withoutVite()`
  supaya ujian render penuh tidak gagal. Untuk guna aplikasi sebenar: `npm install && npm run build`.

## Prinsip seni bina

PHPStan tidak menganalisis `resources/`. Maka **semua logik perniagaan diletak dalam `app/`**
(services, concerns, enums); setiap SFC Livewire hanyalah adapter nipis — satu panggilan
ke service yang dianalisis, ditambah hal UI (toast/redirect).

---

## ✅ FASA 1 — Migrations + Models (SIAP)

### Fail dicipta

```
app/Enums/Role.php              pelajar | admin
app/Enums/Section.php           A | B          + label(), fullLabel()
app/Enums/Subject.php           5 kes         + label(), selainBahasaInggeris(), values()
app/Enums/AttemptStatus.php     in_progress | submitted | expired + label(), badgeColor()

database/migrations/2026_07_25_100000_add_role_to_users_table.php
database/migrations/2026_07_25_100100_create_questions_table.php
database/migrations/2026_07_25_100200_create_options_table.php
database/migrations/2026_07_25_100300_create_attempts_table.php
database/migrations/2026_07_25_100400_create_attempt_questions_table.php

app/Models/Question.php  app/Models/Option.php
app/Models/Attempt.php   app/Models/AttemptQuestion.php

database/factories/QuestionFactory.php   sectionA(), sectionB(?Subject), inactive()
database/factories/OptionFactory.php     correct(), withScore(int)
database/factories/AttemptFactory.php    expired()
```

### Fail diubah
- `app/Models/User.php` — tambah `@property Role $role`, cast `'role' => Role::class`,
  kaedah `isAdmin()`, `isPelajar()`, relasi `attempts()`
- `database/factories/UserFactory.php` — tambah state `admin()` dan `pelajar()`

### Keputusan reka bentuk (JANGAN ubah tanpa sebab)

| Keputusan | Sebab |
|---|---|
| `users.role` = `string(16)` default `'pelajar'`, **bukan** `enum()` | SQLite tidak boleh `ALTER` CHECK constraint; `users` paling mungkin diubah lagi |
| `questions.section`, `questions.subject`, `attempts.status` guna `enum()` | SQLite hasilkan `varchar + CHECK`; `migrate:fresh` bersih, integriti percuma |
| `attempt_questions.question_id` = **`restrictOnDelete`** | Cascade akan senyap-senyap padam baris percubaan lampau dan tukar skor sejarah |
| `attempt_questions.selected_option_id` = `nullOnDelete` | Padam pilihan → pilihan pelajar kosong, bukan yatim |
| Skor di-cast `'float'`, **BUKAN `'decimal:2'`** | `decimal:` pulangkan *string* → rosakkan aritmetik + `assertSame(20.0, …)` gagal. Lajur kekal `decimal(5,2)`; pembundaran dibuat dalam service |
| `role` **TIDAK** dimasukkan ke `#[Fillable]` User | `Factory::makeInstance()` jalan `unguarded`, jadi factory tetap berfungsi; tiada laluan mass-assignment boleh naik taraf pendaftaran jadi admin |
| `attempt_id` / `user_id` tiada dalam `#[Fillable]` | Sentiasa dicipta melalui relasi (`$user->attempts()->create(...)`) |

### ⚠️ Perangkap level 7 yang WAJIB dipatuhi
`now()` bertaip `CarbonInterface`, jadi `$attempt->expires_at = now()->addMinutes(90)`
akan **gagal** PHPStan terhadap `@property Carbon`. **Jangan sesekali assign tarikh
terus ke property** — sentiasa guna `create([...])` / `update([...])` / `forceFill([...])`.

---

## ✅ FASA 2 — Attempt Service: pemilihan rawak + pemarkahan (SIAP)

### Fail dicipta

```
app/Exceptions/InsufficientQuestionBankException.php   ::for(Section, ?Subject, needed, available)
app/Exceptions/AttemptExpiredException.php
app/Exceptions/AttemptNotActiveException.php
app/Exceptions/InvalidOptionException.php

app/Services/QuestionSelector.php
app/Services/ScoringService.php
app/Services/AttemptService.php
```

### `QuestionSelector`
Pemalar: `SECTION_A_COUNT=30`, `SECTION_B_OTHER_COUNT=60`, `BAHASA_INGGERIS_COUNT=10`,
`SECTION_B_COUNT=70`, `TOTAL=100`.

`select(): array<int,int>` — **tiga** kueri `inRandomOrder()` berasingan (satu kueri
tidak boleh ungkap "60 daripada subjek ini DAN 10 daripada subjek itu"):
- Posisi **1–30** = Bahagian A
- Posisi **31–100** = 60 bukan-BI + 10 BI, **`shuffle()` bercampur** (keputusan #4)

Baling `InsufficientQuestionBankException` **sebelum** sebarang baris ditulis.

### `ScoringService` — FORMULA RASMI
```
score_a     = maxA > 0 ? round((earnedA / maxA) * 20, 2) : 0.0
score_b     = totalB > 0 ? round((correctB / totalB) * 70, 2) : 0.0
score_total = round(score_a + score_b, 2)        // jumlah komponen yang SUDAH dibundar
```
- `maxA` = jumlah skor tertinggi setiap soalan A yang ditarik (bukan yang dijawab sahaja)
- Soalan tidak dijawab: 0 pada pengangka, **tetapi tetap dikira pada penyebut**
- Guna `totalB` sebenar (bukan pemalar 70) supaya tidak mungkin bahagi sifar
- Jumlah dikira daripada nilai yang sudah dibundar supaya paparan A + B sentiasa = paparan Jumlah
- `sectionBBreakdown()` → `array<string, array{subject, correct, total, percent}>`, subjek tanpa soalan digugurkan

### `AttemptService` — `DURATION_MINUTES = 90`
```php
startOrResume(User): Attempt          // throws InsufficientQuestionBankException
activeAttempt(User): ?Attempt
assertActive(Attempt): void           // throws AttemptExpired|AttemptNotActive
saveAnswer(Attempt, int $position, int $optionId): void
toggleFlag(Attempt, int $position): bool
submit(Attempt): Attempt
expire(Attempt): Attempt
```

**Tingkah laku kritikal:**
- `startOrResume` — sambung percubaan `in_progress` yang belum luput; jika sudah luput,
  **finalise dulu (`expire`) kemudian cipta yang baharu**. Sisip pukal 100 baris
  (`insert()` memintas model events → timestamp dibekalkan manual).
- `assertActive` — **satu-satunya titik sekatan** untuk semua tulisan. Jika luput:
  panggil `expire()` (markah apa yang sempat disimpan) **kemudian baru** baling.
- `saveAnswer` — **WAJIB** sahkan opsyen milik soalan itu (`Option::whereKey()->where('question_id', …)->exists()`).
  **Ini semakan keselamatan paling penting dalam aplikasi** — tanpanya pelajar boleh
  sunting payload Livewire dan hantar opsyen skor tertinggi dalam seluruh bank untuk semua 30 soalan A.
- `submit`/`expire` — baca semula bawah `lockForUpdate()` dalam transaksi, pulang awal
  jika status bukan `in_progress` lagi → **idempotent**, selamat daripada auto-submit
  berlumba dengan klik "Hantar".
- `expire()` set `submitted_at = expires_at` (masa tamat sebenar, bukan bila kelewatan disedari).

---

## ✅ FASA 3 — Halaman ujian Livewire + pemasa (SIAP)

### Fail dicipta
```
resources/views/pages/ujian/⚡mula.blade.php         Halaman mula + arahan + keputusan lepas
resources/views/pages/ujian/⚡jawab.blade.php        Halaman ujian utama
resources/views/pages/ujian/⚡keputusan.blade.php    Halaman keputusan

app/Http/Middleware/EnsureUserHasRole.php      variadic: role:pelajar / role:admin
app/Concerns/RedirectsUsersByRole.php          homeFor(?Authenticatable): string
app/Http/Responses/LoginResponse.php
app/Http/Responses/TwoFactorLoginResponse.php  (2FA memintas LoginResponse — perlu kedua-duanya)
```

### Fail diubah
- `bootstrap/app.php` — alias `'role' => EnsureUserHasRole::class`
- `routes/web.php` — kumpulan `role:pelajar` (ujian.*) dan `role:admin` (admin.*)
- `app/Providers/FortifyServiceProvider.php` — bind kedua-dua contract dalam `register()`
  (penanda `@chisel-*` dikekalkan)

### Nama laluan
```
ujian.mula        GET  /ujian
ujian.jawab       GET  /ujian/{attempt}
ujian.keputusan   GET  /ujian/{attempt}/keputusan
admin.soalan-a    GET  /admin/soalan-a      ← BELUM DIBINA (Fasa 4)
admin.soalan-b    GET  /admin/soalan-b      ← BELUM DIBINA (Fasa 4)
```

### Keputusan reka bentuk kritikal

**1. Kebocoran kunci jawapan — dicegah pada peringkat KUERI, bukan template.**
`⚡jawab` memuat opsyen dengan senarai lajur eksplisit:
```php
'question.options' => fn ($query) => $query->select('id', 'question_id', 'option_text')->orderBy('id'),
```
Memuat model `Option` penuh akan meletakkan `score` dan `is_correct` dalam ingatan —
satu `@dump` atau `@js()` cuai membocorkan seluruh kunci jawapan ke sumber halaman.
Ada ujian regresi khusus untuk ini. **JANGAN buang `->select(...)` itu.**

**2. Pemasa berwibawa di pelayan.** `expires_at` disimpan pada percubaan. Pulau Alpine
`wire:ignore` disemai daripada `@js($secondsRemaining)` sahaja — klien **tidak pernah**
diberitahu `expires_at`. `wire:ignore` WAJIB, jika tidak morph DOM Livewire akan reset
jam setiap tindakan. Selepas setiap tindakan komponen `dispatch('masa-dikemaskini')`
untuk selaraskan hanyutan `setInterval`.

**3. Disiplin payload.** `answered` / `flagged` ialah `array<int,bool>` **berkunci posisi**
(carian `isset()` O(1), payload kecil). `#[Locked]` pada `attemptId`, `answered`, `flagged`.
**Jangan sesekali** letak model `AttemptQuestion` dalam property awam.

**4. Palet 100 butang** guna `<button>` asli dalam `@for`, BUKAN 100 `<flux:button>`
(setiap komponen Flux ialah render Blade penuh; halaman ini render semula setiap jawapan).

**5. Milik siapa?** Route-model binding **tidak** semak pemilikan —
setiap komponen ujian `abort_unless($attempt->user_id === Auth::id(), 403)` dalam `mount()`.

**6. Halaman keputusan** tapis Bahagian B **dalam kueri** (`whereHas('question', … Section::B)`),
jadi baris Bahagian A tidak pernah diambil dan tiada silap template boleh membocorkannya.

### Kemas kini ujian sedia ada
- `tests/TestCase.php` — tambah `setUp()` dengan `withoutVite()`
  (kegagalan manifest Vite ini **sedia ada**, bukan daripada kod baharu — `DashboardTest`
  gagal sama pada starter kit asal kerana `node_modules`/`public/build` tiada)
- `tests/Feature/Auth/AuthenticationTest.php` — jangkaan redirect `dashboard` → `ujian.mula`
  (perubahan tingkah laku yang memang diluluskan)

---

## ✅ FASA 5 — Seeder (SIAP, dibuat awal kerana Fasa 2 perlukannya)

```
database/seeders/Data/SoalanA.php    40 soalan situasi BM, 4 opsyen skor 3/2/1/0
database/seeders/Data/SoalanB.php    90 soalan: iq 20, matematik 19, pengetahuan_am 18,
                                     sains 18, bahasa_inggeris 15
database/seeders/QuestionSeeder.php  gelung + shuffle
```
`database/seeders/DatabaseSeeder.php` diubah — tambah `admin@example.com` (admin),
`pelajar@example.com` (pelajar), dan panggil `QuestionSeeder`. Baris `Test User` asal dikekalkan.

**Kolam vs kuota:** bukan-BI 75 ≥ 60 ✓ · BI 15 ≥ 10 ✓ — kedua-dua melebihi cabutan,
jadi percubaan berturut-turut benar-benar berbeza.

**Seeder mengocok susunan opsyen** dan menjejak jawapan betul melalui **teks**, bukan indeks —
tanpa ini jawapan betul sentiasa di kedudukan sama dan boleh diteka. Taburan disahkan: `[23, 21, 26, 20]`.

Kesahihan disahkan: setiap soalan A ada sekurang-kurangnya satu opsyen skor > 0;
setiap soalan B ada **tepat satu** `is_correct`; semua ada ≥ 4 opsyen.

---

## ✅ FASA 6 (sebahagian) — Ujian PHPUnit

```
tests/Concerns/SeedsQuestionBank.php              seedQuestionBank(), makeSectionAQuestion(), makeSectionBQuestion()
tests/Feature/Ujian/QuestionSelectionTest.php     14 ujian — kuota, posisi, kekalan susunan, resume, bank tak cukup
tests/Feature/Ujian/ScoringTest.php               13 ujian — 13.33 / 42.00 / 55.33, siling 20/70/90, bahagi sifar
tests/Feature/Ujian/ExamTimerTest.php             10 ujian — penolakan selepas luput, idempotency, kiraan detik
tests/Feature/Ujian/ExamPageTest.php              17 ujian — simpan serta-merta, palet, kebocoran skor, keizinan
tests/Feature/RoleAccessTest.php                   8 ujian — middleware peranan + redirect log masuk
```

### Keadaan pengesahan semasa
```
php artisan migrate:fresh --seed                     ✅ bersih
php artisan test                                     ✅ 95 lulus, 345 assertion
vendor\bin\pint --parallel --test                    ✅ lulus
vendor\bin\phpstan analyse --memory-limit=1G         ✅ 0 ralat (level 7)
```

---

## ⏭️ FASA 4 — Admin CRUD (BELUM DIBUAT — kerja seterusnya)

> Ingat: **Livewire 4 SFC + Flux**, BUKAN Filament. Tiada dependency baharu.

Fail placeholder SUDAH dijana oleh `php artisan make:livewire` tetapi masih kandungan stub:
```
resources/views/pages/admin/⚡soalan-a.blade.php   ← stub, perlu ditulis
resources/views/pages/admin/⚡soalan-b.blade.php   ← stub, perlu ditulis
```
Laluan `admin.soalan-a` / `admin.soalan-b` **sudah wujud** dalam `routes/web.php` dan
sudah dilindungi `role:admin`. `RoleAccessTest` sudah sahkan pelajar dapat 403.

### Perlu dicipta
**`app/Concerns/ManagesQuestions.php`** — semua peraturan pengesahan + kegigihan di sini,
supaya logik admin berada dalam `app/` yang dianalisis PHPStan (ikut corak
`app/Concerns/ProfileValidationRules.php` yang sedia ada).

### Spesifikasi Bahagian A
- Medan: `question_text`, `is_active`, senarai opsyen (min 4, maks 6), setiap satu ada `skor`
- `subject` mesti `null`, semua `is_correct` mesti `false`
- Peraturan silang-baris yang peraturan array biasa tidak boleh ungkap:
  **sekurang-kurangnya satu opsyen mesti berskor > 0**, jika tidak `ScoringService`
  sumbang 0 kepada `maxA` dan soalan itu senyap-senyap berhenti dikira

### Spesifikasi Bahagian B
- Medan: `question_text`, `subject` (wajib), `is_active`, opsyen (min 4, maks 6)
- **"Tepat satu betul"**: JANGAN guna array checkbox `is_correct`. Guna **satu skalar**
  `public ?int $correctIndex` terikat pada `<flux:radio.group variant="cards">`
  (disahkan wujud dalam Flux percuma). Kumpulan radio secara *struktur* tepat-satu —
  keadaan tidak sah tidak boleh diungkap dalam UI langsung.
  Pertahanan pelayan: `Rule::in(array_keys($this->options))`.
  Semasa simpan: `is_correct = ($index === $this->correctIndex)`.

### ⚠️ Tiga perangkap yang WAJIB dikendalikan
1. Selepas `unset($this->options[$i])` **mesti** `array_values()`, jika tidak
   `wire:model="options.3…"` terikat pada lubang dan Livewire senyap-senyap buang input.
2. `wire:key` mesti **uid stabil setiap baris** (`Str::uuid()` semasa baris dicipta),
   BUKAN indeks gelung. Kunci indeks → padam baris tengah → morphdom bawa DOM basi
   ke baris salah (gejala klasik: padam opsyen 2, teks opsyen 3 hilang).
3. `removeOption()` mesti betulkan `correctIndex`: kosongkan jika baris itu sendiri,
   kurangkan 1 jika baris sebelumnya dipadam. Tanpa ini, padam opsyen 1 senyap-senyap
   pindahkan jawapan betul ke opsyen lain.

### Padam soalan
FK ialah `restrictOnDelete`. Semak `$question->attemptQuestions()->exists()` DAHULU:
- jika sudah digunakan → `update(['is_active' => false])` + toast amaran
- jika belum → padam sebenar

### Senarai
`flux:table` + `flux:pagination`, trait `Livewire\WithPagination`, `#[Computed] questions()`
paginate 10, carian `wire:model.live.debounce.300ms` + `updatedSearch() { $this->resetPage(); }`
(tanpa ini, mencari dari halaman 3 papar senarai kosong). Borang dalam `flux:modal`
(ikut corak `resources/views/pages/settings/⚡delete-user-modal.blade.php`).

### Ujian yang perlu ditulis
`tests/Feature/Admin/SoalanATest.php` dan `SoalanBTest.php` — cipta soalan A berskor;
min 4 opsyen; wajib ada skor > 0; sync opsyen semasa edit tanpa yatim; padam soalan
terpakai → nyahaktif. Untuk B: tepat satu `is_correct`; subjek wajib; `correctIndex`
wajib + julat; buang baris betul → kosongkan; buang baris sebelum → anjak indeks.

---

## ⏭️ FASA 7 — Kemasan akhir (BELUM DIBUAT)

- `resources/views/layouts/app/sidebar.blade.php` — tambah item nav ikut peranan
  ke dalam kumpulan "Platform" sedia ada (fail ini TIADA penanda chisel, selamat diedit):
  admin → dua halaman soalan; pelajar → "Ujian PKSK"
- Semak semula semua teks BM
- Jalankan `composer test` penuh

---

## Nota untuk sesi baharu

- Fail `⚡` **jangan** dicipta melalui PowerShell (U+26A1 akan rosak) —
  guna `php artisan make:livewire pages::admin.soalan-a`
- Jangan sentuh: `.env`, `composer.json`, `composer.lock`, `config/fortify.php`, view auth
- Jangan buang penanda `/* @chisel-* */` dalam fail starter kit
- Closure eager-load bersarang terima `Relation`, **bukan** `Builder` — jangan taip
  `Builder` pada `->with(['rel' => fn ($q) => …])` atau ia akan `TypeError`
- Log masuk ujian: `admin@example.com` / `pelajar@example.com`, kata laluan `password`
