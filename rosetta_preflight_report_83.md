# Rosetta Pre-Flight Validation Report — 83 recurring-error articles

**Generated:** 2026-07-14  
**Method:** each article's SIP was built on the server (metadata + galley files, with the plugin's `normalizeJatsGalley` applied), archived, copied locally, and validated with the **same open-source tools Rosetta uses** — DROID 6.9.13 (format/extension identification), JHOVE 1.34.0 (per-format well-formedness), and `xmllint` against the local JATS 1.2 DTD. Nothing was deposited.

> This reproduces Rosetta's accept/reject verdict **locally**, so files can be fixed before ever contacting Rosetta (per Franziska's recommendation).

## Summary

- **Articles validated:** 84
- **✔ PASS (would be accepted):** 0
- **⚠ WARN (would be rejected):** 84

| Failure category | Articles |
|---|---:|
| Wrong file extension **only** (image named `.xml`) | 0 |
| JATS DTD-invalid **only** | 26 |
| **Both** wrong extension **and** JATS-invalid | 58 |
| Clean | 0 |

## Root causes (across all flagged articles)

### A. Wrong file extension — images stored as `.xml`
58 articles contain image files named `.xml`. DROID identifies their true format; Rosetta's JHOVE then fails to parse them as XML → **Files Rejected**. True formats found:

| Real format (mislabeled as .xml) | File count |
|---|---:|
| Portable Network Graphics | 244 |
| JPEG File Interchange Format | 70 |
| Raw JPEG Stream | 2 |

### B. JATS full-text is DTD-invalid
84 articles have a JATS galley that fails JATS 1.2 DTD validation (even after the plugin's `normalizeJatsGalley` fix). Distinct causes:

| JATS defect | Articles affected |
|---|---:|
| `<permissions>` ordered before `<pub-date>` in `article-meta` | 84 |
| `<fpage>`/`<lpage>` wrongly nested inside `<pub-date>` | 71 |
| invalid `id` attribute value on table `<td>` cells | 10 |
| `<volume>` wrongly nested inside `<pub-date>` | 5 |

## Per-journal breakdown

| Journal | Articles | Wrong-extension | JATS-invalid |
|---|---:|---:|---:|
| agripv | 16 | 16 | 16 |
| cordi | 5 | 0 | 5 |
| glass-europe | 3 | 2 | 3 |
| ocp | 17 | 16 | 17 |
| pv-symposium | 2 | 1 | 2 |
| siliconpv | 37 | 20 | 37 |
| st-symposium | 1 | 1 | 1 |
| zukunftsnetz | 3 | 2 | 3 |

## Per-article detail

| # | Article (SIP) | Verdict | Ext-mismatch files | JATS errors | Issues |
|---|---|---|---:|---:|---|
| 1 | agripv-1357-v1 | ⚠ WARN | 8 | 2 | 8 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 2 | agripv-1368-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 3 | agripv-1370-v1 | ⚠ WARN | 8 | 2 | 8 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 4 | agripv-1372-v1 | ⚠ WARN | 3 | 2 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 5 | agripv-1374-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 6 | agripv-1378-v1 | ⚠ WARN | 12 | 2 | 12 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 7 | agripv-1404-v1 | ⚠ WARN | 5 | 2 | 5 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 8 | agripv-1406-v1 | ⚠ WARN | 6 | 2 | 6 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 9 | agripv-2812-v1 | ⚠ WARN | 3 | 22 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 10 | agripv-2826-v1 | ⚠ WARN | 3 | 48 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 11 | agripv-2827-v1 | ⚠ WARN | 4 | 2 | 4 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 12 | agripv-2835-v1 | ⚠ WARN | 7 | 2 | 7 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 13 | agripv-2837-v1 | ⚠ WARN | 1 | 45 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 14 | agripv-2849-v1 | ⚠ WARN | 5 | 35 | 5 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 15 | agripv-2852-v1 | ⚠ WARN | 7 | 21 | 7 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 16 | agripv-2853-v1 | ⚠ WARN | 6 | 96 | 6 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id,volume-in-pub-date |
| 17 | cordi-334-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 18 | cordi-355-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 19 | cordi-356-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 20 | cordi-408-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,volume-in-pub-date |
| 21 | cordi-421-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 22 | glass-europe-1139-v1 | ⚠ WARN | 0 | 238 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 23 | glass-europe-2629-v1 | ⚠ WARN | 11 | 2 | 11 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 24 | glass-europe-2657-v1 | ⚠ WARN | 12 | 2 | 12 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 25 | ocp-1045-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 26 | ocp-1098-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 27 | ocp-1118-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 28 | ocp-1176-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 29 | ocp-1179-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 30 | ocp-1181-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 31 | ocp-1188-v1 | ⚠ WARN | 3 | 8 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 32 | ocp-1194-v1 | ⚠ WARN | 6 | 2 | 6 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 33 | ocp-1195-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 34 | ocp-1197-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 35 | ocp-1412-v1 | ⚠ WARN | 2 | 24 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 36 | ocp-1413-v1 | ⚠ WARN | 8 | 2 | 8 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 37 | ocp-1415-v1 | ⚠ WARN | 7 | 2 | 7 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 38 | ocp-1416-v1 | ⚠ WARN | 3 | 2 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 39 | ocp-1418-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 40 | ocp-190-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,volume-in-pub-date |
| 41 | ocp-2764-v1 | ⚠ WARN | 10 | 2 | 10 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 42 | pv-symposium-2641-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 43 | pv-symposium-2653-v1 | ⚠ WARN | 8 | 2 | 8 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 44 | siliconpv-1265-v1 | ⚠ WARN | 6 | 2 | 6 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 45 | siliconpv-1271-v1 | ⚠ WARN | 15 | 2 | 15 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 46 | siliconpv-1271-v2 | ⚠ WARN | 15 | 2 | 15 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 47 | siliconpv-1293-v1 | ⚠ WARN | 9 | 2 | 9 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 48 | siliconpv-1323-v1 | ⚠ WARN | 12 | 2 | 12 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 49 | siliconpv-1326-v1 | ⚠ WARN | 7 | 2 | 7 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 50 | siliconpv-2669-v1 | ⚠ WARN | 3 | 2 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 51 | siliconpv-2676-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 52 | siliconpv-2679-v1 | ⚠ WARN | 10 | 14 | 10 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id |
| 53 | siliconpv-2683-v1 | ⚠ WARN | 3 | 2 | 3 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 54 | siliconpv-2684-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 55 | siliconpv-2685-v1 | ⚠ WARN | 1 | 2 | 1 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 56 | siliconpv-2689-v1 | ⚠ WARN | 4 | 2 | 4 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 57 | siliconpv-2691-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 58 | siliconpv-2692-v1 | ⚠ WARN | 7 | 2 | 7 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 59 | siliconpv-2694-v1 | ⚠ WARN | 5 | 2 | 5 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,volume-in-pub-date |
| 60 | siliconpv-2696-v1 | ⚠ WARN | 11 | 2 | 11 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 61 | siliconpv-2697-v1 | ⚠ WARN | 4 | 2 | 4 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 62 | siliconpv-2699-v1 | ⚠ WARN | 5 | 2 | 5 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 63 | siliconpv-2700-v1 | ⚠ WARN | 4 | 2 | 4 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 64 | siliconpv-2714-v1 | ⚠ WARN | 2 | 2 | 2 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 65 | siliconpv-2714-v2 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 66 | siliconpv-838-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 67 | siliconpv-842-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 68 | siliconpv-843-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 69 | siliconpv-845-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 70 | siliconpv-853-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 71 | siliconpv-854-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 72 | siliconpv-877-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 73 | siliconpv-878-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 74 | siliconpv-881-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 75 | siliconpv-882-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 76 | siliconpv-883-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 77 | siliconpv-889-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 78 | siliconpv-938-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 79 | siliconpv-940-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 80 | siliconpv-952-v1 | ⚠ WARN | 0 | 1 | JATS: permissions-before-pub-date |
| 81 | st-symposium-2759-v1 | ⚠ WARN | 4 | 17 | 4 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date,invalid-td-id,volume-in-pub-date |
| 82 | zukunftsnetz-1041-v1 | ⚠ WARN | 0 | 2 | JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 83 | zukunftsnetz-2577-v1 | ⚠ WARN | 10 | 2 | 10 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |
| 84 | zukunftsnetz-2597-v1 | ⚠ WARN | 14 | 2 | 14 image(s) as .xml; JATS: fpage/lpage-in-pub-date,permissions-before-pub-date |

## What this means for the re-deposit

- **Every flagged article must be fixed before deposit** — re-depositing as-is reproduces the same Rosetta rejection.
- **Wrong extensions** are mechanically fixable (rename `.xml` → real image extension + update the OJS file record).
- **The JATS defects are systematic** — the same `<fpage>/<lpage>`-inside-`<pub-date>` and `<permissions>` ordering appear across nearly all articles, indicating a **conversion/template bug**, not per-article mistakes. A single corrective transform at the JATS-generation (or SIP-build) stage would address the whole batch.
- **Pre-flight gate:** run `check-sip.sh <sip-folder>` before each deposit; deposit only on `✔ PASS`.
