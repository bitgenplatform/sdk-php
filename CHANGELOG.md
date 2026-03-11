## [0.1.5] - 2026-03-11

### Changed
- `BitgenRawException` removed — HTTP 500/400 errors from `POST /api/v3/tx` are now wrapped as a standard `BitgenException`
- All methods accepting a user now support `string|UserFull|UserListItem` in addition to a raw UUID string
- Added `Support/UserRef` for user reference resolution
- `Config` now supports 3 standard envs (`sandbox`, `production`, `localhost`) and a custom host mode (env ignored, HTTP, default port 80)

### Breaking
- `BitgenRawException` no longer exists — replace any `catch (BitgenRawException $e)` with `catch (BitgenException $e)`