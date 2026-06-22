# CLAUDE.md — תקן פיתוח לפרויקט "קינדי" (Kindi)

מסמך זה מנחה כל שורת קוד בפרויקט. הוא מאחד את **תקן Multi Digital** עם פרטי
הפרויקט הספציפי. אין לחרוג מהכללים אלא אם אושר אחרת מראש.

---

## 1. הפרויקט במשפט

חנות צעצועים ("קינדר טויס" / Kindi) — אתר **WordPress + WooCommerce** עם
**ת'ים בלוקים (FSE)** מותאם אישית, RTL/עברית, המשחזר במדויק עיצוב שמקורו ב-Lovable
(React + Tailwind). דיוק פיקסל-פרפקט הוא דרישה מחייבת — כולל גופנים, משקלים,
צבעים, ריווחים ואנימציות.

- **Theme slug / text-domain:** `kindi`
- **Function / hook prefix:** `kindi_`
- **מקור עיצוב (reference בלבד, לא נשלח עם הת'ים):** `_lovable_src/`

---

## 2. מערכת העיצוב — מקור אמת

מערכת העיצוב מוגדרת ב-`theme.json` בלבד. אין לכתוב ערכי צבע/גודל קשיחים (hardcoded)
בקבצי CSS או תבניות — להשתמש תמיד ב-CSS Custom Properties שמייצר WordPress
(`--wp--preset--color--*`, `--wp--custom--*`).

### צבעים (oklch, מתוך `src/styles.css`)
| טוקן | ערך |
|------|-----|
| brand-red | `oklch(0.58 0.21 27)` |
| brand-red-dark | `oklch(0.49 0.21 27)` |
| brand-red-soft | `oklch(0.93 0.05 27)` |
| brand-blue | `oklch(0.55 0.18 245)` |
| brand-blue-soft | `oklch(0.92 0.05 240)` |
| brand-navy | `oklch(0.32 0.16 258)` |
| brand-navy-dark | `oklch(0.22 0.13 258)` |
| brand-yellow / accent | `oklch(0.86 0.17 91)` |
| surface | `oklch(0.985 0.006 240)` |
| foreground | `oklch(0.22 0.04 265)` |
| muted | `oklch(0.96 0.005 95)` |
| border | `oklch(0.92 0.008 95)` |

### טיפוגרפיה (גופנים מסחריים של Fontef — מוגשים מקומית מ-`assets/fonts/`)
- **Ploni** — גוף הטקסט (`--wp--preset--font-family--sans`). משקלים: 300/400/500/600/700.
- **PloniYad** — כותרות/display (`--font-display`). משקלים: 400/500/600/700/900.
- **Gloria** — דקורטיבי. משקלים: 400/600/700.
- `font-display: swap` תמיד. preload רק למשקלים שבחלק העליון של העמוד (LCP).

### רדיוס, צללים, אנימציות
- רדיוס בסיס: `0.75rem`. צללים: `--shadow-card`, `--shadow-pop`.
- אנימציות (ב-`assets/css/animations.css`, נטענות מותנה): bob, wiggle, float,
  marquee, confetti, mascot-sway, ground-breath, sparkle-pop, search-glow.
- **חובה לכבד `prefers-reduced-motion`** — לעטוף את כל האנימציות.

---

## 3. ביצועים (יעדים מחייבים)

- Mobile PageSpeed > 90, Desktop > 95. LCP < 2.5s, CLS < 0.1, INP תקין.
- **טעינה מותנית בלבד:** CSS/JS לפי תבנית/בלוק/שורטקוד. אין assets גלובליים מיותרים.
- Vanilla JS בלבד — **ללא jQuery**, ללא ספריות צד-ג' כשאפשר ליבת WP/JS.
- `defer`/`async` ל-JS. למנוע Render-Blocking. Lazy-load לתמונות ול-iframes.
- תמונות WebP/AVIF + `srcset` מתאים לגודל מסך.
- מטמון: Transient לכל מידע שאינו זמן-אמת; Object Cache כשזמין. אין קריאות API בכל טעינה.
- שאילתות: מינימום, `prepare()` תמיד, `no_found_rows`, `update_post_meta_cache=false`
  וכו' כשאפשר. למנוע N+1. `get_posts` כשאין צורך ב-`WP_Query` מלא. אין `SELECT *`.
- תאימות מלאה: LiteSpeed Cache, Redis, Memcached, Cloudflare, WP Rocket, Object Cache Pro.

---

## 4. אבטחה

- **Sanitization** לכל קלט (`sanitize_text_field`, `sanitize_email`, `absint`, `wp_kses_post`…).
- **Escaping** לכל פלט (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`).
- Nonce + `current_user_can` בכל פעולה שמשנה נתונים, בכל AJAX, ובכל REST endpoint
  (`permission_callback` חובה).
- `$wpdb->prepare()` תמיד. אין SQL concatenation. למנוע XSS/CSRF/IDOR/Privilege-Escalation.
- אסור: `eval`, `exec`, `shell_exec`, `system`, `passthru`, `base64_*` להסתרת קוד.
- העלאות קבצים: בדיקת סוג + MIME + הרשאות + גודל. אין PHP. `wp_safe_redirect` בלבד.
- אין לחשוף שגיאות/stack-trace/מפתחות. סודות ב-Environment Variables.

---

## 5. נגישות (ת"י 5568 + WCAG 2.2 AA)

- HTML5 סמנטי, H1 יחיד, היררכיית כותרות תקינה. RTL+LTR מלא (עברית/אנגלית).
- ניגודיות: טקסט רגיל 4.5:1, טקסט גדול 3:1. ניווט מקלדת מלא + Focus States נראים +
  Skip-to-content. אין מלכודות פוקוס.
- `alt` משמעותי לכל תמונה; דקורטיבי → `alt=""`/`aria-hidden`. `<label>` לכל שדה
  (placeholder אינו תחליף). ARIA רק כשסמנטיקה טבעית לא מספיקה.
- מודאלים: trap focus, ESC, החזרת פוקוס. עדכוני AJAX → `aria-live`.
- כיבוד `prefers-reduced-motion`. טקסט סקלאבילי עד 200% בלי שבירת פריסה.
- כל עמוד ≥ 95 ב-Lighthouse Accessibility. עמוד הצהרת נגישות תואם רגולציה.

---

## 6. איכות קוד

- WordPress Coding Standards. PHP 8.3+. אפס Deprecated/Warning/Notice/Fatal.
- DRY, פונקציות קצרות וממוקדות, שמות ברורים, הפרדת לוגיקה/תצוגה, תיעוד לכל רכיב משמעותי.
- אין קוד מת, אין `var_dump`/`print_r`/`console.log` בייצור.

---

## 7. SEO

HTML סמנטי, Open Graph, Schema (Product/BreadcrumbList כשרלוונטי), URL ידידותי,
תאימות לתוספי SEO, מניעת תוכן כפול, Lazy-load.

---

## 8. מבנה הת'ים

```
kindi/
├── style.css            כותרת ת'ים (מטא-דאטה בלבד)
├── theme.json           מערכת העיצוב (מקור אמת לטוקנים)
├── functions.php        bootstrap — טוען inc/*
├── inc/
│   ├── setup.php        theme supports, נכסי עריכה
│   ├── enqueue.php      טעינה מותנית של CSS/JS
│   ├── performance.php  ניקוי head, מטמון, הסרת bloat
│   ├── security.php     headers, הקשחה
│   └── woocommerce.php  התאמות חנות
├── templates/           תבניות בלוקים (HTML)
├── parts/               header, footer, אזורי תבנית
├── patterns/            block patterns לסקשנים של העמוד
└── assets/
    ├── fonts/           Ploni / PloniYad / Gloria (woff2)
    ├── css/             CSS מותנה לסקשנים/אנימציות
    ├── js/              Vanilla JS מותנה
    └── img/             נכסים סטטיים (מסקוט, לוגו)
```

---

## 9. נהלי עבודה

- ענף פיתוח: `claude/amazing-gauss-5u56se`. קומיטים ברורים, push לענף בלבד.
- כל שינוי עובר את ההיגיון של פרק 3–7 לפני מסירה. לוודא פיקסל-פרפקט מול `_lovable_src`.
