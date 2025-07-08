# SAMP Sunucu Monitörü'ne Katkıda Bulunma

SAMP Sunucu Monitörü projesine olan ilginiz için teşekkür ederiz! Topluluktan gelen katkıları memnuniyetle karşılıyoruz.

## 🚀 Başlangıç

1. Repoyu fork edin
2. Fork'unuzu klonlayın: `git clone https://github.com/thislaex/samp-query.git`
3. Özellik dalı oluşturun: `git checkout -b feature/ozellik-adiniz`
4. Değişikliklerinizi yapın
5. Değişikliklerinizi kapsamlı şekilde test edin
6. Değişikliklerinizi commit edin: `git commit -m 'Bir özellik ekle'`
7. Dalınıza push edin: `git push origin feature/ozellik-adiniz`
8. Pull Request gönderin

## 📋 Geliştirme Rehberi

### Kod Stili
- PSR-12 PHP kodlama standartlarını takip edin
- Anlamlı değişken ve fonksiyon isimleri kullanın
- Karmaşık mantık için açık, öz yorumlar yazın
- Fonksiyonları küçük ve odaklanmış tutun

### PHP Gereksinimleri
- PHP 8.0 veya üzeri
- Gerekli extensionlar: `sockets`, `json`
- Mümkün olduğunda birden fazla PHP versiyonu ile test edin

### Frontend Rehberi
- Styling için TailwindCSS sınıflarını kullanın
- Mobil uyumluluğu sağlayın
- Farklı tarayıcılarda test edin
- JavaScript fonksiyonlarını basit ve okunabilir tutun

### Test
- Birden fazla SAMP sunucusu ile test edin
- UDP bağlantısının çalıştığını doğrulayın
- Hata yönetimi senaryolarını kontrol edin
- API endpoint'lerini kapsamlı şekilde test edin

## 🐛 Hata Raporları

Hata rapor ederken lütfen şunları ekleyin:
- PHP versiyonu
- Sunucu ortamı detayları
- Hatayı yeniden üretme adımları
- Beklenen vs gerçek davranış
- Hata mesajları (varsa)

## 💡 Özellik İstekleri

Yeni özellikler için:
- Önce mevcut issue'ları kontrol edin
- Açık kullanım senaryosu açıklaması sağlayın
- Uygulama karmaşıklığını göz önünde bulundurun
- Tartışma ve geri bildirime açık olun

## 🔄 Pull Request Süreci

1. Gerekirse dokümantasyonu güncelleyin
2. Yeni işlevsellik için test ekleyin veya güncelleyin
3. CI kontrollerinin geçtiğinden emin olun
4. Maintainer'lardan inceleme isteyin
5. Geri bildirimleri hızlıca ele alın

## 📝 Commit Mesaj Rehberi

- Açık, tanımlayıcı commit mesajları kullanın
- Bir fiille başlayın (Ekle, Düzelt, Güncelle, Kaldır)
- İlk satırı 50 karakterin altında tutun
- Gerekirse detaylı açıklama ekleyin

Örnek:
```
Oyuncu listesi görüntüleme özelliği ekle

- Detaylı oyuncu bilgisi için SAMP 'd' sorgusu uygula
- Oyuncu listesi için responsive tablo ekle
- Oyuncu skoru ve ping bilgilerini dahil et
```

## 🎯 Katkı Alanları

- Sunucu izleme özellikleri
- UI/UX iyileştirmeleri
- Performans optimizasyonları
- Dokümantasyon güncellemeleri
- Hata düzeltmeleri
- Test iyileştirmeleri

## 📞 Sorularınız mı var?

Sorular için issue açmaktan çekinmeyin veya topluluk tartışmalarımıza katılın.

Katkıda bulunduğunuz için teşekkürler! 🙏
