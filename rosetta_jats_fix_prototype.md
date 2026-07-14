# JATS-fix prototype — results

**Date:** 2026-07-14
**Goal:** after correcting image extensions, fix the systematic JATS DTD defects so the SIPs
validate locally (and would be accepted by Rosetta), verified with the local JATS 1.2 DTD.
**Tool:** `~/rosetta-validators/fix-jats.php` (DOM-based, preserves DOCTYPE & all other content).

## What it fixes

Two systematic defects appear in (nearly) all 84 articles, plus two mechanical ones:

| Defect | Fix applied | Cause |
|---|---|---|
| **D1** `<fpage>/<lpage>/<volume>/<issue>` wrongly nested inside `<pub-date>` | move them out as siblings of `pub-date` | conversion bug |
| **D2** `<permissions>` ordered before `<pub-date>` in `article-meta` | reorder `article-meta` children to the DTD sequence | conversion bug |
| **D3** `<td>/<th>` `id`/`rid` values starting with a digit (invalid XML `ID`) | prefix with `id-` (and matching `rid`) | hash IDs beginning 0-9 |

D1/D2 are pure structural; D3 is a safe rename that keeps `id`↔`rid` links intact.

## Result — 82 of 84 articles become fully JATS-valid

Verified with `xmllint --dtdvalid <JATS 1.2 DTD> --nonet` (after the plugin's `normalizeJatsGalley`
and this transform):

| Journal | Now valid |
|---|---|
| siliconpv | 37/37 |
| cordi | 5/5 |
| glass-europe | 3/3 |
| zukunftsnetz | 3/3 |
| pv-symposium | 2/2 |
| st-symposium | 1/1 |
| agripv | 15/16 |
| ocp | 16/17 |
| **Total** | **82/84** |

### The 2 that still fail — genuine content defects (need manual review, not a mechanical fix)
- **agripv-2852** — an **empty `<list>`** (`<list>` requires `list-item+`, has none).
- **ocp-1188** — **`<ext-link>` nested inside `<ext-link>`** (not allowed by the DTD).

These are malformed content in the source JATS, not the systematic pattern; they need a human/
editorial decision (remove the empty list; unnest the ext-link).

## Where this fits

- Image extensions were already corrected in the DB + on disk (536 files → correct `.png`/`.jpg`).
- This transform is **prototype/test-only** — applied to a temp copy of each galley JATS and
  validated locally. It has **not** been wired into the plugin or applied to the stored galleys.
- Recommended integration: apply the transform at the **SIP-build stage** (same place the plugin
  runs `normalizeJatsGalley`), so the deposited SIP carries the corrected JATS while the stored
  OJS galley is left untouched — or fix the stored galleys directly if TIB prefers.

## Pre-flight gate (unchanged workflow)

After extension-fix + JATS-fix, re-run `check-sip.sh <sip>`; deposit only the ✔ PASS ones.
Current status: **82 would PASS**, 2 need manual JATS content fixes before deposit.
