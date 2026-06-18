# WP Remarketing

Remarketing etiket/pixel yöneticisi WordPress eklentisi. Google Ads, Google Tag
Manager, Meta Pixel ve TikTok kimliklerini tek panelden, onay (consent) duyarlı
biçimde sitenize enjekte eder. WooCommerce ürün görüntüleme ve satın alma
olaylarını destekler.

> **Faz 1** — merkezi etiket yönetimi, onay kapısı, temel WooCommerce olayları.

## Kurulum

**Yöntem 1 — WordPress paneli:** Eklentiler → Yeni Ekle → Eklenti Yükle ile bu
deponun zip'ini yükleyin (repo kökü = eklenti kökü).

**Yöntem 2 — repo olarak:** depoyu `wp-content/plugins/wp-remarketing` altına
klonlayın, ardından panelden etkinleştirin.

Etkinleştirdikten sonra **Ayarlar → WP Remarketing** sayfasından kimliklerinizi
girin. Boş bırakılan alanlar enjekte edilmez.

## Dosya yapısı

```
wp-remarketing.php              # Ana eklenti dosyası (header + bootstrap)
uninstall.php                   # Silinince ayarları temizler
includes/
  class-wprem-plugin.php        # Bileşenleri bağlar
  class-wprem-settings.php      # Ayar deposu + admin sayfası + sanitizasyon
  class-wprem-tags.php          # Front-end etiket/olay enjeksiyonu
  class-wprem-db.php            # İstatistik tablosu (kurulum/upgrade)
  class-wprem-tracker.php       # Beacon + UTM/geo/bot + WooCommerce olay kaydı
  class-wprem-stats.php         # İstatistik raporları + admin sayfası
  views/settings-page.php       # Ayar sayfası şablonu
  views/stats-page.php          # İstatistik sayfası şablonu
assets/admin.css                # Admin stili
assets/tracker.js               # İlk-taraf pageview/UTM beacon
readme.txt                      # WordPress.org readme
```

## İstatistik modülü (Faz 2)

Eklenti, pixel etiketlerinden bağımsız olarak **ilk-taraf** istatistik de toplar
(Ayarlar → WP Remarketing → İstatistik toplama):

* Async beacon her sayfa görüntülemeyi ve **UTM** (kaynak/mecra/kampanya/term/içerik)
  ilk-dokunuş atıfını kaydeder.
* WooCommerce **sepete ekleme** ve **satın alma** olayları ziyaretçinin oturumuna
  bağlanır, böylece kampanya bazında huni çıkar.
* **Bot/insan** ayrımı user-agent ile yapılır; botlar raporlarda hariç tutulur.
* **İl/şehir** IP konumundan (ip-api) çözülür. Ham IP **saklanmaz**; ziyaretçi
  yalnızca `sha256(ip + ua + salt)` ile anonimleştirilir.
* **Ayarlar → WP Remarketing İstatistik** sayfası 7/30/90 günlük kartlar ve
  kaynak/kampanya/il kırılımı gösterir.

Veriler özel `{prefix}wprem_events` tablosunda tutulur; eklenti silindiğinde tablo
ve ayarlar temizlenir.

## Sürüm çıkarma (otomatik)

Release tamamen otomatiktir — elle tag atmaya gerek yok:

1. `wp-remarketing.php` header'ındaki `Version:` değerini artır (kaynak budur).
2. `readme.txt` içindeki `Stable tag` ve changelog'u güncelle.
3. Commit + `main`'e push.

Release workflow header'daki sürümü okur; o sürüm için `v<sürüm>` tag'i yoksa
otomatik tag atar, kurulabilir zip'i derler ve sürüm notlarıyla GitHub Release
yayınlar. Sürüm değişmediyse (tag zaten varsa) adım atlanır. Workflow'u GitHub
Actions sekmesinden elle de tetikleyebilirsin (`workflow_dispatch`).

CI (`build.yml`) ayrıca her push/PR'da PHP 7.4 + 8.2 söz dizimi denetimi yapar.

## Güvenlik notları

* Tüm kimlikler kayıttan önce desen kontrolünden geçer (`AW-`, `GTM-`, sayısal
  Meta ID vb.), çıktıda `esc_attr` / `esc_js` ile yeniden kaçışlanır.
* `manage_options` yetkisi olmayan kullanıcı ayar sayfasını göremez/kaydedemez.
* Onay modu açıkken etiketler yalnızca onay çerezi `1` olduğunda yüklenir.
