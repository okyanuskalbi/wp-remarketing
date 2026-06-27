=== WP Remarketing ===
Contributors: emre
Tags: remarketing, google ads, gtm, meta pixel, tiktok, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.4.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Remarketing etiket/pixel yöneticisi — Google Ads, Google Tag Manager, Meta Pixel ve TikTok için tek panelden onay duyarlı etiket enjeksiyonu.

== Description ==

WP Remarketing, sık kullanılan remarketing etiketlerini tek bir ayar sayfasından yönetmenizi sağlar:

* Google Ads (gtag.js)
* Google Tag Manager (head + noscript)
* Meta (Facebook) Pixel — PageView dahil
* TikTok Pixel

Özellikler:

* Boş bırakılan kimlikler enjekte edilmez.
* Onay (consent) modu: kapalı / çerez tabanlı / basit banner.
* Giriş yapmış yöneticileri etiketlerden hariç tutma.
* WooCommerce: ürün görüntüleme (ViewContent) ve satın alma (Purchase / CompletePayment) olayları.

Bu sürüm **Faz 1**: merkezi etiket yönetimi, onay kapısı ve temel WooCommerce olayları. Sunucu taraflı olaylar (Conversions API), gelişmiş olay eşleme ve raporlama sonraki fazlara bırakılmıştır.

== Installation ==

1. `wp-remarketing` klasörünü `wp-content/plugins/` altına yükleyin (veya Eklentiler > Yeni Ekle > Eklenti Yükle ile zip'i kurun).
2. Eklentiyi etkinleştirin.
3. Ayarlar > WP Remarketing sayfasından kimliklerinizi girin.

== Changelog ==

= 1.4.2 =
* Otomatik güncelleme testi: depo herkese açık yapıldı; güncellemeler artık panelden tek tıkla gelir.

= 1.4.1 =
* Yeni: İstatistik sayfasına "Güncelleme durumu" kutusu — kurulu vs GitHub sürümü ve bağlantı hatasını gösterir (tanılama).

= 1.4.0 =
* Görsel: İstatistik kartları renkli ikonlu yeniden tasarlandı; tablolar yuvarlatıldı, platform rozet olarak gösteriliyor; Pano widget'ı görselleştirildi.

= 1.3.3 =
* Test: otomatik güncelleme doğrulaması (force-check ile anında bildirim).

= 1.3.2 =
* Düzeltme: "Yeniden kontrol et" artık güncelleme önbelleğini de yeniler (force-check); kurulum/kaldırmada önbellek temizlenir. Yeni sürümler anında görünür.

= 1.3.1 =
* Test: otomatik güncelleme doğrulaması; yazar adı güncellendi.

= 1.3.0 =
* Yeni: Otomatik güncelleme — eklenti GitHub Releases'i kontrol eder, yeni sürümde panelde "Güncelle" çıkar (harici kütüphane yok).

= 1.2.1 =
* Yeni: WordPress Pano'ya özet widget'ı — son 30 günün ziyaretçi, oturum, sepette bırakan, satış, ciro ve bot rakamları; tam istatistik sayfasına link.

= 1.2.0 =
* Yeni: İlk taraf analitik — ziyaretçi/oturum, UTM kaynak, il/ilçe, platform (Mobil/Tablet/Masaüstü) ve sepet/satış takibi.
* Yeni: "Sepette bırakanlar" listesi — sepete ekleyip almamış oturumlar; il/ilçe, platform, kaynak/UTM, kampanya bilgisiyle.
* Yeni: Gerçek ziyaretçi ile bot trafiği ayrıştırıldı; bot trafiği ayrı tabloda (sayfa bazlı isabet).
* Gizlilik: ham IP saklanmaz; yalnızca salt hash + IP'den çözülen il/ilçe tutulur.

= 1.1.1 =
* Uyumluluk: WooCommerce HPOS (custom order tables) uyumluluğu beyan edildi.
* Uyumluluk: gtag global'i yalnızca yoksa tanımlanıyor — GA / Site Kit / GTM eklentilerinin gtag'ini ezmiyor.
* Yeni filtre: wprem_has_consent — CookieYes/Complianz/Borlabs gibi onay eklentileri etiketleri geçitleyebilir.
* Yeni filtre: wprem_is_bot — Wordfence/Cloudflare gibi araçlar bot kararını sağlayabilir.
* Belgeler: pixel çiftlenmesi uyarısı ve eklenti uyumluluk notları eklendi.

= 1.1.0 =
* Yeni (Faz 2): İlk-taraf istatistik modülü. Async beacon ile sayfa görüntüleme + UTM (kaynak/mecra/kampanya) yakalama, WooCommerce sepete ekleme ve satın alma olaylarını oturuma bağlama.
* Yeni: Bot/insan ayrımı (user-agent tabanlı); botlar raporlarda hariç tutulur.
* Yeni: IP konumundan il/şehir çözümü (ip-api). Ham IP saklanmaz; ziyaretçi yalnızca sha256(ip+ua+salt) ile anonimleştirilir.
* Yeni: Admin "WP Remarketing İstatistik" sayfası — tekil ziyaretçi/oturum/sepet/satış/ciro/dönüşüm kartları + kaynak, kampanya ve il bazında kırılım (7/30/90 gün).
* Yeni ayarlar: istatistik toplama ve il/şehir çözümü açma/kapama.

= 1.0.1 =
* Düzeltme: "banner" onay modunda banner artık ilk ziyaretçiye gösteriliyor (önceden onay kilitleniyordu).
* Düzeltme: WooCommerce satın alma olayı sipariş başına yalnızca bir kez tetikleniyor (sayfa yenilemede tekrar saymıyor).
* Eklendi: Google Ads artık ViewContent/Purchase olaylarını gtag ile alıyor (önceden yalnızca sayfa düzeyi remarketing vardı).
* Düzeltme: Çeviriler "init" kancasında yükleniyor (WP 6.7+ uyarısı giderildi).
* İyileştirme: ViewContent değeri wc_get_price_to_display ile, content_type eklendi; footer kancalarındaki guard hatası giderildi.
* CI: git archive ile temiz paketleme, sürüm yarışına karşı concurrency, readme Stable tag doğrulaması, orphan-tag önlemi.

= 1.0.0 =
* İlk sürüm (Faz 1): Google Ads, GTM, Meta Pixel, TikTok etiketleri; onay modu; WooCommerce ViewContent/Purchase olayları.
