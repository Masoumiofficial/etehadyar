# EtehadWP AI Writer — Changelog Supernatural

## 6.0.0 — Supernatural OS — 2026-08-06
**انفجار پس از 5.23.0 Writer — از ابزار به سیستم‌عامل**

### ✨ Supernatural Layer
- Nebula Command Center: داشبورد هولوگرافیک با 4 KPI زنده + ویجت فرصت GSC + سلامت
- Portal ورود ماورایی: سحابی متحرک + لوگوی اتحاد + JARVIS فارسی (Skip پس از 3 بار)
- Command Palette (⌘K): فرمان صوتی/متنی برای تمام ماژول‌ها
- Supernatural Toggle: سوییچ حالت ماورایی در هدر
- JARVIS شناور: دستیار همیشه بیدار

### 🧠 Site Brain 2.0
- جدول `wp_eaiw_vectors` + chunk 900 کاراکتری + content_hash
- Embedding شبیه‌سازی determinist (بدون هزینه) + آماده برای OpenAI Embeddings واقعی
- RAG: `EAIW_RAG::search()` + `context_for_prompt()` برای تزریق به Provider
- ایندکس دسته‌ای AJAX (20 پست در هر درخواست) + progress نئونی

### 🤖 Agent Army
- جدول `wp_eaiw_agents` + 4 Agent: seo_watcher, gardener, link_weaver, trend_hunter
- Cron 15 دقیقه + Action Scheduler سازگار + Trace ID
- هر Agent: `should_run()` + `run()` + `last_result` JSON
- UI toggle + اجرای دستی + لاگ

### ✨ Omnichannel Factory
- `EAIW_Omnichannel_Factory::generate_from_post()` — 7 خروجی

### 🎨 Vision Studio
- اتصال Flux/Stability/OpenAI Images — با fallback SVG Placeholder ماورایی
- ذخیره مستقیم در Media Library + Alt فارسی

### 🏗️ Architect
- `EAIW_Architect::generate()` — Hero + Features + CTA به‌صورت Gutenberg blocks
- ذخیره به‌عنوان Draft Page + meta

### 🛒 Woo God Mode
- `EAIW_Woo_GodMode::enhance_product()` — long_desc + FAQ + SEO

### 🔮 Oracle
- پیش‌بینی ریسک CTR/Position + forecast

### 🛡️ Guardian
- جدول `wp_eaiw_jobs` + اسکن ساعتی + transient

### 💬 ChatSoul
- REST `/eaiw/v1/chat` + ویجت شناور + لاگ `wp_eaiw_chatsoul_logs`

### ⚡ Nexus
- جدول `wp_eaiw_automations` + 3 اتوماسیون پیش‌فرض

### 🔐 Vault
- AES-256-GCM با AUTH_KEY + SECURE_AUTH_SALT — مثل 5.1.0
- کلیدها هرگز به JS نمی‌رود

### حفظ سازگاری 5.23
- تمام Provider Profiles, GSC/GA4/SERP, Knowledge Hub, Calendar, Cluster, Cannibalization, Video Studio حفظ شد
- Legacy تب + ریدایرکت
