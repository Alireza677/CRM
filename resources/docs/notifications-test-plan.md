Notification Rules – Manual Test Plan

Scenarios

1) New rule with only database channel enabled
- Steps:
  - Open `/settings/notifications`.
  - Pick an event with no rule yet. Enable the row, select only `database` channel.
  - Enter internal/system text optionally; leave email subject/body empty.
  - Save.
- Expected:
  - POST payload contains `enabled=1`, `channels[]=['database']` and no email requirement errors.
  - DB: `notification_rules` row has `enabled=1`, `channels=["database"]`.
  - DB: `notification_templates` has one row for `(module,event,'database')` with `body_template` set (or empty if not provided).
  - UI re-renders with the switch ON.

2) Enable email channel; require subject/body and persist in templates
- Steps:
  - Edit the same row; add `email` to channels.
  - Provide `subject_template` and `body_template`.
  - Save.
- Expected:
  - Validation requires both email fields if they were missing.
  - DB: upserted `notification_templates` row for `(module,event,'email')` with subject/body populated.
  - Rule `enabled` remains ON.

3) Toggle All ON then save a row
- Steps:
  - Use Toggle All to turn every row ON, save at least one row.
- Expected:
  - Request carries `enabled=1` for that row; DB persists it and UI re-renders ON.

4) Disable a channel and save
- Steps:
  - Uncheck `sms` (or any channel) for a row and save.
- Expected:
  - `notification_rules.channels` updated to exclude that channel.
  - Existing `notification_templates` rows for the disabled channel remain intact (not deleted).

How to capture evidence

- HAR/payload: Use browser DevTools Network tab, right-click the request → Save all as HAR with content; or copy the JSON form data shown.
- DB snapshots:
  - Use Tinker or DB client to inspect rows:
    - `select * from notification_rules where module='X' and event='Y' \G`.
    - `select * from notification_templates where module='X' and event='Y' order by channel;`.

Notes

- Placeholders validation only applies to fields sent and/or required by selected channels.
- Templates are the per-channel rows; rule-level columns are legacy fallback only.
