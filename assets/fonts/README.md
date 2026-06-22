# גופנים — Ploni · PloniYad · Gloria (Fontef, מסחרי)

הת'ים מגיש את הגופנים **מקומית** (לביצועים ולרישוי תקין). יש להעלות לכאן את
קבצי ה-`woff2` בדיוק בשמות הבאים — `theme.json` כבר מצביע עליהם:

| משפחה | משקל | שם קובץ נדרש |
|--------|------|---------------|
| Ploni | 300 (Light) | `ploni-light.woff2` |
| Ploni | 400 (Regular) | `ploni-regular.woff2` |
| Ploni | 500 (Medium) | `ploni-medium.woff2` |
| Ploni | 600 (DemiBold) | `ploni-demibold.woff2` |
| Ploni | 700 (Bold) | `ploni-bold.woff2` |
| PloniYad | 400 | `ploniyad-regular.woff2` |
| PloniYad | 500 | `ploniyad-medium.woff2` |
| PloniYad | 600 | `ploniyad-demibold.woff2` |
| PloniYad | 700 | `ploniyad-bold.woff2` |
| PloniYad | 900 (Black) | `ploniyad-black.woff2` |
| Gloria | 400 | `gloria-regular.woff2` |
| Gloria | 600 | `gloria-demibold.woff2` |
| Gloria | 700 | `gloria-bold.woff2` |

## המרה ל-woff2
אם הקבצים אצלכם ב-`woff`/`ttf`/`otf`, להמיר ל-`woff2` (קל ומהיר יותר):

```bash
# התקנה חד-פעמית: pip install fonttools brotli
fonttools ttLib.woff2 compress ploni-regular.ttf   # → ploni-regular.woff2
```

> ⚠️ רישוי: Ploni / PloniYad / Gloria הם גופנים מסחריים של Fontef. יש לוודא
> שברשותכם רישיון web המתיר self-hosting לפני הפצה לייצור.
