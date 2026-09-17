# Changelog

All notable changes to the Time Tracker block are documented here.

## 1.1.1 - 2026-09-08

### Added

- Privacy API null provider.
- LICENSE, CHANGES, README, thirdpartylibs, .gitignore, and Moodle Plugin CI.
- `$plugin->supported` for Moodle 4.5–5.2.

### Changed

- Requires Moodle 4.5+ and `local_timetracker` ≥ `2026090805` (1.4.2).
- Report link points to `/local/timetracker/report/` (no site-specific URL).

## 1.1.0 - 2026-09-08

### Fixed

- Block content restored for learners and teachers (empty `get_content()` early return removed).
- Empty-state messages so the block stays visible when no time is tracked yet.

## 1.0.0 - 2019-03-23

- Earlier ScholarLMS release.
