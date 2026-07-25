# CLAUDE.md

Simulasi peperiksaan **PKSK Tahun 6 Fasa 2** — satu sesi 90 minit, 100 soalan.

> Fail ini = cara bekerja dalam repo ini (konvensyen, arahan, perangkap).
> Untuk **status fasa dan sejarah keputusan**, baca [PROGRESS.md](PROGRESS.md).

---

## ⚠️ Baca dahulu: stack sebenar ≠ apa yang orang sangka

Brief asal projek ini salah pada lima perkara. Sahkan sebelum menulis kod:

| Sangkaan biasa | Realiti |
|---|---|
| Livewire 3 | **Livewire 4.3** — SFC `⚡`, `Route::livewire()` |
| Breeze | **Fortify + Flux UI 2** (`laravel/livewire-starter-kit`) |
| Filament untuk admin | **Tiada Filament.** Admin = Livewire + Flux |
| Pest | **PHPUnit 12**, ujian gaya kelas |
| Laravel 12 | **Laravel 13.22**, PHP 8.4 |

**Jangan pasang Filament atau Pest.** Pengguna sudah memilih untuk tidak menambah
sebarang dependency composer/npm. Tanya dahulu sebelum menambah apa-apa.

---

## Arahan

```bash
php artisan test                                    # 169 ujian
php artisan test --filter NamaUjian                 # satu kelas
php artisan migrate:fresh --seed                    # bina semula DB + 130 soalan
php artisan soalan:import fail.json --dry-run       # sahkan fail import

vendor\bin\pint --parallel                          # format
vendor\bin\phpstan analyse --memory-limit=1G        # WAJIB flag memori
```

### ⚠️ Perangkap persekitaran (Windows + Herd)

1. **PHPStan perlu `--memory-limit=1G`.** Had lalai 128M tidak cukup dan ia gagal
   dengan "Allowed memory size exhausted", bukan dengan ralat analisis.
2. **`npm` mesti dijalankan dalam Git Bash.** Cylance Script Control menyekat npm
   melalui PowerShell (`exit code 34`).
3. **`php` / `artisan` mesti dijalankan dalam PowerShell.** `php` ialah `.bat` Herd
   dan tidak berada dalam PATH Git Bash (`exit code 127`).
4. **Fail `⚡` jangan dicipta melalui shell** — U+26A1 akan rosak. Guna
   `php artisan make:livewire pages::nama`.
5. **Mesej commit berbilang baris**: tulis ke fail dengan alat Write, kemudian
   `git commit -F fail`. Here-string PowerShell 5.1 rosak bila ada petikan, dan
   `Set-Content -Encoding utf8` menambah BOM ke dalam mesej commit.

### URL & log masuk
`http://latihan-pksk.test` (Herd sudah park `~/Desktop/Herd`)

| Emel | Kata laluan | Peranan |
|---|---|---|
| `admin@example.com` | `password` | admin |
| `pelajar@example.com` | `password` | pelajar |

---

## Peraturan seni bina

### 1. Logik dalam `app/`, Blade kekal nipis
PHPStan level 7 mengimbas `app/ bootstrap/app.php config/ database/ routes/` —
**bukan `resources/`**. Jadi semua logik mesti berada dalam `app/`; setiap SFC
hanyalah adapter (satu panggilan service + hal UI).

Bila logik dikongsi antara SFC, jangan letak dalam trait sahaja — PHPStan akan
lapor `trait.unused` kerana ia hanya diguna dari `resources/`. Guna **kelas abstrak**
dalam `app/Livewire/` yang di-`extends` oleh SFC. Contoh sedia ada:
`app/Livewire/Admin/QuestionBankPage.php`.

### 2. Konvensyen Livewire 4
- Halaman = SFC di `resources/views/pages/⚡nama.blade.php`
- Laluan = `Route::livewire('path', 'pages::nama')`
- Ujian = `Livewire::test('pages::nama', ['param' => $x])`
- Alpine sudah terkandung dalam Livewire — **jangan** tambah pakej `alpinejs`

### 3. Penanda Chisel
Beberapa fail starter kit ada `/* @chisel-XXX */ ... /* @end-chisel-XXX */`.
Laravel Chisel membuang kod antara penanda bila ciri dimatikan.
**Jangan padam penanda ini.** Fail terlibat: `FortifyServiceProvider`,
`UserFactory`, `routes/settings.php`, view auth.

---

## Peraturan domain (jangan ubah tanpa arahan)

### Formula pemarkahan — `app/Services/ScoringService.php`
```
score_a     = maxA > 0 ? round((earnedA / maxA) * 20, 2) : 0.0
score_b     = totalB > 0 ? round((correctB / totalB) * 70, 2) : 0.0
score_total = round(score_a + score_b, 2)     // jumlah komponen yang SUDAH dibundar
```
- `maxA` = jumlah skor tertinggi setiap soalan A yang **ditarik**, bukan yang dijawab
- Soalan tidak dijawab: 0 pada pengangka, **tetap dikira** pada penyebut
- Jumlah dikira dari nilai yang sudah dibundar supaya paparan A + B = paparan Jumlah

### Kuota pemilihan — `app/Services/QuestionSelector.php`
- Posisi **1–30** = Bahagian A
- Posisi **31–100** = 60 bukan-BI + 10 Bahasa Inggeris, **dikocok bercampur**
- Subjek B **tiada kuota per-subjek** — bank berat sebelah = kertas berat sebelah.
  Ini keputusan produk; papan pemuka admin memaparkan taburan supaya ia kelihatan.

### Pemasa berwibawa di pelayan
`expires_at` disimpan pada percubaan. Alpine hanya **memaparkan** kiraan yang
disemai dari pelayan; klien tidak pernah diberitahu `expires_at`.
`AttemptService::assertActive()` ialah satu-satunya titik sekatan untuk semua
tulisan — jika luput, ia `expire()` dahulu (markah apa yang sempat disimpan)
**kemudian** baling. Jangan pintas.

---

## Invarian keselamatan — jangan pecahkan

1. **Kunci jawapan tidak boleh sampai ke DOM.** `⚡jawab` memuat opsyen dengan
   `->select('id', 'question_id', 'option_text')`. Memuat model `Option` penuh
   meletakkan `score` dan `is_correct` dalam ingatan di mana satu `@dump` atau
   `@js()` cuai membocorkan segalanya. Ada ujian regresi.
2. **Pemilikan mesti disemak dalam `mount()`** — route-model binding tidak
   menyemaknya: `abort_unless($attempt->user_id === Auth::id(), 403)`.
3. **`saveAnswer()` mesti sahkan opsyen milik soalan itu.** Tanpanya pelajar boleh
   sunting payload Livewire dan hantar opsyen berskor tertinggi dalam seluruh bank
   untuk semua 30 soalan A. Ini semakan paling penting dalam aplikasi.
4. **Halaman keputusan menapis Bahagian B dalam kueri**, bukan dalam template —
   baris A tidak pernah diambil, jadi tiada silap template boleh membocorkannya.
5. **`role` bukan `#[Fillable]`** pada `User`. Factory jalan `unguarded` jadi ia
   tetap berfungsi, tetapi tiada laluan mass-assignment boleh naik taraf pendaftaran
   menjadi admin.

---

## Perangkap kod yang pernah memakan masa

| Perangkap | Penyelesaian |
|---|---|
| `now()` bertaip `CarbonInterface`, gagal level 7 bila di-assign ke `@property Carbon` | Jangan assign tarikh terus ke property; guna `create()` / `update()` / `forceFill()` |
| Cast `decimal:2` pulangkan **string** | Guna cast `'float'`; bundarkan dalam service |
| Konstant pada trait tak boleh diakses melalui nama trait | `Question::MIN_OPTIONS` / `MAX_OPTIONS` duduk pada model |
| Closure eager-load bersarang terima `Relation`, bukan `Builder` | Jangan taip `Builder` pada `->with(['rel' => fn ($q) => ...])` |
| `unset($this->options[$i])` tinggalkan lubang; `wire:model` senyap-senyap gagal | Sentiasa `array_values()` selepas buang |
| `wire:key` guna indeks gelung merosakkan DOM bila baris tengah dibuang | Guna uid stabil (`Str::uuid()` semasa baris dicipta) |
| Buang baris menganjak `correctIndex` | `handleOptionRemoved()` betulkan: kosongkan jika baris itu, tolak 1 jika selepasnya |
| `#[Computed]` bukan view data | Ujian guna `assertSee()`, bukan `viewData()` |
| `abort()` / `findOrFail()` dalam tindakan Livewire **membaling** | Guna `expectException()` atau `$this->get(...)->assertForbidden()` |
| PHPStan cache nilai pulangan kaedah tulen merentas mutasi | Baca property terus (`$result->errors !== []`) |
| Manifest Vite tiada semasa ujian | `tests/TestCase.php` panggil `withoutVite()` |

---

## Struktur

```
app/
  Enums/          Role, Section, Subject, AttemptStatus (backed string)
  Services/       QuestionSelector, ScoringService, AttemptService,
                  QuestionImporter, QuestionBankReport, PelajarProgress
  Livewire/Admin/ QuestionBankPage (kelas abstrak asas)
  Concerns/       ManagesQuestions, RedirectsUsersByRole
  Console/Commands/ImportQuestionsCommand   (soalan:import)

resources/views/pages/
  ⚡dashboard.blade.php
  ujian/    ⚡mula ⚡jawab ⚡keputusan
  admin/    ⚡soalan-a ⚡soalan-b

database/
  data/contoh-import-soalan.json    templat + peraturan untuk penjanaan AI
  seeders/Data/SoalanA.php, SoalanB.php
```

### Laluan
```
dashboard         /dashboard                    auth, verified
ujian.mula        /ujian                        role:pelajar
ujian.jawab       /ujian/{attempt}              role:pelajar
ujian.keputusan   /ujian/{attempt}/keputusan    role:pelajar
admin.soalan-a    /admin/soalan-a               role:admin
admin.soalan-b    /admin/soalan-b               role:admin
```

---

## Menambah soalan secara pukal

```bash
php artisan soalan:import fail.json --dry-run   # sahkan dahulu
php artisan soalan:import fail.json             # import sebagai TIDAK AKTIF
```

Import lalai **tidak aktif** supaya soalan janaan AI disemak manusia dahulu;
`QuestionSelector` hanya menarik soalan aktif. Satu ralat membatalkan seluruh fail.
Format dan peraturan penuh ada dalam `database/data/contoh-import-soalan.json`.

**Saiz bank vs kepelbagaian** (pertindihan antara dua percubaan):
130 soalan → ~72/100 · 300 → ~33/100 · **500 → ~20/100** · 800 → ~13/100.
Minimum mutlak: 30 A + 60 bukan-BI + 10 BI **aktif**, jika tidak
`InsufficientQuestionBankException` dibaling sebelum apa-apa ditulis.
