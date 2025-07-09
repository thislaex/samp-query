# 🎮 GTA SAMP/Open.MP Sunucu Monitörü

Modern ve responsive web tabanlı GTA San Andreas Multiplayer (SAMP) ve Open.MP sunucu izleme aracı. Gerçek zamanlı sunucu verilerini doğrudan UDP query protokolü ile çeker ve güzel, mobil uyumlu arayüzde sunar.

[![GitHub License](https://img.shields.io/badge/Lisans-MIT-green.svg)](https://github.com/thislaex/samp-query/blob/main/LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-8.0+-777BB4.svg)](https://php.net)
[![SAMP Protocol](https://img.shields.io/badge/SAMP-UDP%20Query-blue.svg)](https://sampwiki.blast.hk/wiki/Query_Mechanism)
[![Open.MP Support](https://img.shields.io/badge/Open.MP-Supported-orange.svg)](https://open.mp)
[![GitHub Stars](https://img.shields.io/github/stars/thislaex/samp-query.svg)](https://github.com/thislaex/samp-query/stargazers)
[![GitHub Issues](https://img.shields.io/github/issues/thislaex/samp-query.svg)](https://github.com/thislaex/samp-query/issues)

## ✨ Özellikler

- 🔥 **Gerçek Zamanlı Veri**: Doğrudan UDP SAMP/Open.MP query protokolü uygulaması
- 🎨 **Modern Arayüz**: TailwindCSS ve Alpine.js ile responsive tasarım
- ⚡ **Hızlı Performans**: Optimize edilmiş UDP socket bağlantıları
- 🔍 **Akıllı Arama**: Sunucu adı, IP, oyun modu veya sunucu tipine göre filtreleme
- 📱 **Mobil Uyumlu**: Tüm cihazlarda mükemmel görüntü
- 🔄 **Otomatik Yenileme**: Her 30 saniyede otomatik güncelleme
- 📊 **Detaylı Bilgi**: Oyuncu sayısı, ping, oyun modu, dil ve daha fazlası
- 🚫 **Harici API Yok**: Güvenilirlik için doğrudan protokol uygulaması
- 🆕 **Open.MP Desteği**: SAMP ve Open.MP sunucularını aynı arayüzde izleme

## 🚀 Canlı Demo

[Demo'yu Görüntüle](https://samp.laex.com.tr)

## 📸 Ekran Görüntüleri

![SAMP/Open.MP Sunucu Monitörü](https://samp.laex.com.tr/img/samp-query.png)

## 🛠️ Kurulum

### Ön Gereksinimler

- PHP 8.0 veya üzeri
- Socket extension (`php-sockets`)
- JSON extension (`php-json`)
- Web sunucusu (Apache/Nginx)

### Hızlı Kurulum

1. **Repoyu klonlayın:**
```bash
git clone https://github.com/thislaex/samp-query.git
cd samp-query
```

2. **Web sunucunuza deploy edin:**
```bash
# Apache için
cp -r * /var/www/html/samp/

# Nginx için
cp -r * /usr/share/nginx/html/samp/

# XAMPP/WAMP (Windows) için
# Dosyaları htdocs/samp/ klasörüne kopyalayın
```

3. **PHP extensionlarını kontrol edin:**
```bash
php -m | grep socket
php -m | grep json
```

4. **Sunucularınızı yapılandırın:**
`index.php` dosyasındaki `$servers` dizisini düzenleyin:

```php
private $servers = [
    ['ip' => 'sunucu-ip-adresi', 'port' => 7777, 'type' => 'samp'],
    ['ip' => 'openmp-sunucu-ip', 'port' => 7777, 'type' => 'openmp'],
];
```

5. **Tarayıcıda açın:**
```
http://localhost/samp/
```

## 📋 API Kullanımı

Uygulama bir JSON API endpoint sağlar:

```http
GET /path/to/samp/?api=servers
```

**Örnek Yanıt:**
```json
{
    "success": true,
    "servers": [
        {
            "hostname": "[0.3.7/DL] SAMP Sunucunuz",
            "players": 42,
            "maxplayers": 100,
            "gamemode": "Roleplay/Freeroam",
            "language": "Turkish",
            "password": false,
            "ping": 85,
            "ip": "192.168.1.100",
            "port": 7777,
            "source": "udp_query",
            "server_type": "samp",
            "is_openmp": false,
            "query_time_ms": 45.2
        },
        {
            "hostname": "Open.MP Test Server",
            "players": 25,
            "maxplayers": 100,
            "gamemode": "Freeroam/DM",
            "language": "English",
            "password": false,
            "ping": 65,
            "ip": "192.168.1.101",
            "port": 7777,
            "source": "udp_query",
            "server_type": "openmp",
            "is_openmp": true,
            "query_time_ms": 38.1
        }
    ],
    "count": 2,
    "query_time_ms": 156.7,
    "timestamp": "2025-07-09 15:30:45"
}
```

## ⚙️ Yapılandırma

### Sunucu Ekleme/Çıkarma

`index.php` dosyasındaki `$servers` dizisini düzenleyin:

```php
private $servers = [
    ['ip' => '51.254.139.153', 'port' => 7777, 'type' => 'samp'],     // SAMP Sunucu
    ['ip' => '89.45.44.38', 'port' => 7777, 'type' => 'samp'],       // SAMP Sunucu
    ['ip' => 'openmp-server.com', 'port' => 7777, 'type' => 'openmp'], // Open.MP Sunucu
];
```

**Sunucu Tipleri:**
- `'type' => 'samp'` - SA-MP sunucuları için
- `'type' => 'openmp'` - Open.MP sunucuları için

### Timeout Ayarları

UDP socket timeout değerlerini değiştirin:

```php
// 2 saniyeden 5 saniyeye timeout değişimi
@socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 0));
```

### Otomatik Yenileme Aralığı

JavaScript'te otomatik yenileme zamanlamasını değiştirin:

```javascript
// 30 saniyeden 60 saniyeye değiştirme
setInterval(() => {
    if (!this.loading) {
        this.refreshServers();
    }
}, 60000);
```

## 🏗️ Teknoloji Yığını

- **Backend**: PHP 8.0+
- **Frontend**: HTML5, TailwindCSS, Alpine.js
- **Protokol**: SAMP/Open.MP UDP Query Protocol
- **İkonlar**: Font Awesome 6
- **Stil**: TailwindCSS CDN

## 📖 SAMP/Open.MP Query Protokolü

Bu proje hem SAMP hem de Open.MP için UDP query protokolünü uygular:

1. **Paket Yapısı**: `SAMP + IP(4 bytes) + Port(2 bytes) + 'i'`
2. **Yanıt Ayrıştırma**: Little-endian byte okuma
3. **Veri Alanları**: Şifre durumu, oyuncu sayısı, hostname, gamemode, dil
4. **Uyumluluk**: SAMP 0.3.7/DL ve Open.MP sunucularıyla tam uyumlu

### Protokol Detayları

```php
// Query paket formatı
$packet = 'SAMP' .              // Header (4 bytes)
          pack('CCCC', ...explode('.', $ip)) . // IP adresi (4 bytes)  
          pack('v', $port) .     // Port little-endian (2 bytes)
          'i';                   // Info query türü (1 byte)
```

## 🤝 Katkıda Bulunma

Katkılarınızı bekliyoruz! Lütfen şu adımları takip edin:

1. **Repoyu fork edin**
```bash
# GitHub'da thislaex/samp-query repo'sunu fork edin
# Ardından kendi fork'unuzu klonlayın
git clone https://github.com/KULLANICI_ADINIZ/samp-query.git
cd samp-query
```

2. **Geliştirme dalı oluşturun**
```bash
git checkout -b feature/yeni-ozellik
# veya
git checkout -b bugfix/hata-duzeltmesi
```

3. **Değişikliklerinizi yapın ve test edin**
```bash
# Kodunuzu yazın ve test edin
# Hem SAMP hem de Open.MP sunucuları ile test etmeyi unutmayın
```

4. **Değişiklikleri commit edin**
```bash
git add .
git commit -m "feat: yeni özellik eklendi"
# veya
git commit -m "fix: sunucu bağlantı hatası düzeltildi"
```

5. **Fork'unuza push edin**
```bash
git push origin feature/yeni-ozellik
```

6. **Pull Request oluşturun**
- GitHub'da https://github.com/thislaex/samp-query sayfasına gidin
- "Pull Request" butonuna tıklayın
- Değişikliklerinizi açıklayın
- Pull Request'i gönderin

### Geliştirme Rehberi

- **Kod Standartları**: PSR-12 kodlama standartlarını takip edin
- **Commit Mesajları**: [Conventional Commits](https://conventionalcommits.org/) formatını kullanın
  - `feat:` - Yeni özellik
  - `fix:` - Hata düzeltmesi
  - `docs:` - Dokümantasyon güncelleme
  - `style:` - CSS/UI değişiklikleri
  - `refactor:` - Kod düzenlemesi
- **Testing**: Değişikliklerinizi farklı SAMP ve Open.MP sunucuları ile test edin
- **Responsive**: Mobil uyumluluğu kontrol edin

## 🐛 Sorun Giderme

### Yaygın Sorunlar

| Sorun | Çözüm |
|-------|-------|
| Sunucular görünmüyor | PHP sockets extension'ının kurulu olup olmadığını kontrol edin |
| UDP bağlantısı başarısız | Firewall'ın UDP bağlantılarına izin verdiğini doğrulayın |
| Yavaş sorgular | Timeout değerlerini ayarlayın veya ağı kontrol edin |
| API boş döndürüyor | Sunucuların online ve erişilebilir olduğundan emin olun |
| Open.MP sunucu görünmüyor | Sunucu tipini 'openmp' olarak ayarladığınızdan emin olun |

### Firewall Yapılandırması

**Linux (iptables):**
```bash
sudo iptables -A OUTPUT -p udp --dport 7777 -j ACCEPT
```

**Windows Firewall:**
```powershell
New-NetFirewallRule -DisplayName "SAMP/OpenMP UDP" -Direction Outbound -Protocol UDP -LocalPort 7777
```

## 📈 Yol Haritası

- [ ] 👥 Oyuncu listesi görüntüleme
- [ ] ⭐ Sunucu favoriler sistemi
- [ ] 📊 Geçmiş istatistikler ve grafikler
- [ ] 🔐 Sunucu yönetimi için admin paneli
- [ ] 🌍 Çoklu dil desteği
- [ ] 🌙 Karanlık mod teması
- [ ] ⏱️ Sunucu uptime takibi
- [ ] 📧 Sunucu durumu bildirimleri
- [ ] 🗺️ Sunucu konum haritası
- [ ] 📱 Progressive Web App (PWA)

## 📄 Lisans

Bu proje MIT Lisansı altında lisanslanmıştır - detaylar için [LICENSE](LICENSE) dosyasına bakın.

## 🙏 Teşekkürler

- [SAMP Topluluğu](https://sampwiki.blast.hk/wiki/Main_Page) - Protokol dokümantasyonu için
- [Open.MP Ekibi](https://open.mp) - Open.MP desteği ve dokümantasyon için
- [TailwindCSS](https://tailwindcss.com/) - Harika CSS framework için
- [Alpine.js](https://alpinejs.dev/) - Hafif JavaScript framework için
- [Font Awesome](https://fontawesome.com/) - Güzel ikonlar için

## 📞 İletişim

- **GitHub**: [@thislaex](https://github.com/thislaex)
- **Proje**: [thislaex/samp-query](https://github.com/thislaex/samp-query)
- **Issues**: [Bug Report & Feature Request](https://github.com/thislaex/samp-query/issues)
- **Discord**: thislaex

## ⭐ Destek

Bu proje size yardımcı olduysa, GitHub'da yıldız vermeyi düşünün!

---

**SAMP & Open.MP topluluğu için ❤️ ile yapılmıştır**