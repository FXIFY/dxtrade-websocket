# Changelog

All notable changes to `dxtrade-websocket` will be documented in this file.

## 1.1.0 - 2026-03-10

- Added support for split DXTrade endpoints with dedicated `auth_url`, `push_session_url`, and `websocket_url` configuration.
- Added `direct` websocket connection mode for DXTrade deployments that do not expose `POST /push/session`.
- Added outbound timestamp format configuration with support for ISO 8601 timestamps required by documented Push API examples.
- Normalized account subscription payloads to documented DXTrade Push API shapes using `requestType: LIST` and account keys like `default:ACCOUNT_CODE`.
- Fixed direct websocket connection handling to use proper `ws://` / `wss://` schemes and correct channel paths in direct mode.
- Normalized DXTrade collection responses such as `portfolios`, `metrics`, `accountEvents`, and `cashTransfers` into per-entity processor events.
- Derived `accountCode`, `accountId`, and `clearingCode` from DXTrade account payloads before dispatching processor events.
- Fixed the websocket runner to subscribe once per connection and process incoming frames serially, avoiding duplicate subscriptions and Swoole socket / database concurrency issues.
- Updated README examples and configuration guidance to match the validated DXTrade Push API contract and staging-compatible direct websocket setup.

## 1.0.0 - 2026-01-25

- Initial release
- DXTrade websocket client support
- Event processing capabilities
