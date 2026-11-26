<?php
// This file is part of Moodle - https://moodle.org/
// Licensed under the GNU GPL v3 or later: https://www.gnu.org/copyleft/gpl.html
/**
 * Turkish strings for mod_videoplayeray.
 * @package mod_videoplayeray
 * @copyright 2017 Adil YILDIZ
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Video Analiz';
$string['modulenameplural'] = 'Video Analiz';
$string['modulename_help'] = 'İzlenme takibi ve zamanlı etkileşimli sorular içeren video etkinlikleri oluşturmanızı sağlar.';
$string['novideoplayerays'] = 'Bu derste henüz Video Analiz etkinliği bulunmuyor.';
$string['videoplayeray'] = 'Video Analiz';
$string['videoplayerayname'] = 'Video Analiz Adı';
$string['videoplayerayname_help'] = 'Etkinliğin ders sayfasında görünecek adını giriniz.';
$string['videoplayerayadres'] = 'Video Adresi';
$string['videoplayerayoverlayjs'] = 'olaylar.js Adresi';
$string['videoplayerayoverlayjs_help'] = 'Eski biçim: var olaylar = {"2":["Soru1",[["S1cevap1",false],["S1cevap2",true]],0]}';
$string['pluginadministration'] = 'Video Analiz Yönetimi';
$string['pluginname'] = 'Video Analiz';

$string['videoplayeray:addinstance'] = 'Yeni bir Video Analiz ekle';
$string['videoplayeray:submit'] = 'Video Analiz izlenme kaydet';
$string['videoplayeray:view'] = 'Video Analiz izle';
$string['videoplayeray:viewreports'] = 'Video raporlarını görüntüle';

$string['complete'] = 'Tamamlandı';
$string['incomplete'] = 'Devam ediyor';
$string['smshttps'] = 'SMS API adresi HTTPS kullanmalıdır.';
$string['smssoap'] = 'SOAP SMS API için sağlayıcıya özel uyarlama gerekir.';
$string['smsfailed'] = 'SMS API isteği başarısız. Sağlayıcı ayarlarını kontrol ediniz.';
$string['participantdetail'] = 'Katılımcı izlenme geçmişi';
$string['event'] = 'İşlem';
$string['result'] = 'Sonuç';
$string['correct'] = 'Doğru';
$string['incorrect'] = 'Yanlış';
$string['noevents'] = 'Henüz oynatma veya cevap kaydı bulunmuyor.';
$string['event_sorusoruldu'] = 'Soru gösterildi';
$string['event_cevapverildi'] = 'Cevap verildi';
$string['event_izlemebasladi'] = 'İzleme başladı';
$string['event_izlemedevamedildi'] = 'İzlemeye devam edildi';
$string['event_izlemebekletildi'] = 'Video duraklatıldı';
$string['event_izlemekaydirildi'] = 'Video ileri/geri sarıldı';
$string['event_tamekran'] = 'Tam ekran değişti';
$string['event_seskes'] = 'Ses değişti';
$string['event_izlemebitti'] = 'Video sona erdi';

$string['report'] = 'Video raporu';
$string['coursereport'] = 'Dersin video raporu';
$string['allcoursesreport'] = 'Tüm derslerin video raporu';
$string['views'] = 'Tamamlanan izlenme';
$string['progress'] = 'Güncel izlenme oranı';
$string['questionssolved'] = 'Doğru cevaplanan sorular';
$string['questionanalytics'] = 'Soru bazlı başarı analizi';
$string['attempts'] = 'Deneme';
$string['successrate'] = 'Başarı oranı';

$string['question'] = 'Video sorusu';
$string['answer'] = 'Cevapla';
$string['selectanswer'] = 'Önce bir cevap seçiniz.';
$string['correctanswer'] = 'Doğru. Video devam ediyor.';
$string['wronganswer'] = 'Yanlış. Önceki bölüme dönüp tekrar izleyiniz.';
$string['continuevideo'] = 'Videoya devam et';
$string['rewindandwatch'] = 'Önceki bölüme dön ve izle';
$string['cannotseekquestion'] = 'Zorunlu soruyu doğru cevaplamadan bu bölümün ilerisine geçemezsiniz.';
$string['missingquestionsmessage'] = 'Videoyu tamamlamak için {missing} zorunlu soruyu daha doğru cevaplamalısınız.';
$string['trackingfailed'] = 'İzlenme bilgisi kaydedilemedi. Bağlantınızı kontrol edip devam etmeden sayfayı yenileyiniz.';
$string['completedmessage'] = 'Videonun tamamını {count} kez izlediniz.';
$string['incompletemessage'] = 'Videonun %{percent} kadarını izlediniz. Eksik bölümleri izlemeye devam ediniz.';
$string['filenotfound'] = 'Video dosyası bulunamadı. Etkinliği düzenleyip video yükleyiniz.';
$string['notenrolled'] = 'İzlenme kaydı için derse etkin kayıt gereklidir.';
$string['invalidprogress'] = 'Geçersiz izlenme bilgisi.';
$string['invalidoverlay'] = 'En fazla 255 karakterlik HTTP(S) adresi giriniz veya alanı boş bırakınız.';
$string['overlaypermission'] = 'Soru betiğini değiştirmek için güvenilir içerik yetkisi gerekir.';
$string['resourcecontent'] = 'Video dosyası';
$string['taskname'] = 'Video izlenme SMS hatırlatmaları';
$string['saved'] = 'İlerlemeniz kaydedildi';
$string['saving'] = 'Kaydediliyor…';
$string['autosave'] = 'İlerlemeniz otomatik kaydedilir.';
$string['resume'] = 'Kaldığım yerden devam et';
$string['previewmode'] = 'Önizleme: izlenme bilgisi kaydedilmez.';
$string['learningprogress'] = 'ÖĞRENME DURUMUNUZ';
$string['viewinggoal'] = 'Bu videonun en az %{$a->percent} kadarını {$a->count} kez izleyiniz.';
$string['questionsprogress'] = 'Sorular';

$string['questions'] = 'Zamanlı sorular';
$string['managequestions'] = 'Soruları yönet';
$string['managequestionsintro'] = 'Videoyu oynatıp istediğiniz saniyede durdurarak soru ekleyin. Zaman değerlerini dk:sn (ör. 01:30) veya saniye (90) biçiminde girebilirsiniz.';
$string['questionssavefirst'] = 'Önce etkinliği kaydedip videoyu yükleyin; ardından "Sorular" sekmesinden videoyu izleyerek zamanlı sorular ekleyebilirsiniz.';
$string['questionscountinfo'] = 'Bu etkinlikte {$a} zamanlı soru tanımlı.';
$string['questionlist'] = 'Tanımlı sorular';
$string['noquestions'] = 'Henüz soru eklenmedi. Aşağıdaki formdan yeni soru ekleyebilirsiniz.';
$string['questionnumber'] = 'Soru {no}';
$string['questionsintro'] = 'Sorunun gösterileceği zamanı belirleyin, seçenekleri girip doğru cevabı seçin.';
$string['questiontime'] = 'Gösterilme zamanı (dk:sn veya saniye)';
$string['questiontime_help'] = 'Sorunun videoda açılacağı anı 01:30 gibi dakika:saniye veya 90 gibi saniye cinsinden giriniz.';
$string['rewindtime'] = 'Yanlış cevapta dönülecek zaman';
$string['rewindtime_help'] = 'Yanlış cevap verildiğinde videonun geri sarılacağı zamanı (00:00 ile soru zamanı arasında) giriniz.';
$string['questiontype'] = 'Soru türü';
$string['qtype_multichoice'] = 'Çoktan seçmeli';
$string['qtype_truefalse'] = 'Doğru / Yanlış';
$string['optiontrue'] = 'Doğru';
$string['optionfalse'] = 'Yanlış';
$string['answernumber'] = '{$a}. seçenek';
$string['correctoption'] = 'Doğru seçenek';
$string['questionrequired'] = 'Zorunlu soru';
$string['questionrequired_desc'] = 'Öğrenci doğru cevaplamadan ileri saramasın ve videoyu tamamlayamasın';
$string['shuffleoptions'] = 'Seçenekleri karıştır';
$string['shuffleoptions_desc'] = 'Her gösterimde seçenek sırasını rastgele değiştir';
$string['feedbackcorrect'] = 'Doğru cevap açıklaması (isteğe bağlı)';
$string['feedbackwrong'] = 'Yanlış cevap açıklaması (isteğe bağlı)';
$string['addquestion'] = 'Yeni soru ekle';
$string['editquestion'] = 'Soruyu düzenle';
$string['questionadded'] = 'Soru eklendi.';
$string['questionupdated'] = 'Soru güncellendi.';
$string['questiondeleted'] = 'Soru silindi.';
$string['confirmdeletequestion'] = 'Bu soruyu silmek istediğinize emin misiniz?';
$string['currenttime'] = 'Mevcut zaman';
$string['usecurrenttime'] = 'Bu zamanı soru zamanı yap';
$string['userewindtime'] = 'Bu zamanı dönüş zamanı yap';
$string['beyondduration'] = 'Video süresini aşıyor';
$string['optionscount'] = '{$a} seçenek';
$string['legacyquestions'] = 'Gelişmiş: eski soru betiği';
$string['invalidquestiontime'] = '00:00–23:59:59 (0–86399 sn) aralığında geçerli bir zaman giriniz.';
$string['duplicatequestiontime'] = 'Bu saniyede zaten başka bir soru bulunuyor.';
$string['invalidrewind'] = 'Dönüş zamanı sıfır ile soru zamanı arasında olmalıdır.';
$string['invalidquestion'] = 'Soru metni, en az iki seçenek ve boş olmayan doğru seçenek giriniz.';
$string['toomanyquestions'] = 'En fazla 100 soru ekleyebilirsiniz.';

$string['searchuser'] = 'Katılımcı ara';
$string['downloadcsv'] = 'CSV olarak indir';
$string['noreportrows'] = 'Bu filtrelere uygun katılımcı bulunamadı.';
$string['reportintro'] = 'Tamamlayanları, soru başarılarını ve desteğe ihtiyaç duyanları görün. İzlenme oranı güncel izleme turunu gösterir.';
$string['reportsummary'] = '{$a->total} katılımcı–video kaydının {$a->complete} tanesi tamamlandı';
$string['resetprogress'] = 'Tüm izlenme ilerlemesini ve soru cevap kayıtlarını sil';

$string['setting_apiadres'] = 'SMS API Adresi';
$string['setting_kullanici'] = 'SMS API Kullanıcı';
$string['setting_sifre'] = 'SMS API Şifre';
$string['setting_gonderen'] = 'SMS Gönderici No/KısaAd';
$string['setting_apituru'] = 'API Türü';
$string['setting_smsat'] = 'Öğrenciye SMS Bilgilendirme Etkin';
$string['setting_minviews'] = 'Videolar En Az Kaç Kere İzlenmeli';
$string['setting_threshold'] = 'Videoların En Az Yüzde Kaçı İzlenmeli';
$string['setting_adminsms'] = 'Yönetici SMS Bilgilendirme Etkin';
$string['setting_adminphone'] = 'Yönetici Telefon Numarası';

$string['privacy:metadata:views'] = 'Video izlenme ilerlemesi, cevaplanan sorular ve tamamlanma sayıları.';
$string['privacy:metadata:events'] = 'Video oynatma ve soru cevaplama geçmişi.';
$string['privacy:metadata:userid'] = 'Katılımcı kimliği.';
$string['privacy:metadata:videoid'] = 'Ders modülü kimliği.';
$string['privacy:metadata:izlenme'] = 'Güncel izleme turunda izlenen saniyeler.';
$string['privacy:metadata:izlenmesayisi'] = 'Tamamlanan izlenme sayısı.';
$string['privacy:metadata:position'] = 'Saniye cinsinden son oynatma konumu.';
$string['privacy:metadata:answered'] = 'Güncel izleme turunda doğru cevaplanan soru kimlikleri.';
$string['privacy:metadata:eventdata'] = 'Oynatma olayları, sorular, cevaplar, zaman damgaları ve geçmiş kayıtlar.';
$string['privacy:metadata:sms'] = 'Yapılandırılmış SMS sağlayıcısına gönderilen isteğe bağlı hatırlatmalar.';
$string['privacy:metadata:phone'] = 'Alıcı telefon numarası.';
$string['privacy:metadata:message'] = 'Alıcı adını ve eksik video sayısını içeren hatırlatma metni.';
