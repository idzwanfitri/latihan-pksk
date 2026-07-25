<?php

namespace Database\Seeders\Data;

/**
 * Bahagian A — Kecerdasan Insaniah.
 *
 * Situational items with no correct answer; each option carries a score of
 * 3, 2, 1 or 0. The written order of the options is deliberately varied so the
 * best answer cannot be inferred from its position on the page.
 */
final class SoalanA
{
    /**
     * @return array<int, array{text: string, options: array<int, array{text: string, score: int}>}>
     */
    public static function all(): array
    {
        return [
            [
                'text' => 'Rakan sekelas anda meminta untuk menyalin kerja rumah anda kerana dia tidak sempat menyiapkannya. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menolak, tetapi menawarkan diri untuk mengajarnya cara menyiapkan kerja itu.', 'score' => 3],
                    ['text' => 'Memberi kerja rumah anda kepadanya kerana dia rakan baik anda.', 'score' => 0],
                    ['text' => 'Menolak permintaannya tanpa memberikan sebarang penjelasan.', 'score' => 1],
                    ['text' => 'Menasihatinya supaya berjumpa guru dan memohon lanjutan masa.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda ternampak seorang murid tahun satu menangis seorang diri di tepi kantin pada waktu rehat. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Membiarkannya kerana mungkin dia hanya merajuk sebentar.', 'score' => 0],
                    ['text' => 'Menghampirinya, bertanya khabar dan menemaninya berjumpa guru bertugas.', 'score' => 3],
                    ['text' => 'Memberitahu guru bertugas tentang murid itu sebelum kembali makan.', 'score' => 2],
                    ['text' => 'Memandangnya sekilas dan meneruskan perjalanan ke kantin.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Kumpulan anda kalah dalam pertandingan kuiz sekolah walaupun sudah berlatih bersungguh-sungguh. Apakah perasaan dan tindakan anda?',
                'options' => [
                    ['text' => 'Menyalahkan ahli kumpulan yang gagal menjawab soalan terakhir.', 'score' => 0],
                    ['text' => 'Berasa kecewa dan memutuskan untuk tidak menyertai kuiz lagi.', 'score' => 1],
                    ['text' => 'Menerima keputusan dan mengajak kumpulan menilai kelemahan bersama.', 'score' => 3],
                    ['text' => 'Mengucapkan tahniah kepada pemenang dan meneruskan latihan.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda menjumpai sebuah dompet berisi wang di dalam tandas sekolah. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menyerahkan dompet itu kepada guru atau pejabat sekolah dengan segera.', 'score' => 3],
                    ['text' => 'Menyimpannya dahulu dan menunggu ada orang bertanya.', 'score' => 1],
                    ['text' => 'Bertanya kepada rakan sekelas siapa pemiliknya sebelum menyerahkannya.', 'score' => 2],
                    ['text' => 'Mengambil wang di dalamnya kerana anda yang menjumpainya.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Guru memberikan tugasan berkumpulan, tetapi seorang ahli anda tidak pernah menyiapkan bahagiannya. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Membuat bahagiannya sendiri tanpa memberitahu sesiapa.', 'score' => 1],
                    ['text' => 'Terus melaporkannya kepada guru supaya markahnya dipotong.', 'score' => 1],
                    ['text' => 'Berbincang dengannya secara baik untuk memahami masalahnya.', 'score' => 3],
                    ['text' => 'Mengagihkan semula tugasan itu dalam mesyuarat kumpulan.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda dipilih sebagai ketua kelas walaupun beberapa rakan tidak bersetuju. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Mendengar pandangan mereka dan berusaha bekerjasama dengan semua.', 'score' => 3],
                    ['text' => 'Menjalankan tugas seperti biasa dan mengabaikan bantahan mereka.', 'score' => 1],
                    ['text' => 'Meminta guru melantik orang lain kerana anda tidak disukai.', 'score' => 0],
                    ['text' => 'Membuktikan kemampuan anda melalui kerja yang baik.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Ibu anda sakit dan anda perlu menjaga adik pada malam sebelum peperiksaan. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Merungut kerana tiada masa untuk mengulang kaji pelajaran.', 'score' => 0],
                    ['text' => 'Menjaga adik sambil mengulang kaji apa yang termampu.', 'score' => 3],
                    ['text' => 'Menjaga adik dan bangun awal keesokan hari untuk mengulang kaji.', 'score' => 2],
                    ['text' => 'Meminta jiran menjaga adik supaya anda boleh belajar.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Seorang rakan sering mengejek cara anda bertutur. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Mengejeknya semula supaya dia merasainya.', 'score' => 0],
                    ['text' => 'Berdiam diri dan menjauhkan diri daripadanya.', 'score' => 1],
                    ['text' => 'Memberitahunya dengan tenang bahawa anda terasa hati.', 'score' => 3],
                    ['text' => 'Melaporkan perkara itu kepada guru kelas.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda diberi masa dua minggu untuk menyiapkan projek sains. Bagaimanakah anda menguruskannya?',
                'options' => [
                    ['text' => 'Membahagikan kerja mengikut jadual harian sejak hari pertama.', 'score' => 3],
                    ['text' => 'Memulakan kerja pada minggu kedua supaya lebih fokus.', 'score' => 2],
                    ['text' => 'Menyiapkannya pada malam sebelum tarikh serahan.', 'score' => 0],
                    ['text' => 'Menunggu rakan memulakan dahulu sebelum anda bermula.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda terlihat rakan meniru semasa ujian bulanan. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Berpura-pura tidak nampak kerana takut dimusuhi.', 'score' => 1],
                    ['text' => 'Menasihatinya selepas ujian supaya tidak mengulanginya.', 'score' => 3],
                    ['text' => 'Memaklumkan kepada guru selepas ujian tamat.', 'score' => 2],
                    ['text' => 'Meniru juga kerana dia pun berbuat demikian.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda ditugaskan membentangkan hasil kerja di hadapan kelas tetapi anda sangat gementar. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Berlatih berulang kali di rumah sebelum hari pembentangan.', 'score' => 3],
                    ['text' => 'Meminta rakan membentangkan bagi pihak anda.', 'score' => 0],
                    ['text' => 'Membaca terus daripada nota semasa membentang.', 'score' => 1],
                    ['text' => 'Menarik nafas dalam dan cuba membentang seadanya.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Sekolah anda mengadakan gotong-royong pada hari Sabtu. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Hadir awal dan mengajak rakan lain turut serta.', 'score' => 3],
                    ['text' => 'Hadir kerana diwajibkan oleh pihak sekolah.', 'score' => 2],
                    ['text' => 'Hadir sekejap untuk menandatangani kehadiran sahaja.', 'score' => 1],
                    ['text' => 'Tidak hadir kerana hari Sabtu ialah hari cuti.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda mendapat markah yang rendah dalam ujian matematik. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menyemak kesilapan dan meminta guru menerangkan semula.', 'score' => 3],
                    ['text' => 'Menyalahkan soalan kerana terlalu sukar.', 'score' => 0],
                    ['text' => 'Berjanji kepada diri untuk belajar lebih tekun.', 'score' => 2],
                    ['text' => 'Menyembunyikan kertas ujian daripada ibu bapa.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Seorang murid baharu duduk seorang diri semasa waktu rehat. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Membiarkannya kerana dia mungkin mahu bersendirian.', 'score' => 1],
                    ['text' => 'Menegurnya dan memperkenalkan dia kepada rakan lain.', 'score' => 3],
                    ['text' => 'Mengajaknya makan bersama kumpulan anda.', 'score' => 2],
                    ['text' => 'Memerhatikannya sahaja dari jauh.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda terlanjur bercakap kasar kepada adik anda kerana marah. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Meminta maaf kepadanya dan menjelaskan sebab anda marah.', 'score' => 3],
                    ['text' => 'Mendiamkan diri sehingga dia melupakannya.', 'score' => 1],
                    ['text' => 'Membelikannya sesuatu tanpa meminta maaf.', 'score' => 2],
                    ['text' => 'Menganggapnya perkara biasa antara adik-beradik.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda diminta memilih antara latihan sukan dan kelas tambahan yang berlangsung serentak. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Berbincang dengan kedua-dua guru untuk mencari jalan penyelesaian.', 'score' => 3],
                    ['text' => 'Memilih kelas tambahan dan memaklumkan jurulatih sukan.', 'score' => 2],
                    ['text' => 'Memilih latihan sukan tanpa memberitahu sesiapa.', 'score' => 0],
                    ['text' => 'Tidak hadir kedua-duanya kerana keliru.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Rakan anda berkongsi rahsia peribadi dan meminta anda merahsiakannya. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Memberitahu rakan rapat yang lain kerana anda percayakan mereka.', 'score' => 0],
                    ['text' => 'Merahsiakannya seperti yang dijanjikan.', 'score' => 3],
                    ['text' => 'Merahsiakannya, tetapi menasihatinya berjumpa orang dewasa jika serius.', 'score' => 2],
                    ['text' => 'Berpura-pura lupa tentang perkara itu.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda melihat sampah bertaburan di kawasan padang sekolah selepas perhimpunan. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Memungut sampah berdekatan dan mengajak rakan membantu.', 'score' => 3],
                    ['text' => 'Memungut sampah yang berdekatan dengan anda sahaja.', 'score' => 2],
                    ['text' => 'Melaporkannya kepada pengawas sekolah.', 'score' => 1],
                    ['text' => 'Meninggalkannya kerana ada pekerja pembersihan.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda tidak memahami satu topik walaupun sudah diterangkan dua kali oleh guru. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Berdiam diri kerana malu bertanya lagi.', 'score' => 0],
                    ['text' => 'Berjumpa guru selepas kelas untuk bertanya semula.', 'score' => 3],
                    ['text' => 'Meminta bantuan rakan yang sudah faham.', 'score' => 2],
                    ['text' => 'Mencari sendiri jawapannya dalam buku teks.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda dijemput ke majlis hari jadi rakan pada hari yang sama dengan mesyuarat kelab. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Memaklumkan awal kepada salah satu pihak dan menepati janji yang lain.', 'score' => 3],
                    ['text' => 'Hadir ke majlis hari jadi kerana lebih menyeronokkan.', 'score' => 1],
                    ['text' => 'Hadir ke mesyuarat kelab dan mengucapkan tahniah kemudian.', 'score' => 2],
                    ['text' => 'Tidak hadir kedua-duanya untuk mengelakkan masalah.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda ternampak seorang rakan membuang sampah dari tingkap bas sekolah. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menegurnya dengan sopan supaya tidak mengulanginya.', 'score' => 3],
                    ['text' => 'Memberitahu pemandu bas tentang perbuatannya.', 'score' => 2],
                    ['text' => 'Mendiamkan diri kerana bukan urusan anda.', 'score' => 0],
                    ['text' => 'Menceritakannya kepada rakan lain selepas turun bas.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda diberikan wang saku lebih daripada biasa oleh ibu bapa. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menyimpan sebahagian dan membelanjakan selebihnya dengan berhemah.', 'score' => 3],
                    ['text' => 'Membelanjakan semuanya pada hari yang sama.', 'score' => 0],
                    ['text' => 'Menyimpan kesemuanya di dalam tabung.', 'score' => 2],
                    ['text' => 'Membelanjakannya untuk membelikan rakan makanan.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda diminta menolong guru menyusun buku di perpustakaan pada waktu rehat. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menolong dengan rela hati walaupun waktu rehat berkurang.', 'score' => 3],
                    ['text' => 'Menolong sambil merungut kerana lapar.', 'score' => 1],
                    ['text' => 'Menolong dan mengajak seorang rakan membantu bersama.', 'score' => 2],
                    ['text' => 'Menolak kerana waktu rehat ialah hak anda.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Pasukan anda menang pertandingan bola sepak dengan jaringan yang anda cipta. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Meraikan kemenangan sebagai satu pasukan.', 'score' => 3],
                    ['text' => 'Menceritakan kehebatan jaringan anda kepada semua orang.', 'score' => 0],
                    ['text' => 'Mengucapkan terima kasih kepada rakan yang menghantar bola.', 'score' => 2],
                    ['text' => 'Berdiam diri dan pulang seperti biasa.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda terlupa membawa buku latihan dan guru bertanya sebabnya. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Mengaku terlupa dan berjanji membawanya esok.', 'score' => 3],
                    ['text' => 'Berkata buku itu tertinggal di rumah nenek.', 'score' => 0],
                    ['text' => 'Berkata anda tidak tahu ke mana buku itu hilang.', 'score' => 1],
                    ['text' => 'Mengaku terlupa tanpa memberikan sebarang penjelasan.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Rakan sebelah anda kelihatan murung sejak beberapa hari. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Bertanya khabar dan menawarkan diri untuk mendengar ceritanya.', 'score' => 3],
                    ['text' => 'Membiarkannya kerana takut mengganggu.', 'score' => 1],
                    ['text' => 'Memaklumkan kepada guru kaunseling.', 'score' => 2],
                    ['text' => 'Menceritakan hal itu kepada rakan lain.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda tidak bersetuju dengan cadangan majoriti ahli kumpulan. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menyuarakan pandangan anda dengan sopan sebelum undian dibuat.', 'score' => 3],
                    ['text' => 'Menerima cadangan majoriti tanpa berkata apa-apa.', 'score' => 2],
                    ['text' => 'Menolak untuk bekerjasama dalam tugasan itu.', 'score' => 0],
                    ['text' => 'Merungut kepada rakan lain di luar mesyuarat.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda dan rakan berebut untuk menggunakan komputer perpustakaan yang terakhir. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Mencadangkan giliran supaya kedua-duanya dapat menggunakannya.', 'score' => 3],
                    ['text' => 'Mengalah dan menunggu komputer lain kosong.', 'score' => 2],
                    ['text' => 'Berkeras kerana anda sampai dahulu.', 'score' => 1],
                    ['text' => 'Bertengkar dengannya di dalam perpustakaan.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Guru memuji hasil kerja anda di hadapan kelas. Apakah reaksi anda?',
                'options' => [
                    ['text' => 'Mengucapkan terima kasih dan terus berusaha.', 'score' => 3],
                    ['text' => 'Berasa bangga dan memandang rendah kerja rakan lain.', 'score' => 0],
                    ['text' => 'Tersenyum dan berkongsi cara anda menyiapkannya.', 'score' => 2],
                    ['text' => 'Berasa malu dan tidak berkata apa-apa.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda ternampak seorang warga emas kesukaran melintas jalan berhampiran sekolah. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menawarkan diri untuk memimpinnya melintas dengan selamat.', 'score' => 3],
                    ['text' => 'Memanggil pengawal keselamatan sekolah untuk membantu.', 'score' => 2],
                    ['text' => 'Memerhatikan sahaja sehingga dia berjaya melintas.', 'score' => 1],
                    ['text' => 'Meneruskan perjalanan kerana anda sudah lewat.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Kumpulan anda perlu memilih tajuk projek tetapi tiada persetujuan dicapai. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Mencadangkan setiap ahli membentangkan idea sebelum mengundi.', 'score' => 3],
                    ['text' => 'Membiarkan ketua kumpulan yang memutuskan.', 'score' => 2],
                    ['text' => 'Berkeras dengan tajuk pilihan anda sendiri.', 'score' => 0],
                    ['text' => 'Menunggu guru menetapkan tajuk untuk kumpulan.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda dijanjikan hadiah oleh bapa jika mendapat keputusan cemerlang. Apakah motivasi anda belajar?',
                'options' => [
                    ['text' => 'Belajar kerana ingin menguasai ilmu, hadiah hanya bonus.', 'score' => 3],
                    ['text' => 'Belajar semata-mata untuk mendapatkan hadiah itu.', 'score' => 1],
                    ['text' => 'Belajar kerana tidak mahu mengecewakan bapa.', 'score' => 2],
                    ['text' => 'Belajar hanya apabila diingatkan oleh bapa.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Anda ditegur guru kerana kesilapan yang anda tidak lakukan. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menjelaskan keadaan sebenar dengan tenang dan sopan.', 'score' => 3],
                    ['text' => 'Menerima teguran itu tanpa membela diri.', 'score' => 1],
                    ['text' => 'Membantah dengan suara yang tinggi di dalam kelas.', 'score' => 0],
                    ['text' => 'Berjumpa guru selepas kelas untuk menjelaskannya.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Rakan anda selalu meminjam pensel dan tidak pernah memulangkannya. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Memberitahunya dengan baik supaya memulangkan pinjaman.', 'score' => 3],
                    ['text' => 'Berhenti meminjamkan apa-apa kepadanya tanpa penjelasan.', 'score' => 1],
                    ['text' => 'Terus meminjamkan kerana tidak mahu berselisih faham.', 'score' => 0],
                    ['text' => 'Meminta bantuan ketua kelas untuk menegurnya.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Anda mempunyai masa lapang selepas menyiapkan semua kerja sekolah. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Membaca buku atau membantu kerja rumah keluarga.', 'score' => 3],
                    ['text' => 'Bermain permainan video sehingga larut malam.', 'score' => 0],
                    ['text' => 'Berehat sebentar sebelum mengulang kaji ringan.', 'score' => 2],
                    ['text' => 'Menonton televisyen sepanjang petang.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda dipilih mewakili sekolah tetapi latihannya bertindih dengan kelas tuisyen. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Berbincang dengan ibu bapa dan guru untuk menyusun semula jadual.', 'score' => 3],
                    ['text' => 'Menolak jemputan mewakili sekolah.', 'score' => 1],
                    ['text' => 'Menghadiri latihan dan meninggalkan kelas tuisyen sepenuhnya.', 'score' => 0],
                    ['text' => 'Menghadiri latihan dan meminta nota tuisyen daripada rakan.', 'score' => 2],
                ],
            ],
            [
                'text' => 'Seorang rakan mengajak anda ponteng kelas pendidikan jasmani. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menolak dan mengajaknya masuk kelas bersama-sama.', 'score' => 3],
                    ['text' => 'Menolak dan terus masuk kelas seorang diri.', 'score' => 2],
                    ['text' => 'Mengikutnya kerana tidak mahu dianggap sombong.', 'score' => 0],
                    ['text' => 'Berpura-pura sakit supaya tidak perlu memilih.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda menyedari terdapat kesilapan dalam markah yang diberikan guru, memihak kepada anda. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Memaklumkan kepada guru supaya markah dibetulkan.', 'score' => 3],
                    ['text' => 'Mendiamkan diri kerana ia bukan salah anda.', 'score' => 0],
                    ['text' => 'Bertanya kepada rakan sama ada perlu memberitahu guru.', 'score' => 2],
                    ['text' => 'Menunggu guru menyedarinya sendiri.', 'score' => 1],
                ],
            ],
            [
                'text' => 'Anda perlu menyiapkan tiga tugasan dalam masa yang singkat. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menyusun keutamaan mengikut tarikh serahan dan kesukaran.', 'score' => 3],
                    ['text' => 'Menyiapkan tugasan yang paling mudah sahaja.', 'score' => 1],
                    ['text' => 'Menyiapkan mengikut urutan ia diberikan.', 'score' => 2],
                    ['text' => 'Menangguhkan semuanya kerana berasa tertekan.', 'score' => 0],
                ],
            ],
            [
                'text' => 'Ibu meminta anda menjaga rumah semasa beliau ke pasar, tetapi rakan mengajak bermain. Apakah tindakan anda?',
                'options' => [
                    ['text' => 'Menunaikan amanah menjaga rumah dan mengajak rakan bermain di rumah anda.', 'score' => 3],
                    ['text' => 'Menolak ajakan rakan dan menunggu ibu pulang.', 'score' => 2],
                    ['text' => 'Keluar bermain sebentar sebelum ibu pulang.', 'score' => 0],
                    ['text' => 'Menghubungi ibu untuk meminta kebenaran keluar.', 'score' => 1],
                ],
            ],
        ];
    }
}
