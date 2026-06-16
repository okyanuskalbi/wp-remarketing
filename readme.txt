=== WP Remarketing ===
Contributors: emre
Tags: remarketing, google ads, gtm, meta pixel, tiktok, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 1.0.0 =
* İlk sürüm (Faz 1): Google Ads, GTM, Meta Pixel, TikTok etiketleri; onay modu; WooCommerce ViewContent/Purchase olayları.
