# PROGRESS — Simulasi Ujian PKSK Tahun 6 Fasa 2

> Fail ini dikemas kini selepas SETIAP fasa. Sesi baharu boleh sambung kerja
> hanya dengan membaca fail ini — tidak perlu baca semula semua kod.

**Kemas kini terakhir:** 2026-07-25 · **Status:** SEMUA FASA SIAP (1–9) · 169 ujian lulus · UI disahkan dalam pelayar

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
tests/Feature/Admin/SoalanATest.php               17 ujian — CRUD A, skor, re-index, nyahaktif
tests/Feature/Admin/SoalanBTest.php               20 ujian — CRUD B, tepat-satu-betul, anjakan correctIndex
tests/Feature/Admin/ImportSoalanTest.php          20 ujian — import JSON, pengesahan, dedup, dry-run
tests/Feature/PapanPemukaTest.php                 15 ujian — papan pemuka ikut peranan
```

### Keadaan pengesahan semasa
```
php artisan migrate:fresh --seed                     ✅ bersih
php artisan test                                     ✅ 169 lulus, 560 assertion
vendor\bin\pint --parallel --test                    ✅ lulus
vendor\bin\phpstan analyse --memory-limit=1G         ✅ 0 ralat (level 7)
```

### Penanda aras prestasi (bank 630 soalan)
Cipta percubaan: **~6 ms, 6 kueri** — bilangan kueri tetap tanpa mengira saiz bank.
Pertindihan antara dua percubaan: purata **16.6 / 100** soalan.

Anggaran pertindihan mengikut saiz bank (`30²/A + 60²/bukanBI + 10²/BI`):

| Jumlah bank | A | bukan-BI | BI | Pertindihan |
|---|---|---|---|---|
| 130 (seeder) | 40 | 75 | 15 | ~72 / 100 |
| 300 | 90 | 175 | 35 | ~33 / 100 |
| 500 | 150 | 290 | 60 | ~20 / 100 |
| 800 | 240 | 460 | 100 | ~13 / 100 |

---

## ✅ FASA 4 — Admin CRUD (SIAP)

Dibina guna **Livewire 4 SFC + Flux**, BUKAN Filament. Tiada dependency baharu.

### Fail dicipta
```
app/Concerns/ManagesQuestions.php            state borang + sync opsyen + padam/nyahaktif
app/Livewire/Admin/QuestionBankPage.php      kelas ABSTRAK asas untuk kedua-dua skrin
tests/Feature/Admin/SoalanATest.php          17 ujian
tests/Feature/Admin/SoalanBTest.php          20 ujian
```

### Fail diubah (daripada stub kepada penuh)
```
resources/views/pages/admin/⚡soalan-a.blade.php
resources/views/pages/admin/⚡soalan-b.blade.php
```

### ⚠️ Kenapa ada kelas abstrak, bukan trait sahaja
Percubaan pertama letak semua logik dalam trait `ManagesQuestions` sahaja **gagal**
PHPStan: trait itu hanya digunakan dari `resources/` yang tidak dianalisis, jadi
PHPStan lapor `trait.unused` selama-lamanya. Penyelesaian: kelas abstrak
`App\Livewire\Admin\QuestionBankPage extends Component` yang `use ManagesQuestions`,
dan kedua-dua SFC `extends QuestionBankPage`. Kini semua logik benar-benar dianalisis
level 7. **Jangan pindahkan logik kembali ke dalam fail Blade.**

### Kontrak kelas abstrak
Setiap SFC WAJIB laksana empat kaedah ini:
```php
protected function section(): Section;              // A atau B
protected function subject(): ?Subject;             // null untuk A
protected function correctOptionIndex(): ?int;      // null untuk A
protected function validationRules(): array;
```
Kaedah pilihan untuk di-override: `beforeSave()` (semakan silang-baris),
`handleOptionRemoved(int $index)` (pembetulan selepas baris dibuang),
`resetForm()`, `fillFormFrom(Question)` — dua terakhir panggil `parent::` dahulu.

Tindakan yang disediakan percuma: `create()`, `edit(int)`, `save()`, `addOption()`,
`removeOption(int)`, `confirmDelete(int)`, `delete()`, `updatedSearch()`,
serta `#[Computed] questions()` (paginate 10, ditapis ikut section).

### Tiga perangkap — SEMUA sudah dikendalikan dan diuji
1. **Re-index selepas buang baris** — `ManagesQuestions::removeOption()` panggil
   `array_values()`. Diuji: `test_removing_an_option_reindexes_the_remaining_rows`.
2. **`wire:key` uid stabil** — setiap baris ada `'key' => Str::uuid()` dijana sekali
   semasa baris dicipta, bukan indeks gelung. Diuji: `test_every_option_row_has_a_stable_unique_key`.
3. **Anjakan `correctIndex`** — `handleOptionRemoved()` dalam `⚡soalan-b` kosongkan
   jika baris itu sendiri dibuang, kurangkan 1 jika baris sebelumnya dibuang.
   Diuji tiga kali, termasuk `test_the_shifted_selection_saves_the_intended_option`
   yang sahkan opsyen yang BETUL benar-benar tersimpan selepas anjakan.

### Keputusan reka bentuk
| Perkara | Cara |
|---|---|
| "Tepat satu betul" (B) | Satu skalar `public ?int $correctIndex` terikat `<flux:radio.group variant="cards">`. Kumpulan radio secara **struktur** tepat-satu — "sifar betul" dan "dua betul" tidak boleh diungkap dalam UI. Pertahanan payload: `Rule::in(array_keys($this->options))` |
| Skor Bahagian A | `options.*.score` integer 0–10; `beforeSave()` tolak jika tiada satu pun > 0 (jika tidak soalan senyap-senyap berhenti dikira dalam `maxA`) |
| Padam soalan | `removeOrDeactivate()` — semak `attemptQuestions()->exists()` DAHULU; jika terpakai → `is_active = false` + toast amaran; jika tidak → padam betul-betul |
| Min/maks opsyen | `MIN_OPTIONS = 4`, `MAX_OPTIONS = 6` |
| Sync opsyen | Baris ada `id` → update; tiada `id` → cipta; selebihnya `whereNotIn($keptIds)->delete()` |
| Modal | `Flux::modal('borang-soalan')->show()` / `->close()` dipanggil dari PHP, bukan `flux:modal.trigger` |

### Nota ujian (untuk sesi baharu)
- `questions` ialah `#[Computed]`, **bukan** view data — `viewData('questions')` akan
  gagal "Undefined array key". Guna `assertSee()` / `assertDontSee()` untuk uji senarai.
- `findOrFail()` dalam tindakan Livewire membaling `ModelNotFoundException`, bukan
  pulangkan status 404 — guna `expectException()`, bukan `assertStatus(404)`.

---

## ✅ FASA 7 — Navigasi ikut peranan (SIAP)

`resources/views/layouts/app/sidebar.blade.php` — dua kumpulan nav baharu selepas
"Platform" (fail ini TIADA penanda chisel):
- Admin → kumpulan **"Bank Soalan"**: Soalan Bahagian A, Soalan Bahagian B
- Pelajar → kumpulan **"Peperiksaan"**: Ujian PKSK

Guna `auth()->user()?->isAdmin()`. Diuji dalam `RoleAccessTest`:
`test_the_sidebar_shows_the_question_bank_only_to_admins` dan pasangannya untuk pelajar.

**Kemas kini kemudian:** pautan "Repository" dan "Documentation" starter kit dibuang
atas permintaan pengguna. Nav bawah `flux:spacer` kini kosong, tinggal menu pengguna.

---

## ✅ FASA 9 — Papan pemuka ikut peranan (SIAP)

Sebelum ini `dashboard` hanyalah placeholder starter kit (3 kotak `aspect-video`
kosong dengan corak SVG). Kini ia papan pemuka sebenar.

```
resources/views/pages/⚡dashboard.blade.php   BAHARU — halaman Livewire ikut peranan
app/Services/QuestionBankReport.php          BAHARU — health(), isReady(), subjectSpread(), counts()
app/Services/PelajarProgress.php             BAHARU — activeAttempt(), latestResult(), bestTotal(), dll
tests/Feature/PapanPemukaTest.php            BAHARU — 15 ujian
```

Fail diubah:
- `routes/web.php` — `Route::view('dashboard', ...)` → `Route::livewire('dashboard', 'pages::dashboard')`.
  Nama laluan `dashboard` KEKAL, jadi `DashboardTest` dan `⚡profile` masih berfungsi.
- `app/Services/QuestionImportResult.php` — statik `bankHealth()`/`subjectSpread()`
  **dipindah keluar** ke `QuestionBankReport`. Kelas ini kini hanya simpan hasil import.
- `app/Console/Commands/ImportQuestionsCommand.php` — guna `QuestionBankReport`.
- `resources/views/layouts/app/sidebar.blade.php` — buang Repository + Documentation.

### Kandungan
**Admin:** 4 kad kiraan (jumlah / aktif / tidak aktif / percubaan), jadual kesediaan
bank dengan `flux:progress` setiap kategori, amaran merah jika bank tidak cukup untuk
satu kertas, jadual taburan subjek B, pautan ke kedua-dua skrin soalan.

**Pelajar:** kad sambung ujian jika ada percubaan hidup (dengan baki masa), 3 kad
(ujian selesai / skor terbaik / skor terakhir), jadual 5 keputusan terkini, atau
keadaan kosong jika belum pernah menduduki ujian.

### Kenapa `QuestionBankReport` diasingkan
Arahan `soalan:import` DAN papan pemuka admin kedua-duanya perlukan angka yang sama.
Meletakkannya sebagai statik dalam `QuestionImportResult` (kelas hasil import) adalah
nama yang salah sebaik ia dikongsi. Kini satu kelas boleh disuntik, dua tempat guna,
nombor sentiasa sepadan.

### Fail lama dibuang
`resources/views/dashboard.blade.php` (placeholder starter kit) **telah dipadam** —
laluan kini menunjuk kepada SFC, jadi fail itu tidak lagi dimuatkan oleh apa-apa.
Menyimpan dua fail bernama "dashboard" hanya mengundang salah edit kemudian.

Kesan sampingan: `resources/views/components/placeholder-pattern.blade.php` kini
turut yatim (tiada rujukan tinggal dalam `resources/`, `app/`, atau `routes/`).

---

## ✅ FASA 8 — Import pukal JSON (SIAP)

Dibina untuk menyokong rancangan menjana 300–500 soalan dengan AI.

```
app/Console/Commands/ImportQuestionsCommand.php   arahan soalan:import
app/Services/QuestionImporter.php                 pengesahan + kegigihan
app/Services/QuestionImportResult.php             kiraan, ralat, kesihatan bank
database/data/contoh-import-soalan.json           templat + peraturan untuk AI
tests/Feature/Admin/ImportSoalanTest.php          20 ujian
```

### Penggunaan
```bash
php artisan soalan:import fail.json --dry-run   # sahkan sahaja, tiada tulisan
php artisan soalan:import fail.json             # import sebagai TIDAK AKTIF (lalai)
php artisan soalan:import fail.json --aktif     # import terus aktif
```

### Format JSON
Sama bentuk dengan `SoalanA::all()` / `SoalanB::all()` supaya konsisten:
```json
{
  "section_a": [{ "text": "...", "options": [{ "text": "...", "score": 3 }] }],
  "section_b": [{ "subject": "iq", "text": "...", "options": ["a","b","c","d"], "correct": 1 }]
}
```
Kedua-dua kunci pilihan. Kunci bermula `_` diabaikan (templat guna ini untuk nota).

### Keputusan reka bentuk
| Perkara | Cara | Sebab |
|---|---|---|
| **Semua-atau-tiada** | Satu ralat → seluruh fail ditolak, sifar ditulis | Fail separa-import lebih susah difahami daripada yang ditolak; admin betulkan dan jalankan semula |
| **Lalai TIDAK AKTIF** | `is_active = false` melainkan `--aktif` | Output AI perlu disemak manusia dahulu; `QuestionSelector` memang abaikan soalan tidak aktif |
| **Pengesahan sama dengan UI** | Min/maks 4–6 pilihan, satu jawapan betul, sekurang-kurangnya satu skor > 0 | Kalau import boleh langkau invarian ini, bank rosak senyap sehingga hari peperiksaan |
| **Dedup** | Ikut `question_text` — dalam fail dan terhadap DB | Jalankan semula fail yang sama tidak menggandakan |
| **Laporan** | Jadual kesediaan bank + taburan subjek B | Membuat ketidakseimbangan subjek KELIHATAN tanpa mengubah semantik peperiksaan |

### ⚠️ Konstant dipindahkan
`MIN_OPTIONS` / `MAX_OPTIONS` **dipindah dari trait `ManagesQuestions` ke model `Question`**
(`Question::MIN_OPTIONS`). Sebab: PHP tidak benarkan akses konstant melalui nama trait
(`classConstant.onTrait`), dan had ini milik model domain, bukan concern UI.
Semua rujukan (trait, dua Blade admin, pengimport) sudah dikemas kini.

### Kuota subjek Bahagian B — SENGAJA TIDAK DIUBAH
60 soalan bukan-BI masih ditarik **bercampur** daripada 4 subjek tanpa kuota per-subjek,
seperti spesifikasi asal. Ini bermakna bank yang berat sebelah menghasilkan kertas yang
berat sebelah. Ini keputusan **produk** (keadilan peperiksaan), bukan teknikal — jadi
ia tidak diubah sendiri. Laporan `soalan:import` kini memaparkan taburan supaya admin
nampak. Jika kuota per-subjek dikehendaki, ubah `QuestionSelector::select()` — tambah
satu panggilan `pick()` bagi setiap subjek.

---

## Nota untuk sesi baharu

- Fail `⚡` **jangan** dicipta melalui PowerShell (U+26A1 akan rosak) —
  guna `php artisan make:livewire pages::admin.soalan-a`
- Jangan sentuh: `.env`, `composer.json`, `composer.lock`, `config/fortify.php`, view auth
- Jangan buang penanda `/* @chisel-* */` dalam fail starter kit
- Closure eager-load bersarang terima `Relation`, **bukan** `Builder` — jangan taip
  `Builder` pada `->with(['rel' => fn ($q) => …])` atau ia akan `TypeError`
- Log masuk ujian: `admin@example.com` / `pelajar@example.com`, kata laluan `password`
