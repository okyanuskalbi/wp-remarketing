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
  views/settings-page.php       # Ayar sayfası şablonu
assets/admin.css                # Admin stili
readme.txt                      # WordPress.org readme
```

## Güvenlik notları

* Tüm kimlikler kayıttan önce desen kontrolünden geçer (`AW-`, `GTM-`, sayısal
  Meta ID vb.), çıktıda `esc_attr` / `esc_js` ile yeniden kaçışlanır.
* `manage_options` yetkisi olmayan kullanıcı ayar sayfasını göremez/kaydedemez.
* Onay modu açıkken etiketler yalnızca onay çerezi `1` olduğunda yüklenir.
