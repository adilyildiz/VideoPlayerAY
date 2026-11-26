# VideoPlayerAY / Video Analiz (`mod_videoplayeray`) 2.1

Moodle için video izlenme takibi, zamanlı etkileşimli sorular, ileri sarma koruması ve ayrıntılı raporlama sunan etkinlik modülü (GNU GPL v3).

Kurulum ZIP paketini [Releases](../../releases) üzerinden indirebilir veya `python tools/package.py` komutuyla `dist/videoplayeray-2.1.0.zip` olarak oluşturabilirsiniz.

## Uyumluluk

- Hedef: Moodle 3.9 LTS – 5.3 LTS (`$plugin->requires = 2020061500`, `$plugin->supported = [309, 503]`).
- Gerçek kurulum testleri: Moodle 4.5.16+ / PHP 8.2.12 ve Moodle 5.3.1+ / PHP 8.4.27; MariaDB 11.4.5.
- 3.9–4.4 ve 5.0–5.2 ayrıca çalıştırılmadı; PHP 7.4+ / Moodle 3.9+ uyumluluk katmanı (`videoplayeray_is_list`, `FEATURE_MOD_PURPOSE` koruması, `activityheader` çalışma zamanı kontrolü, Moodle 3.9–3.11 açıklama gösterimi ve BS4/BS5 ortak CSS sınıfları) statik olarak doğrulanmıştır.
- Sürüm, kuruma özgü tema/SMS sağlayıcısı ve gerçek video kabul testi tamamlanana kadar beta olarak işaretlidir.

## Kurulum ve mevcut eklentiyi yükseltme

1. Mevcut Moodle veritabanı, `moodledata` ve eklenti klasörünün yedeğini alın.
2. Yönetim > Eklentiler > Eklenti yükle bölümünden yeni ZIP'i yükleyin. Elle kurulumda ZIP içindeki `videoplayeray` klasörünü Moodle'ın etkinlik modülleri klasörüne yerleştirin.
3. Moodle 3.9–5.0 için konum `moodle/mod/videoplayeray`; 5.1 ve sonrasında `moodle/public/mod/videoplayeray` olur. ZIP'in kökünde tek `videoplayeray` klasörü vardır.
4. Yönetim > Bildirimler üzerinden veritabanı yükseltmesini tamamlayın ve önbellekleri temizleyin.
5. Video Analiz ayarlarında gerekli izlenme sayısını ve yüzdesini kontrol edin. Varsayılanlar: 1 tekrar ve %95.

2023011000 veya 2026112500 sürümünden yükseltmede video dosyaları, eski soruların betik adresi, izlenme sayıları ve olay kayıtları korunur. Mevcut JSON soruları otomatik olarak yeni `videoplayeray_questions` tablosuna taşınır; kullanıcı verisi silinmez. Yeni rapor yetkisi `mod/videoplayeray:viewreports` öğretmen/yönetici rollerine eklenir; özel rollerde ayrıca tanımlayın.

## Yeni kullanım

**Öğrenci:** Video yanında izlenme yüzdesini, gerekli hedefi, tamamlanan tekrar sayısını, soru ilerleme özetini (`doğru / toplam` ve zaman rozetleri) ve kayıt durumunu görür. Zorunlu soruları doğru cevaplamadan ileri sarma engellenir; doğru cevaplanan sorular güncel izleme turunda hatırlanır ve geri sarıldığında tekrar sorulmaz. Yanlış cevapta panel içinde geri bildirim gösterilir ve önceki bölüme dönüp tekrar izleme düğmesi sunulur. Oturum açmış etkin kayıtlı katılımcılarda ilerleme otomatik kaydedilir. Daha önce kaydedilen konum varsa “Kaldığım yerden devam et” düğmesi çıkar. Misafir ve kayıtlı olmayan öğretmen/yönetici görüntülemesi önizlemedir.

**Öğretmen (Soruları Yönet):** Etkinlik sekmesindeki **Zamanlı sorular** (`questions.php`) sayfasında yüklenen videoyu önizleyerek istediğiniz saniyede durdurup tek tıkla soru zamanı veya dönüş zamanı seçebilirsiniz. Zamanlar `dk:sn` (örn. `01:30`), `ss:dk:sn` veya ham saniye olarak girilebilir. Çoktan seçmeli (2–6 seçenek) ve Doğru/Yanlış soru türleri, zorunlu/isteğe bağlı ayarı, seçenek karıştırma, doğru/yanlış özel açıklamaları ve video süresini aşan sorular için canlı uyarı desteklenir.

**Rapor:** Etkinlik sekmesindeki **Video raporu** sayfasında video, ders veya tüm ders kapsamını seçer; katılımcı adına ve tamamlanma durumuna göre filtreler. Tabloda ve CSV çıktısında katılımcı bazlı doğru cevaplanan soru sayısı gösterilir; tek video raporunda soru bazlı deneme/doğru/yanlış/başarı oranı analizi yer alır. Katılımcı adına tıklayarak oynatma ve soru/cevap geçmişine erişir. Tüm ders raporu site yapılandırma yetkisi gerektirir; ayrı grup sınırlamaları uygulanır.

**Notlandırma ve Ders Sıfırlama:** Etkinlikte puan tanımlıysa gerekli tekrar sayısını (ve varsa tüm zorunlu soruları) tamamlayan katılımcı tam puan alır; tamamlamayan 0 alır. Ölçek kullanılıyorsa tamamlayana en yüksek ölçek değeri verilir. `grade.php` öğretmenleri doğrudan rapor/detay ekranına yönlendirir. Ders sıfırlama (Course Reset) ile tüm izlenme ilerlemesi ve soru cevap kayıtları temizlenebilir.

## Eski olaylar.js dosyaları

Önceki `var olaylar = {"5": ["Soru", [["A", false], ["B", true]], 0]};` biçimi korunur. Veritabanında tanımlı sorular varsa onlar önceliklidir. Betik adresi gelişmiş bölümde tutulur; yeni bir çalıştırılabilir betik eklemek/değiştirmek `moodle/site:trustcontent` yetkisi gerektirir. Yeni sorular için ayrı JavaScript dosyası gerekmez.

## Teknik değişiklikler ve sınırlar

- HTML5 video ve Moodle AMD modülleri (`mod_videoplayeray/player`, `mod_videoplayeray/questions`); eski gömülü jQuery/Video.js yüklenmez ve yeni dağıtıma alınmaz.
- Normalize `videoplayeray_questions` tablosu, `videoplayeray_izlenme.answered` ve `videoplayeray_olaylar.questionid` alanları; tam yedekleme/geri yükleme ve ID eşleme desteği.
- Moodle 3.9–5.3 uyumluluk katmanı: PHP 7.4/8.0 için `videoplayeray_is_list()` polyfill'i, Moodle 4.0+ `FEATURE_MOD_PURPOSE` ve `$PAGE->activityheader` için çalışma zamanı korumaları (`videoplayeray_configure_activity_header()`), Moodle 3.9–3.11 için `format_module_intro()` desteği, Bootstrap 4/5 ortak CSS sınıfları, secondary/settings navigation (`Zamanlı sorular`, `Video raporu`), `FEATURE_GROUPS` / `FEATURE_GROUPINGS` ve `videoplayeray_reset_userdata()`.
- Kayıt uçlarında POST, sesskey, etkinlik erişimi ve aktif kayıt kontrolü; kullanıcı kimliği sunucu oturumundan alınır.
- Uzun videolarda `max_input_vars` kesilmesini önlemek için JSON; en fazla 24 saatlik video takibi.
- Tamamlama öncesinde son kayıt beklenir; zorunlu sorular cevaplanmadan tamamlama (`videocomplete.php`) reddedilir (`eksik_soru`). Boş ilerlemenin yeniden gönderilmesi tekrar sayısını artırmaz.
- Moodle Privacy API ile kullanıcı verisi keşfi, dışa aktarma, tek/toplu silme; yeni olaylarda bütün `$USER` nesnesi kopyalanmaz.
- SMS görevi korunur: HTTPS, sertifika doğrulama, 30 saniye zaman aşımı, öğrenci başına tek hatırlatma. SMS varsayılan olarak kapalıdır. Gerçek SMS gönderimi test edilmedi.

## Kontrolleri yeniden çalıştırma

`tools/smoke.php`, `tools/backup_smoke.php` ve `tools/http_test.py` **yalnızca boş, yerel ve atılabilir test kurulumları** içindir.

```text
node tools/player_test.cjs
php tools/smoke.php ABSOLUTE_MOODLE_PUBLIC_ROOT
php tools/backup_smoke.php ABSOLUTE_MOODLE_PUBLIC_ROOT
python tools/http_test.py 405 8089
python tools/http_test.py 503 8090
python tools/package.py
```

Son test kapsamı `TEST-RESULTS.md` dosyasındadır.

## Lisans

GNU General Public License v3.0 — ayrıntılar için [LICENSE](LICENSE) dosyasına bakınız.
