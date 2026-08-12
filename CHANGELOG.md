# Changelog

All notable changes to AS Gated Content are recorded here.

## 0.2.3 - 2026-08-13

- Replaced separate rule condition repeaters with a single Conditions repeater for category and meta matches.
- Updated rule matching so the Condition mode applies across unified condition rows.

## 0.2.2 - 2026-08-13

- Removed the unused hidden Overlay Mode field from Gate settings.

## 0.2.1 - 2026-08-13

- Moved Gate Rule post category targeting into a Conditions tab repeater.
- Removed the Rule priority instructional text from the admin interface.

## 0.2.0 - 2026-08-13

- Added content-level gate behaviour controls for inherit, override, and disable gates.
- Added prioritized Gate Rules with current-content matching.
- Added optional post category targeting for post Gate Rules.
- Added advanced meta condition matching with all/any logic.
- Updated rule resolution so the highest-priority matching rule wins.

## 0.1.2 - 2026-06-18

- Fixed exit-intent threshold handling so repeated leave attempts can count until the modal opens.
- Made desktop exit-intent detection more forgiving near the top of the viewport, including when the WordPress admin bar is visible.
- Added a softer fade-in and dialog entrance transition for the gate overlay.

## 0.1.1 - 2026-06-18

- Renamed plugin slug, package, and GitHub release target to as-gated-content.
- Improved desktop exit-intent detection for upward pointer movement, top-edge leave events, and near-top window blur.

## 0.1.0 - 2026-06-18

- Added Gate and Gate Rule custom post types.
- Added ACF Pro local field registration for gate, rule, and page-level settings.
- Added Gravity Forms rendering, dependency notices, and visitor submission suppression.
- Added entrance and desktop exit-intent gated overlay behaviour.
- Added GitHub release updater support.
