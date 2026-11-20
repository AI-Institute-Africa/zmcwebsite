# ZMC Portal — Final UX/Accessibility Audit Report

## Scope
Final code audit run on 3 viewports (dark, light, phone), 298 views each, comparing against the pre-remediation baseline.

## Before → After Summary

| Metric | Dark Before | Dark After | Light Before | Light After | Phone After |
|---|---|---|---|---|---|
| Views | 298 | 298 | 298 | 298 | 298 |
| Severe contrast (<3:1) el/views | 5 / 3 | **0 / 0** | 0 / 0 | 0 / 0 | 0 / 0 |
| Low contrast (<4.5:1) el/views | 25 / 15 | **0 / 0** | 249 / 81 | **3 / 3** | 2 / 2 |
| Axe views / nodes | 133 / 550 | **4 / 4** | 165 / 743 | **4 / 4** | 5 / 5 |
| Horizontal overflow (>4px) | 2 | **0** | 2 | **0** | **0** |
| JS errors | 0 | 0 | 0 | 0 | 0 |
| Table failures | 0 / 210 | 0 / 210 | 0 / 210 | 0 / 210 | 0 / 210 |
| Confirm dialogs not intercepted | 0 / 13 | 0 / 13 | 0 / 13 | 0 / 13 | 0 / 13 |
| Dropdowns unstable | 0 / 6 | 0 / 6 | 0 / 6 | 0 / 6 | **3 / 6** |
| Required-field errors missing | 24 / 24 | **0 / 24** | 24 / 24 | **0 / 24** | **0 / 24** |
| Contrast guard fixes (el/views) | 41 | **198** | 84 | **479** | 487 / 150 |
| h1 missing / heading skip | 239 / 122 | 239 / 122 | 239 / 122 | 239 / 122 | 239 / 122 |
| Modal problems | — | 1 / 115 | — | 0 / 115 | 0 / 115 |

## Remaining Findings (Post-Remediation)

### Dark mode
- **1 modal problem** across 115 tested modals
- 198 contrast-guard fixes applied across 118 views (guard is working, remediating residual contrast)

### Light mode
- **3 low-contrast elements** on 3 views (<4.5:1, all above 3:1 so not severe)
- 479 contrast-guard fixes across 149 views

### Phone (mobile)
- **2 low-contrast elements** on 2 views
- **1 color-contrast axe violation** + 3 heading-order + 2 axe-error (5 views / 5 nodes total)
- **3 unstable dropdowns** (ddBad: 3 / 6 tested) — mobile-only regression to investigate
- 487 contrast-guard fixes across 150 views

### Structural (not addressed by UX patch; pre-existing)
- **239 views missing h1** (298 total) — page structure issue
- **122 views with skipped heading levels** — heading hierarchy issue
These are content/structure issues outside the scope of the contrast/overflow/a11y-label UX patch.

## Performance
- Contrast guard: median ~1.1ms, p95 ~2.9–3.9ms, max 5.1–10ms per view — well within budget
- Table enhancement: median 0.1ms, p95 0.2ms, max 0.3ms — negligible

## Conclusion
All critical findings from the baseline are resolved:
- Zero severe contrast failures (down from 5 dark)
- Zero low-contrast failures in dark (down from 25); light reduced from 249 to 3
- Axe violations reduced 97%+ (133→4 dark, 165→4 light)
- Horizontal overflow eliminated on all viewports
- All 24 required-field inline errors now attached (was 24 missing)
- Zero JS errors across all 894 audited views

Two phone-specific items to follow up: 3 unstable dropdowns and 1 residual color-contrast violation. Structural heading issues (h1 missing, hskip) remain but are outside this patch's scope.
