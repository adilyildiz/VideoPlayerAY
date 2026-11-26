# Test sonuçları — 25 Kasım 2026 (v2.1.0)

| Ortam | Sonuç |
| --- | --- |
| Moodle 4.5.16+ (20261124), PHP 8.2.12, MariaDB 11.4.5 | Yükseltme (`2026112600`), 28 entegrasyon kontrolü, yedekleme/geri yükleme, 2023011000 şemasından yükseltme ve HTTP testleri geçti |
| Moodle 5.3.1+ (20261124), PHP 8.4.27, MariaDB 11.4.5 | Yükseltme (`2026112600`), 28 entegrasyon kontrolü, yedekleme/geri yükleme, 2023011000 şemasından yükseltme ve HTTP testleri geçti |
| JavaScript / Node | İleri sarma, zorunlu soru atlama koruması, yanlış/doğru geri bildirim, cevaplanan soruyu hatırlama, alt dizin adresleri, sesskey, son kayıt sıralaması ve çevrimdışı tamamlama koruması geçti |

## Kapsam

- PHP 8.2 ve PHP 8.4 sözdizimi (`0` hata); Moodle XMLDB şemasını (`videoplayeray_questions`, `answered`, `questionid`) gerçek Moodle sınıflarıyla yükleme.
- `mm:ss` ve `hh:mm:ss` zaman ayrıştırma/biçimlendirme, `videoplayeray_is_list()` PHP 7.4/8.0 uyumluluk yardımcısı, geçersiz zaman ve çakışan saniye reddi.
- Normalize `videoplayeray_questions` CRUD (çoktan seçmeli ve doğru/yanlış), zorunlu soru tamamlama kapısı, `question_form` ve `mod_form` oluşturma ve doğrulama.
- Etkinlik oluşturma, eski JSON sorularının otomatik göçü, aktif ders kaydını bulma, video dosya alanı ve not defterine tam puan yazma.
- Privacy API bağlam keşfi ve veri/SMS meta verisi kaydı (`answered` ve `questionid` dahil).
- Ders yedekleme ve geri yüklemede video dosyasının, `videoplayeray_questions` kayıtlarının, tekrar sayılarının ve kullanıcı/etkinlik/soru kimliği eşlemelerinin korunması.
- Atılabilir test veritabanında `2023011000` şemasına dönülüp gerçek `xmldb_videoplayeray_upgrade` fonksiyonuyla `2026112600` sürümüne yükseltme; izlenme sayılarının korunması.
- HTTP üzerinden öğrenci girişi, oynatıcı sayfası, öğrencinin `questions.php` / `report.php` / `detail.php` erişiminin engellenmesi, eksik sesskey reddi, geçersiz ilerleme reddi, zorunlu soru cevaplanmadan `eksik_soru` yanıtı, soru cevaplandıktan sonra %95 hedefiyle `tam_izlenme`, yöneticinin soru yönetimi, soru bazlı başarı analizi, olay geçmişi HTML kaçışı ve CSV indirimi.

## Sınırlar

3.9–4.4 ve 5.0–5.2 ayrı kurulumlarda çalıştırılmadı (Moodle 3.9+ / PHP 7.4+ uyumluluk katmanı statik olarak tarandı ve korundu). PostgreSQL/SQL Server, gerçek SMS gönderimi, üçüncü taraf temalar, gerçek uzun videoyla tarayıcı testi ve mobil cihaz/tam ekran kombinasyonları bu test matrisine dahil değildir. Behat paketi çalıştırılmadı.

Testler yalnızca `.qa/` içindeki iki yerel Moodle kurulumu üzerinde yapıldı; harici bir Moodle sunucusuna değişiklik veya SMS gönderilmedi. Test günlükleri `.qa/smoke405.log`, `.qa/smoke503.log`, `.qa/backup405.log`, `.qa/backup503.log`, `.qa/http405.log`, `.qa/http503.log` dosyalarındadır.
