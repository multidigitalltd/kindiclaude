# גופנים — Ploni · Ploni Yad (Fontef, מסחרי)

הגופנים מועלים דרך **ספריית הגופנים של WordPress** (Site Editor → Styles →
Typography → Manage fonts → Upload). WordPress מאחסן אותם תחת
`wp-content/uploads/fonts/` ומגיש `@font-face` אוטומטית — אין צורך להניח קבצים
בתיקייה זו.

`theme.json` כבר ממפה את משפחות הגופן לפי **שם** (לא לפי slug). השמות שהותקנו
בפועל דרך ה-Font Library הם:
- גוף הטקסט → `Ploni ML v2 AAA`
- כותרות / display → `Ploni Yad v2 AAA`

(עם נפילה אחורה ל-`Ploni` / `Ploni Yad` אם השמות ישתנו בעתיד.)

## מה להעלות (woff2)

### משפחה 1 — `Ploni` (גוף הטקסט)
| משקל (Weight) | סגנון | קובץ |
|----------------|-------|------|
| 300 — Light | Normal | `ploni-light.woff2` |
| 400 — Regular | Normal | `ploni-regular.woff2` |
| 500 — Medium | Normal | `ploni-medium.woff2` |
| 600 — DemiBold | Normal | `ploni-demibold.woff2` |
| 700 — Bold | Normal | `ploni-bold.woff2` |

### משפחה 2 — `Ploni Yad` (כותרות / display)
| משקל (Weight) | סגנון | קובץ |
|----------------|-------|------|
| 400 — Regular | Normal | `ploniyad-regular.woff2` |
| 500 — Medium | Normal | `ploniyad-medium.woff2` |
| 600 — DemiBold | Normal | `ploniyad-demibold.woff2` |
| 700 — Bold | Normal | `ploniyad-bold.woff2` |
| 900 — Black | Normal | `ploniyad-black.woff2` |

**חיוני למינימום** (אם אין את כל המשקלים): Ploni 400/500/600/700 · Ploni Yad 700/900.

## אחרי ההעלאה — לוודא
1. בכל קובץ ודאו ש-WordPress זיהה את ה-**Weight** הנכון ואת ה-**Style = Normal**.
2. ודאו ששמות המשפחות בספרייה הם בדיוק `Ploni` ו-`Ploni Yad`. אם השם שמוצג שונה
   (למשל `Ploni Hand`), עדכנו אותי ואתאים את `theme.json` ב-30 שניות.

> ⚠️ רישוי: Ploni / Ploni Yad הם גופנים מסחריים של Fontef. יש לוודא רישיון web
> המתיר self-hosting לפני העלאה לייצור.
