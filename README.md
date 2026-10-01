# Kindi — ת'ים WordPress לחנות "קינדר טויס"

ת'ים בלוקים (FSE) מותאם אישית ל-WooCommerce, RTL/עברית, המשחזר את עיצוב ה-Lovable
המקורי. מפותח לפי **תקן Multi Digital** (ביצועים, אבטחה, נגישות ת"י 5568 / WCAG 2.2 AA).
ראו [`CLAUDE.md`](./CLAUDE.md) לתקן המלא.

## דרישות
- WordPress 6.5+
- PHP 8.3+
- WooCommerce (אחרון)

## התקנה
1. העתיקו/שכפלו את הת'ים אל `wp-content/themes/kindi`.
2. **העלו את הגופנים** אל `assets/fonts/` לפי [`assets/fonts/README.md`](./assets/fonts/README.md)
   (Ploni · PloniYad · Gloria, פורמט `woff2`). זהו תנאי לדיוק טיפוגרפי מלא.
3. הפעילו את הת'ים: *Appearance → Themes → Kindi*.
4. הגדירו עמוד בית סטטי: *Settings → Reading → "עמוד בית" = front page*.
5. ודאו ש-WooCommerce מותקן ופעיל (עמודי חנות/מוצר/עגלה/צ'קאאוט).

## מבנה
| נתיב | תיאור |
|------|-------|
| `theme.json` | מערכת העיצוב — צבעים, גופנים, ריווחים, צללים (מקור אמת לטוקנים) |
| `style.css` | מטא-דאטה של הת'ים |
| `functions.php` + `inc/` | bootstrap, setup, טעינה מותנית, ביצועים, אבטחה, WooCommerce |
| `templates/` | תבניות בלוקים (front-page, index) |
| `parts/` | header, footer |
| `patterns/` | סקשנים (hero, usp-strip, …) |
| `assets/` | fonts, css, js, img |

## סטטוס פיתוח
- [x] תשתית: theme.json, style.css, functions + inc, CSS בסיס/אנימציות/רכיבים
- [x] header (skip-link, רצועת מבצעים, לוגו, חיפוש, סל), footer
- [x] עמוד בית: hero, רצועת USP, מוצרים מומלצים (WooCommerce)
- [ ] גופנים (ממתין להעלאת קבצי `woff2`)
- [ ] סקשנים נוספים: גריד קטגוריות, באנרי מבצע, מדף לפי גיל, מותגים, KindyZone, המלצות
- [ ] התאמת תבניות WooCommerce (ארכיון, מוצר בודד, עגלה, צ'קאאוט) לעיצוב
- [ ] כפתורים צפים, mega-menu מלא, עמוד הצהרת נגישות

> `_lovable_src/` (מקור העיצוב) מוחרג מהגיט ואינו נשלח עם הת'ים.
