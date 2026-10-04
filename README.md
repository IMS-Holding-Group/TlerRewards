# TlerRewards

موقع برنامج تسويق بالعمولة. الوصف في الصفحة: الموقع الرسمي لبرنامج التسويق بالعمولة التابع لتلر الشهراني. فيه نموذج متقدمين، ودخول مسوق ولوحته، ولوحة إدارة في مجلد باسم طويل، وواجهة PHP على MySQL.

README السابق يصف مجلدا اسمه `admin` وملف `.htpasswd` وكلمات مرور افتراضية. المجلد الحالي للإدارة ليس بهذا الاسم، وملف `.htpasswd` غير موجود في الملفات الحالية. كلمات المرور لا تُعاد في هذه الوثيقة.

# الجزء الأول - التعريف والفهم العام

## 1. ما هو المشروع؟

ثلاث واجهات:

| الواجهة | الملفات | من يستخدمها |
|---|---|---|
| الموقع العام | `index.html` و`css/style.css` و`js/script.js` | زائر يسجل كمتقدم |
| المسوق | `marketer_login.html` و`marketer_dashboard.html` | مسوق مفعّل |
| الإدارة | مجلد في الجذر اسمه سلسلة طويلة | من يفتح ذلك المسار ويملك حساب `admin` |

المجلد ذو الاسم الطويل موجود. دوره من الكود: صفحات `index.html` و`login.html` وتنسيق `css/admin.css` وسكربتات `js/admin.js` و`js/admin-login.js` و`js/bot-detection.js` وملف `.htaccess`. السكربتات تستدعي `../apis/admin_auth.php` وبقية واجهات الإدارة. الاسم نفسه لا يُعرض هنا كبيانات تدخل أو سر يُعاد استخدامه.

## 2. لماذا يوجد هذا المشروع؟

نص الصفحة يتحدث عن الانضمام للتسويق بالعمولة والترويج وتسجيل البيانات. وثيقة عقد عمولات منفصلة غير موجودة. README السابق يلخص نفس الفكرة: متقدمون، إدارة، مسوقون، عملاء، عمولات.

## 3. من يستخدمه؟

| الدور | المصدر |
|---|---|
| زائر / متقدم | نموذج `#registration` يكتب في جدول `applicants` بحالة افتراضية `pending` |
| مسوق | جدول `marketers` والدخول يشترط `is_active = 1` |
| أدمن | جدول `users` حيث `role = 'admin'` |

## 4. ماذا يستطيع النظام أن يفعل؟

- شرح البرنامج في أقسام `#hero` و`#about` و`#stats` و`#registration`.
- أرقام الواجهة ثابتة في HTML عبر `data-target`: 15 لمستفيد نشط، و305 لريال سعودي إجمالي الأرباح، و5 لمتوسط العمولة. النص المجاور يقول عمولة تبدأ من 5%. هذه ليست استعلاما من القاعدة.
- تسجيل متقدم: الاسم، العمر (الحد الأدنى في الحقل 13)، الجنسية، إنستغرام، البريد، الهاتف.
- إدارة المتقدمين: عرض وتعديل وحذف من `admin_applicants.php`.
- إدارة المسوقين: إنشاء وتعديل وحذف وتبديل النشاط من `admin_marketers.php`. كلمة مرور المسوق تُجزأ عند الإنشاء.
- سجل `logs` من `admin_logs.php`.
- المسوق يرى إحصاءات وعملاء، ويضيف عميلا أو يعدّله أو يحذفه.
- مبلغ العمولة في الإضافة: `(amount * commission_percentage) / 100`.
- حماية واجهة الإدارة: `.htaccess` يضع `X-Robots-Tag` ويمنع بعض وكلاء المستخدم، و`bot_protection.php` و`bot_verification.php` و`bot-detection.js`.
- أيقونة التبويب في `index.html` وفي صفحة الإدارة: `assets/images/Logo.ico`.

ألوان README السابق ما زالت قابلة للمراجعة في CSS. الملف يذكر `#072242` و`#041428` و`#1579c7` كألوان سابقة. تأكيد كل قيمة يكون من `css/style.css` عند التعديل؛ لم تُنسخ لوحة كاملة هنا.

## 5. كيف يعمل النظام؟

```text
index.html --fetch--> apis/register_applicant.php --> applicants
marketer_login.html --> apis/marketer_auth.php --> جلسة مسوق
marketer_dashboard.html --> apis/marketer_dashboard.php --> clients
مجلد الإدارة --> apis/admin_*.php --> users / applicants / marketers / logs
setup_database.php --> ينشئ القاعدة من database_schema.sql ويدرج أدمن
```

## 6. أمثلة واقعية

### تقديم طلب

1. الزائر يملأ النموذج.
2. `register_applicant.php` يدرج الاسم والعمر والجنسية والإنستغرام والبريد والهاتف.
3. الحالة تبقى القيمة الافتراضية في SQL: `pending`.

### إضافة عميل

1. المسوق الداخل يرسل `action=add_client` مع الاسم وتفاصيل الطلب والمبلغ ونسبة العمولة وحالة الدفع.
2. الكود يرفض المبلغ أو النسبة إذا كانت 0 أو أقل.
3. يحسب `commission_amount` ويحفظ الصف مرتبطا بـ `marketer_id` في الجلسة.

### إنشاء مسوق من الإدارة

`action=create` في `admin_marketers.php` يخزن `password_hash` بـ `PASSWORD_DEFAULT`. التفعيل حقل `is_active`. الدخول يفشل للحساب غير النشط لأن الشرط `is_active = 1`.

## 7. رحلة المستخدم

المتقدم: الصفحة الرئيسية، النموذج، رسالة `#form-message`. لا لوحة بعد الإرسال في الملفات التي فُحصت.

المسوق: `marketer_login.html` بالبريد أو الهاتف وكلمة المرور، ثم `marketer_dashboard.html` التي تفحص `check_session`.

الأدمن: صفحة الدخول داخل المجلد ذي الاسم الطويل، ثم `check_session` على `admin_auth.php`، ثم إدارة الجداول.

## 8. الوحدات والأقسام

| الوحدة | الوظيفة |
|---|---|
| الصفحة العامة | عرض ونموذج |
| `apis/register_applicant.php` | حفظ المتقدم |
| `apis/marketer_auth.php` | دخول وخروج وفحص جلسة المسوق |
| `apis/marketer_dashboard.php` | إحصاء وعملاء |
| `apis/admin_auth.php` | دخول الأدمن |
| `apis/admin_applicants.php` | حذف وتحديث متقدم |
| `apis/admin_marketers.php` | إنشاء وتحديث وحذف وتبديل حالة |
| `apis/admin_logs.php` | قراءة السجل |
| `apis/config.php` | PDO والتنظيف والسجل |
| `apis/bot_protection.php` | فحص وكيل المستخدم |
| مجلد الإدارة | واجهة HTML/JS |
| `database_schema.sql` | الجداول |
| `setup_database.php` | إنشاء القاعدة وحساب أدمن |

## 9. الشركات والكيانات

كيان تشغيلي واحد في النصوص: برنامج تلر الشهراني. تعدد شركات غير موجود في المخطط.

## 10. الصلاحيات

| القدرة | متقدم | مسوق نشط | أدمن |
|---|---|---|---|
| إرسال النموذج | نعم | - | - |
| عملاء وعمولات حسابه | - | نعم | عبر إدارة المسوقين |
| متقدمون ومسوقون وسجل | - | - | نعم إن صحت جلسة الأدمن |
| دخول مسوق موقّف | - | الشرط `is_active = 1` يمنع الدخول | يمكن تبديل الحالة |

`marketers.user_id` مفتاح اختياري إلى `users`. إنشاء المسوق في الكود الذي فُحص يدرج في `marketers` مباشرة.

## 11. الأتمتة وWorkflows

محرك موافقات يغيّر `applicants.status` تلقائيا غير ظاهر كسلسلة حالات في `register_applicant.php`. الأدمن يحدّث السجل يدويا عبر `update`.

حساب العمولة معادلة واحدة عند الحفظ، وليست جدولة دورية.

`logActivity` يكتب في `logs` عند عمليات مثل إضافة عميل.

## 12. التكامل بين الوحدات

تسجيل المتقدم لا ينشئ مسوقا. إنشاء المسوق وتفعيله يفتح الدخول. عملاء المسوق مرتبطون به وبـ `ON DELETE CASCADE` من `marketers`. حذف مستخدم في `users` يجعل `logs.user_id` فارغا بسبب `ON DELETE SET NULL`.

## 13. المصطلحات

| المصطلح | المعنى |
|---|---|
| applicant | صف متقدم |
| marketer | مسوق وله `commission_rate` و`payment_method` |
| client | عملية أو عميل جلبه المسوق |
| commission_amount | ناتج المبلغ في النسبة مقسوما على 100 |
| payment_status | نص، والافتراضي في SQL `pending` |
| المجلد ذو الاسم الطويل | مسار واجهة الإدارة، وليس كلمة مرور تُنشر |

## 14. الأسئلة الشائعة

**هل أرقام 15 و305 و5 من القاعدة؟**  
هي `data-target` في `index.html`.

**هل مجلد الإدارة اسمه admin؟**  
README السابق يقول ذلك. الملفات الحالية تستخدم مجلدا باسم سلسلة طويلة. `.htpasswd` غير موجود.

**هل تُنشر كلمة مرور الأدمن هنا؟**  
`setup_database.php` والـ README السابق يحتويان كلمة مرور افتراضية. القيم غير منسوخة في هذا الملف.

# الجزء الثاني - التوثيق التقني

## 15. Architecture

```text
HTML/JS
   | fetch
   v
apis/*.php  -> config.php PDO
   |
   v
MySQL affiliate_marketing_trsi
   users, applicants, marketers, clients, logs
```

`config.php` يرسل `Access-Control-Allow-Origin: *`.

## 16. Tech Stack

HTML وCSS وJavaScript وPHP وPDO MySQL. Font Awesome مستخدم في أيقونات الإحصاء (`fas`). إصدار PHP غير مثبت في ملف. README السابق يذكر PHP 7.4 وMySQL 5.7 وApache مع `mod_rewrite`. ذلك شرط مكتوب سابقا، ووجود `.htaccess` يجعل `mod_rewrite` مستخدما هناك.

## 17. Project Structure

```text
TlerRewards/
├── index.html
├── marketer_login.html
├── marketer_dashboard.html
├── css/style.css
├── js/script.js
├── assets/fonts + images (Logo.ico Logo.webp Logo2.webp)
├── apis/           PHP و.htaccess
├── database_schema.sql
├── setup_database.php
└── مجلد باسم طويل/   واجهة الإدارة و.htaccess
```

## 18. Frontend

ثلاث صفحات عامة للزائر والمسوق، وصفحتان داخل مجلد الإدارة (`index.html` و`login.html`). الموقع متعدد الصفحات. القائمة في الرئيسية فيها مراسي. النموذج يرسل عبر AJAX كما في README السابق، والرسالة في `#form-message`.

تجاوب الواجهة مذكور في README السابق. ملف `css/style.css` هو التنسيق العام و`admin.css` للإدارة.

أيقونة التبويب موجودة في الرئيسية وصفحة الإدارة. فحص `marketer_login.html` و`marketer_dashboard.html` لم يظهر فيهما `rel="icon"`.

## 19. Backend

لا إطار. كل ملف يقرأ `action`. الدوال المشتركة في `config.php`: `sanitizeInput` و`validateEmail` و`validatePhone` و`logActivity` و`getClientIP`.

الهاتف في `validatePhone` نمط عشر خانات على الأقل.

## 20. Request Flow

```text
Browser
  -> fetch apis/marketer_auth.php?action=login
  -> PDO SELECT marketers
  -> password_verify
  -> session
  -> JSON
  -> marketer_dashboard.html
```

## 21. Database

من `database_schema.sql`. اسم القاعدة في `config.php` و`setup_database.php`: `affiliate_marketing_trsi`. المضيف `localhost` والمستخدم `root` وكلمة المرور فارغة. الترميز عند إنشاء القاعدة في سكربت الإعداد `utf8`.

| الجدول | مفتاح | علاقات |
|---|---|---|
| `users` | `id` | `username` فريد، `role` افتراضي `admin` |
| `applicants` | `id` | بريد وهاتف فريدان، `status` افتراضي `pending` |
| `marketers` | `id` | `user_id` اختياري إلى `users`، بريد وهاتف فريدان |
| `clients` | `id` | `marketer_id` إلى `marketers` مع حذف متسلسل |
| `logs` | `id` | `user_id` إلى `users` مع تفريغ عند الحذف |

## 22. API

الاستجابة JSON. المصادقة جلسة PHP بعد الدخول.

| الملف | الأفعال الظاهرة |
|---|---|
| `register_applicant.php` | إدراج متقدم |
| `admin_auth.php` | POST `login` و`logout`، وGET `check_session` |
| `admin_applicants.php` | `delete` و`update` |
| `admin_marketers.php` | `create` و`update` و`delete` و`toggle_status` |
| `admin_logs.php` | قراءة السجل |
| `marketer_auth.php` | `login` و`logout` و`check_session` |
| `marketer_dashboard.php` | GET `stats` و`clients`، وPOST `add_client` و`update_client` و`delete_client` |

معاملات العميل: `client_name` و`order_details` و`amount` و`commission_percentage` و`payment_status`.

## 23. Authentication & Authorization

الأدمن: `password_verify` على `users.password_hash` مع `role = admin`. المسوق: البريد أو الهاتف مع حساب نشط. `session_start` في ملفات الواجهة. خصائص كوكي `HttpOnly` و`Secure` و`SameSite` غير ظاهرة في `config.php`.

## 24. Security

| الوسيلة | الحالة |
|---|---|
| جمل مجهزة | عمليات الإدراج والتحديث التي فُحصت |
| `htmlspecialchars` عبر `sanitizeInput` | موجودة |
| تجزئة كلمات المرور | `PASSWORD_DEFAULT` |
| جلسات | أدمن ومسوق |
| سجل عمليات | جدول `logs` |
| `.htaccess` | منع فهرسة وبعض الوكلاء، ومنع سرد المجلد، ومنع تنزيل امتدادات مثل sql وlog |
| فحص بوت في PHP | `bot_protection.php` على وكيل المستخدم والمرجع |
| CORS | `*` في `config.php` |
| CSRF | رمز نموذج غير موجود في الملفات التي فُحصت |
| كلمة مرور أدمن افتراضية | يكتبها `setup_database.php` عند التشغيل |
| عرض أخطاء PHP | `display_errors` = 0 في `config.php` |
| فشل الاتصال | `die` برسالة PDO |

README السابق يذكر حماية Apache بكلمة مرور مجلد. ملف `.htpasswd` غير موجود الآن.

## 25. Configuration

| المكان | المحتوى |
|---|---|
| `apis/config.php` | مضيف، اسم قاعدة، مستخدم، كلمة مرور فارغة |
| `setup_database.php` | نفس الاتصال ثم تنفيذ SQL |
| `.htaccess` | قواعد الوصول |

ملف `.env` غير موجود. لا تُنسخ كلمات المرور من README السابق أو من سكربت الإعداد.

## 26. Integrations

خدمات بريد أو دفع خارجية غير موجودة في الملفات الحالية. إنستغرام حقل نص في النموذج وليس ربط API.

## 27. Scheduled Jobs

غير موجود في الملفات الحالية.

## 28. File Storage

الخطوط والشعارات في `assets/`. رفع ملفات متقدمين غير موجود في `register_applicant.php`.

## 29. Logging & Monitoring

`logActivity` يكتب `action_type` و`description` و`ip_address`. أخطاء PDO تذهب إلى `error_log` في مسارات اللوحة، ورسالة المستخدم عامة: `حدث خطأ في النظام`.

## 30. Installation

1. PHP مع PDO MySQL وApache إن لزم تطبيق `.htaccess`.
2. من مجلد المشروع: `php setup_database.php` كما في README السابق.
3. السكربت ينشئ القاعدة والجداول ثم مستخدم أدمن. غيّر كلمة المرور بعد التشغيل. القيمة الافتراضية مطبوعة من السكربت وغير مكررة هنا.
4. ضع الملفات في جذر الويب وافتح `index.html`.
5. مسار الإدارة هو المجلد ذو الاسم الطويل، وليس بالضرورة `/admin`.

## 31. Development Guide

- صفحة عامة: HTML في الجذر ونداء إلى ملف في `apis/`.
- فعل جديد: فرع `action` في الملف المناسب مع جملة مجهزة وفحص جلسة.
- عمود: تعديل `database_schema.sql`. التشغيل السابق لا يعيد بناء الجداول تلقائيا إن كانت موجودة؛ `CREATE TABLE` في المخطط بلا `IF NOT EXISTS`.
- صلاحية: الأدمن عبر جلسة `admin_auth`، والمسوق عبر جلسة المسوق.

## 32. Deployment

ملف نشر غير موجود. README السابق يفترض Apache و`mod_rewrite`. قبل النشر: استبدال حساب `root`، وتقييد CORS، وتغيير كلمة مرور الإعداد، وإيقاف طباعة أسرار الإعداد من السكربت.

## 33. Backup & Recovery

غير موجود في الملفات الحالية. النسخ يكون لقاعدة `affiliate_marketing_trsi`.

## 34. Troubleshooting

| العرض | الاتجاه |
|---|---|
| Connection failed | MySQL أو ثوابت `config.php` |
| دخول المسوق مرفوض | `is_active` ليس 1 أو الكلمة لا تطابق التجزئة |
| لوحة الإدارة 403 من Apache | قواعد `.htaccess` على وكيل المستخدم أو المرجع |
| الإحصاءات لا تتغير مع القاعدة | الأرقام ثابتة في HTML |
| README يطلب `/admin` | المسار الفعلي مجلد باسم مختلف |

## 35. Dependencies

`composer.json` غير موجود. الاعتماد: PHP وPDO وMySQL وApache لقواعد `.htaccess` وأيقونات Font Awesome المحمّلة في الصفحة.

## 36. Known Limitations

- إحصاءات الصفحة ليست من القاعدة.
- CORS مفتوح `*`.
- كلمة مرور إعداد افتراضية داخل سكربت.
- توثيق قديم لمجلد `admin` و`.htpasswd` لا يطابق الشجرة الحالية.
- صفحات المسوق بلا أيقونة تبويب ظاهرة في الفحص.
- `CREATE TABLE` بلا حماية إعادة التشغيل في المخطط.

## 37. Current System State

| الحالة | التفصيل |
|---|---|
| موجود | الصفحات الثلاث، واجهات PHP، المخطط، مجلد الإدارة، السجل، العمولة المحسوبة |
| يحتاج MySQL | أي حفظ |
| غير مكتمل | ربط المتقدم بمسوق تلقائيا، بريد، دفع عمولة فعلي |
| غير موثق | نطاق الإنتاج داخل هذا المجلد |

## 38. Architecture Decisions

إخفاء مسار الإدارة باسم مجلد طويل ظاهر من وجود المجلد ومن نداءات `../apis`. هذا مسار اكتشاف أصعب، وليس بديلا عن كلمة المرور. كلمة المرور تبقى في `password_hash`.

حساب العمولة على الخادم في `marketer_dashboard.php` حتى لا تعتمد اللوحة على رقم يرسله المتصفح وحده؛ المتصفح يرسل النسبة والمبلغ والخادم يضربهما.

## 39. سجل التغييرات

سجل إصدارات مستقل غير موجود. README السابق يذكر الحقوق لسنة 2025. فروقات التوثيق عن الشجرة الحالية مذكورة في أول هذا الملف.

# الجزء الأخير - ملخص شامل

## System Overview

```text
زائر -> index.html -> register_applicant.php -> applicants
مسوق -> marketer_login -> marketer_auth -> marketer_dashboard -> clients
أدمن -> مجلد باسم طويل -> admin_auth / applicants / marketers / logs
setup_database.php + database_schema.sql -> MySQL
```

## Quick Reference

| الجزء | التقنية | الموقع | الوظيفة |
|---|---|---|---|
| الموقع | HTML/CSS/JS | `index.html` و`css` و`js` | عرض وتسجيل |
| المسوق | HTML | `marketer_*.html` | دخول ولوحة |
| الإدارة | HTML/JS | مجلد الاسم الطويل | لوحات الأدمن |
| الواجهة | PHP | `apis/` | كل الأفعال |
| الإعداد | PHP | `apis/config.php` | PDO |
| المخطط | SQL | `database_schema.sql` | خمسة جداول |
| التثبيت | PHP | `setup_database.php` | إنشاء وحساب أدمن |
| الحماية | Apache/PHP | `.htaccess` و`bot_protection.php` | تقييد وكلاء وفهرسة |
| الشعار | صور | `assets/images/` | Logo.ico وغيره |

## Quick Start

شغّل MySQL، نفّذ `php setup_database.php`، افتح `index.html` عبر خادم PHP. غيّر كلمة مرور الأدمن التي ينشئها السكربت قبل أي استخدام حقيقي. مسار لوحة الإدارة هو المجلد ذو الاسم الطويل داخل المشروع.

## For Non-Technical Users

- الصفحة تشرح برنامج التسويق وتحتوي نموذج انضمام.
- الأرقام الكبيرة في الصفحة (15 و305 و5) مكتوبة في التصميم.
- المسوق يدخل من صفحة خاصة ويرى عملاءه وعمولته.
- الإدارة في مجلد منفصل باسم غير منشور هنا ككلمة سر.
- حالة الطلب الجديدة تبقى معلّقة إلى أن تُحدَّث من الإدارة.

## For Developers

- التقنيات: PHP وPDO وMySQL وHTML وJavaScript وApache `.htaccess`.
- المعمارية: صفحات ثابتة وملفات `apis` حسب الدور.
- القاعدة: `affiliate_marketing_trsi` والجداول الخمسة.
- الأفعال موثقة في القسم 22.
- أهم الملفات: `config.php` و`database_schema.sql` و`marketer_dashboard.php` و`admin_marketers.php`.
- التطوير: جمل مجهزة، وتجنب نسخ كلمات مرور الإعداد. لا تعامل اسم مجلد الإدارة كسر قابل للنشر في الوثائق العامة.
