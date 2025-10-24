# Bilet Satın Alma Sistemi

Bu proje PHP + SQLite ile hazırlanmıştır ve Docker ile çalıştırılabilir.  

## Özellikler
- Kullanıcı giriş/kayıt sistemi
- Bilet satın alma ve iptal
- PDF bilet oluşturma (FPDF)
- Admin ve firma admin panelleri
- SQLite veritabanı

## Gereksinimler
- Docker
- Docker Compose

## Kurulum ve Çalıştırma
Projeyi GitHub’dan klonlayın ve proje klasörüne girin:

```bash
git clone https://github.com/mrFurkann/bilet-satin-alma.git
cd bilet-satin-alma
docker-compose up --build 
```

## Siteye Erişim
- Tarayıcıdan açın: http://localhost:8080

## Siteye Erişim
- Test İçin Admin Hesabı Bulunmaktadır
- email: admin@gmail.com  password: admin123
- Sistemde bir firma ve bir sefer vardır Bartın-Trabzon 2025-11-15 tarihli. Bu tarihten sonra bu sefer listelenmeyecektir. Dilerseniz firma admini oluşturup sefer ekleyebilirsiniz.

