<?php

namespace Database\Seeders\Data;

/**
 * Bahagian B — Kecerdasan Intelek.
 *
 * Objective items with exactly one correct answer. `correct` is the zero-based
 * index into `options`. Distribution: iq 20, matematik 19, pengetahuan_am 18,
 * sains 18, bahasa_inggeris 15 — both draw pools exceed their quota so
 * successive attempts genuinely differ.
 */
final class SoalanB
{
    /**
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    public static function all(): array
    {
        return [
            ...self::iq(),
            ...self::matematik(),
            ...self::pengetahuanAm(),
            ...self::sains(),
            ...self::bahasaInggeris(),
        ];
    }

    /**
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    private static function iq(): array
    {
        return array_map(self::tag('iq'), [
            ['text' => 'Lengkapkan siri nombor berikut: 2, 4, 8, 16, ___', 'options' => ['24', '32', '20', '28'], 'correct' => 1],
            ['text' => 'Lengkapkan siri nombor berikut: 3, 6, 9, 12, ___', 'options' => ['14', '18', '15', '16'], 'correct' => 2],
            ['text' => 'Lengkapkan siri nombor berikut: 1, 1, 2, 3, 5, 8, ___', 'options' => ['11', '12', '13', '15'], 'correct' => 2],
            ['text' => 'Lengkapkan siri huruf berikut: A, C, E, G, ___', 'options' => ['H', 'I', 'J', 'K'], 'correct' => 1],
            ['text' => 'Lengkapkan siri nombor berikut: 100, 90, 81, 73, ___', 'options' => ['66', '65', '64', '67'], 'correct' => 0],
            ['text' => 'Kaki ialah kepada berjalan seperti mata ialah kepada ___', 'options' => ['tidur', 'melihat', 'mendengar', 'menghidu'], 'correct' => 1],
            ['text' => 'Buku ialah kepada membaca seperti garpu ialah kepada ___', 'options' => ['memotong', 'makan', 'memasak', 'mencuci'], 'correct' => 1],
            ['text' => 'Jika semua Zilo ialah Mimo, dan semua Mimo ialah Tako, maka semua Zilo ialah ___', 'options' => ['bukan Tako', 'Tako', 'mungkin Tako', 'tiada kaitan'], 'correct' => 1],
            ['text' => 'Cari nombor ganjil dalam kumpulan: 4, 9, 16, 25, 36', 'options' => ['4', '9', '16', '36'], 'correct' => 1],
            ['text' => 'Yang manakah tidak sekumpulan dengan yang lain?', 'options' => ['Kerusi', 'Meja', 'Almari', 'Cawan'], 'correct' => 3],
            ['text' => 'Lengkapkan siri nombor berikut: 5, 10, 20, 40, ___', 'options' => ['60', '70', '80', '100'], 'correct' => 2],
            ['text' => 'Jika hari ini hari Selasa, hari apakah 10 hari lagi?', 'options' => ['Jumaat', 'Sabtu', 'Ahad', 'Khamis'], 'correct' => 0],
            ['text' => 'Lengkapkan siri nombor berikut: 81, 64, 49, 36, ___', 'options' => ['24', '25', '27', '30'], 'correct' => 1],
            ['text' => 'Ali lebih tinggi daripada Bakar. Bakar lebih tinggi daripada Chong. Siapakah yang paling rendah?', 'options' => ['Ali', 'Bakar', 'Chong', 'Tidak dapat ditentukan'], 'correct' => 2],
            ['text' => 'Yang manakah tidak sekumpulan dengan yang lain?', 'options' => ['Merah', 'Biru', 'Bulat', 'Kuning'], 'correct' => 2],
            ['text' => 'Lengkapkan siri huruf berikut: Z, X, V, T, ___', 'options' => ['S', 'R', 'Q', 'P'], 'correct' => 1],
            ['text' => 'Doktor ialah kepada hospital seperti guru ialah kepada ___', 'options' => ['sekolah', 'buku', 'murid', 'papan'], 'correct' => 0],
            ['text' => 'Lengkapkan siri nombor berikut: 7, 14, 28, 56, ___', 'options' => ['96', '108', '112', '120'], 'correct' => 2],
            ['text' => 'Sebuah jam menunjukkan pukul 3.00. Berapakah sudut antara jarum jam dan jarum minit?', 'options' => ['60 darjah', '90 darjah', '120 darjah', '180 darjah'], 'correct' => 1],
            ['text' => 'Yang manakah tidak sekumpulan dengan yang lain?', 'options' => ['Singa', 'Harimau', 'Kucing', 'Helang'], 'correct' => 3],
        ]);
    }

    /**
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    private static function matematik(): array
    {
        return array_map(self::tag('matematik'), [
            ['text' => 'Berapakah hasil tambah 456 + 289?', 'options' => ['735', '745', '755', '645'], 'correct' => 1],
            ['text' => 'Berapakah 25% daripada 240?', 'options' => ['50', '55', '60', '65'], 'correct' => 2],
            ['text' => 'Sebuah kotak mengandungi 144 biji guli. Jika dibahagi sama rata kepada 12 orang murid, berapakah guli setiap seorang?', 'options' => ['10', '11', '12', '14'], 'correct' => 2],
            ['text' => 'Berapakah hasil darab 15 × 12?', 'options' => ['170', '180', '190', '160'], 'correct' => 1],
            ['text' => 'Luas sebuah segi empat tepat ialah 48 cm persegi. Jika panjangnya 8 cm, berapakah lebarnya?', 'options' => ['5 cm', '6 cm', '7 cm', '8 cm'], 'correct' => 1],
            ['text' => 'Tukarkan 3/4 kepada peratus.', 'options' => ['65%', '70%', '75%', '80%'], 'correct' => 2],
            ['text' => 'Sebuah bas bertolak pada pukul 8.15 pagi dan tiba pada pukul 11.45 pagi. Berapakah tempoh perjalanan?', 'options' => ['3 jam', '3 jam 15 minit', '3 jam 30 minit', '3 jam 45 minit'], 'correct' => 2],
            ['text' => 'Berapakah nilai 7² + 3²?', 'options' => ['52', '58', '49', '64'], 'correct' => 1],
            ['text' => 'Harga sebuah buku ialah RM12.50. Berapakah harga 4 buah buku?', 'options' => ['RM48.00', 'RM50.00', 'RM52.50', 'RM45.00'], 'correct' => 1],
            ['text' => 'Perimeter sebuah segi empat sama ialah 36 cm. Berapakah panjang satu sisinya?', 'options' => ['6 cm', '8 cm', '9 cm', '12 cm'], 'correct' => 2],
            ['text' => 'Berapakah baki apabila 100 dibahagi dengan 7?', 'options' => ['1', '2', '3', '4'], 'correct' => 1],
            ['text' => 'Purata bagi 12, 18 dan 24 ialah ___', 'options' => ['16', '17', '18', '20'], 'correct' => 2],
            ['text' => 'Sebuah tangki berisi 3.5 liter air. Berapakah isi padu air itu dalam mililiter?', 'options' => ['350 ml', '3500 ml', '35 ml', '35000 ml'], 'correct' => 1],
            ['text' => 'Apakah nilai digit 7 dalam nombor 47 258?', 'options' => ['70', '700', '7000', '70000'], 'correct' => 2],
            ['text' => 'Sebuah kedai memberi diskaun 20% bagi barang berharga RM80. Berapakah harga selepas diskaun?', 'options' => ['RM60', 'RM64', 'RM68', 'RM72'], 'correct' => 1],
            ['text' => 'Berapakah hasil bahagi 720 ÷ 9?', 'options' => ['70', '75', '80', '85'], 'correct' => 2],
            ['text' => 'Sebuah segi tiga mempunyai sudut 45 darjah dan 65 darjah. Berapakah sudut ketiga?', 'options' => ['60 darjah', '65 darjah', '70 darjah', '75 darjah'], 'correct' => 2],
            ['text' => 'Tukarkan 2.5 kilogram kepada gram.', 'options' => ['250 g', '2500 g', '25 g', '25000 g'], 'correct' => 1],
            ['text' => 'Jika 5 buah pen berharga RM7.50, berapakah harga 8 buah pen?', 'options' => ['RM10.00', 'RM11.00', 'RM12.00', 'RM12.50'], 'correct' => 2],
        ]);
    }

    /**
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    private static function pengetahuanAm(): array
    {
        return array_map(self::tag('pengetahuan_am'), [
            ['text' => 'Apakah ibu negara Malaysia?', 'options' => ['Putrajaya', 'Kuala Lumpur', 'Shah Alam', 'Johor Bahru'], 'correct' => 1],
            ['text' => 'Berapakah bilangan negeri di Malaysia?', 'options' => ['12', '13', '14', '15'], 'correct' => 1],
            ['text' => 'Pada tarikh bilakah Malaysia mencapai kemerdekaan?', 'options' => ['31 Ogos 1957', '16 September 1963', '31 Ogos 1963', '16 September 1957'], 'correct' => 0],
            ['text' => 'Siapakah Perdana Menteri Malaysia yang pertama?', 'options' => ['Tun Abdul Razak', 'Tunku Abdul Rahman', 'Tun Hussein Onn', 'Tun Dr. Mahathir'], 'correct' => 1],
            ['text' => 'Apakah bunga kebangsaan Malaysia?', 'options' => ['Melur', 'Bunga Raya', 'Orkid', 'Cempaka'], 'correct' => 1],
            ['text' => 'Apakah nama lagu kebangsaan Malaysia?', 'options' => ['Jalur Gemilang', 'Negaraku', 'Tanah Pusaka', 'Keranamu Malaysia'], 'correct' => 1],
            ['text' => 'Negeri manakah yang paling luas di Malaysia?', 'options' => ['Sabah', 'Sarawak', 'Pahang', 'Johor'], 'correct' => 1],
            ['text' => 'Apakah nama bendera Malaysia?', 'options' => ['Jalur Gemilang', 'Bulan Bintang', 'Sang Saka', 'Panji Negara'], 'correct' => 0],
            ['text' => 'Gunung manakah yang tertinggi di Malaysia?', 'options' => ['Gunung Tahan', 'Gunung Kinabalu', 'Gunung Ledang', 'Gunung Jerai'], 'correct' => 1],
            ['text' => 'Berapakah bilangan Wilayah Persekutuan di Malaysia?', 'options' => ['2', '3', '4', '5'], 'correct' => 1],
            ['text' => 'Apakah mata wang Malaysia?', 'options' => ['Rupiah', 'Ringgit', 'Dolar', 'Baht'], 'correct' => 1],
            ['text' => 'Selat manakah yang memisahkan Semenanjung Malaysia dengan Pulau Sumatera?', 'options' => ['Selat Johor', 'Selat Melaka', 'Selat Tebrau', 'Selat Sunda'], 'correct' => 1],
            ['text' => 'Apakah nama Parlimen Malaysia bagi Dewan Rendah?', 'options' => ['Dewan Negara', 'Dewan Rakyat', 'Dewan Undangan', 'Majlis Raja-Raja'], 'correct' => 1],
            ['text' => 'Sungai manakah yang terpanjang di Malaysia?', 'options' => ['Sungai Pahang', 'Sungai Rajang', 'Sungai Perak', 'Sungai Kelantan'], 'correct' => 1],
            ['text' => 'Apakah prinsip pertama Rukun Negara?', 'options' => ['Kesetiaan kepada Raja dan Negara', 'Kepercayaan kepada Tuhan', 'Keluhuran Perlembagaan', 'Kedaulatan Undang-Undang'], 'correct' => 1],
            ['text' => 'Negeri manakah yang dikenali sebagai Pulau Mutiara Timur?', 'options' => ['Langkawi', 'Pulau Pinang', 'Melaka', 'Labuan'], 'correct' => 1],
            ['text' => 'Apakah nama ketua negara Malaysia?', 'options' => ['Perdana Menteri', 'Yang di-Pertuan Agong', 'Sultan', 'Menteri Besar'], 'correct' => 1],
            ['text' => 'Berapa lamakah tempoh pemerintahan seorang Yang di-Pertuan Agong?', 'options' => ['3 tahun', '4 tahun', '5 tahun', '6 tahun'], 'correct' => 2],
        ]);
    }

    /**
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    private static function sains(): array
    {
        return array_map(self::tag('sains'), [
            ['text' => 'Apakah gas yang diperlukan oleh manusia untuk bernafas?', 'options' => ['Karbon dioksida', 'Oksigen', 'Nitrogen', 'Hidrogen'], 'correct' => 1],
            ['text' => 'Apakah proses tumbuhan menghasilkan makanan sendiri?', 'options' => ['Respirasi', 'Fotosintesis', 'Transpirasi', 'Pendebungaan'], 'correct' => 1],
            ['text' => 'Berapakah takat didih air pada tekanan biasa?', 'options' => ['90°C', '100°C', '110°C', '80°C'], 'correct' => 1],
            ['text' => 'Organ manakah yang mengepam darah ke seluruh badan?', 'options' => ['Paru-paru', 'Jantung', 'Hati', 'Buah pinggang'], 'correct' => 1],
            ['text' => 'Apakah planet yang paling hampir dengan Matahari?', 'options' => ['Zuhrah', 'Utarid', 'Bumi', 'Marikh'], 'correct' => 1],
            ['text' => 'Apakah keadaan air pada suhu 0°C?', 'options' => ['Wap', 'Ais', 'Cecair', 'Gas'], 'correct' => 1],
            ['text' => 'Haiwan manakah yang bertelur?', 'options' => ['Kucing', 'Ayam', 'Lembu', 'Kambing'], 'correct' => 1],
            ['text' => 'Apakah bahagian tumbuhan yang menyerap air dari tanah?', 'options' => ['Daun', 'Akar', 'Batang', 'Bunga'], 'correct' => 1],
            ['text' => 'Apakah sumber tenaga utama bagi Bumi?', 'options' => ['Bulan', 'Matahari', 'Angin', 'Air'], 'correct' => 1],
            ['text' => 'Berapakah bilangan planet dalam Sistem Suria?', 'options' => ['7', '8', '9', '10'], 'correct' => 1],
            ['text' => 'Apakah organ deria yang digunakan untuk menghidu?', 'options' => ['Lidah', 'Hidung', 'Telinga', 'Kulit'], 'correct' => 1],
            ['text' => 'Bahan manakah yang merupakan konduktor elektrik yang baik?', 'options' => ['Kayu', 'Tembaga', 'Getah', 'Plastik'], 'correct' => 1],
            ['text' => 'Apakah proses perubahan cecair kepada gas?', 'options' => ['Kondensasi', 'Penyejatan', 'Pembekuan', 'Peleburan'], 'correct' => 1],
            ['text' => 'Apakah rangka manusia dewasa terdiri daripada berapa batang tulang?', 'options' => ['186', '206', '226', '246'], 'correct' => 1],
            ['text' => 'Haiwan manakah yang tergolong dalam kumpulan amfibia?', 'options' => ['Ular', 'Katak', 'Ikan', 'Burung'], 'correct' => 1],
            ['text' => 'Apakah gas yang dibebaskan oleh tumbuhan semasa fotosintesis?', 'options' => ['Karbon dioksida', 'Oksigen', 'Nitrogen', 'Metana'], 'correct' => 1],
            ['text' => 'Apakah alat yang digunakan untuk mengukur suhu?', 'options' => ['Barometer', 'Termometer', 'Ammeter', 'Voltmeter'], 'correct' => 1],
            ['text' => 'Apakah bahagian mata yang mengawal jumlah cahaya yang masuk?', 'options' => ['Kornea', 'Anak mata', 'Retina', 'Lensa'], 'correct' => 1],
        ]);
    }

    /**
     * Written in English, which is correct for this subject even though the rest
     * of the interface is in Bahasa Melayu.
     *
     * @return array<int, array{subject: string, text: string, options: array<int, string>, correct: int}>
     */
    private static function bahasaInggeris(): array
    {
        return array_map(self::tag('bahasa_inggeris'), [
            ['text' => 'Choose the correct answer: She ___ to school every morning.', 'options' => ['go', 'goes', 'going', 'gone'], 'correct' => 1],
            ['text' => 'Choose the correct answer: There ___ many books on the shelf.', 'options' => ['is', 'are', 'was', 'has'], 'correct' => 1],
            ['text' => 'Choose the word with the opposite meaning of "brave".', 'options' => ['bold', 'cowardly', 'strong', 'kind'], 'correct' => 1],
            ['text' => 'Choose the correct answer: My brother and I ___ playing badminton now.', 'options' => ['am', 'are', 'is', 'be'], 'correct' => 1],
            ['text' => 'Choose the correct plural form of "child".', 'options' => ['childs', 'children', 'childes', 'childrens'], 'correct' => 1],
            ['text' => 'Choose the correct answer: The cat is hiding ___ the table.', 'options' => ['on', 'under', 'at', 'of'], 'correct' => 1],
            ['text' => 'Choose the word with the same meaning as "happy".', 'options' => ['angry', 'glad', 'tired', 'sad'], 'correct' => 1],
            ['text' => 'Choose the correct answer: He ___ his homework yesterday.', 'options' => ['finish', 'finished', 'finishes', 'finishing'], 'correct' => 1],
            ['text' => 'Choose the correct answer: This is ___ interesting story.', 'options' => ['a', 'an', 'the', 'some'], 'correct' => 1],
            ['text' => 'Choose the correct answer: The students ___ quietly in the library.', 'options' => ['reads', 'read', 'reading', 'to read'], 'correct' => 1],
            ['text' => 'Choose the correct answer: If it rains, we ___ stay at home.', 'options' => ['would', 'will', 'were', 'have'], 'correct' => 1],
            ['text' => 'Choose the correct answer: She is the ___ girl in her class.', 'options' => ['tall', 'tallest', 'taller', 'more tall'], 'correct' => 1],
            ['text' => 'Choose the correct answer: I have lived here ___ 2019.', 'options' => ['for', 'since', 'from', 'at'], 'correct' => 1],
            ['text' => 'Choose the correct answer: Please ___ the door before you leave.', 'options' => ['closed', 'close', 'closing', 'closes'], 'correct' => 1],
            ['text' => 'Choose the word with the opposite meaning of "ancient".', 'options' => ['old', 'modern', 'past', 'historic'], 'correct' => 1],
        ]);
    }

    /**
     * @return callable(array{text: string, options: array<int, string>, correct: int}): array{subject: string, text: string, options: array<int, string>, correct: int}
     */
    private static function tag(string $subject): callable
    {
        return fn (array $item): array => [
            'subject' => $subject,
            'text' => $item['text'],
            'options' => $item['options'],
            'correct' => $item['correct'],
        ];
    }
}
