Per-Channel Templates Are Source of Truth

- Persistence: Notification content is stored per channel in `notification_templates` uniquely by `(module,event,channel)`. Rule-level `subject_template`, `body_template`, and `sms_template` on `notification_rules` are legacy/fallback only.
- Rendering: UI matrix reads `enabled` and `channels` from `notification_rules`, and templates from `notification_templates`. Fallback order for templates is: per-channel row → legacy rule columns → config defaults from `config('notification_events')`.
- Validation: The server validates templates only for channels being enabled in a request. Examples:
  - If `email` is enabled, require `subject_template` and `body_template`.
  - If `sms` is enabled, require `sms_template`.
  - If `database` is enabled, `internal_template` is optional (falls back to email `body_template` if provided).
- Updates:
  - Store: upserts per selected channels only. Non-selected channels are not deleted.
  - Update: if a channel’s templates are present in the payload, they are upserted; otherwise left unchanged.

Notes

- A unique index on `notification_templates (module,event,channel)` enforces one row per channel per event. Rule columns are nullable for backward compatibility.
- Toggle All and the hidden `enabled` input are kept in sync on the client; the server persists `enabled` directly from the request and re-renders strictly from DB state.
