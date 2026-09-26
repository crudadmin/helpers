# Upgrading to 2.0

- PHP `crudadmin/helpers` 2.0 requires CrudAdmin 6 and framework 6.0.1 or the matching `6.0-dev` development branch. Use the 1.3 series with earlier CrudAdmin versions.
- `AdminHelpers\Utilities\BootstrapRequest` now extends `Admin\Core\Utilities\BootstrapRequest`. Existing project subclasses keep their imports, client/token handling, `onClient()`, and `auth()` methods.
- Section composition and bootstrap cache now live in the framework. The former `HasBootstrapCache` trait remains an alias. Cache arrays still use `minutage` (minutes), with a default of 60; `duration` is not an alias.
- The `1.4.0` release is replaced by `2.0.0`; update pinned Composer constraints.
- `AdminHelpers\Providers\ConfigServiceProvider` was removed, every provider of the package extends `Admin\Providers\AdminPackageServiceProvider`. `config/admin_helpers.php` of the project keeps its values and gets missing keys from the package, but a list of the project now replaces the list of the package instead of being appended to it (e.g. `notifications.platforms`, `notifications.whitelisted_tokens`).
- Only the `get`, `guest`, `authenticated` and `auth` sections and public methods added by the project can be loaded from `BootstrapRequest::only()`. Infrastructure methods (`getBundlePath`, `getBundleKey`, `cache`, `onClient`, `setToken`, `only`, `all`...) are no longer sections, and `authenticated` is not loaded for a guest.
- `logging.channels.notification` and `session.lottery` are set only when the project did not define them (the lottery only when it is missing or the Laravel default `[2, 100]`).
- `AppNotification::getColorAttribute()` returns `data.color` or `#49BFF2`; the orange color of notifications with `termine_id` was project specific, override the accessor in the project model.
- `isTestEnvironment()` is true for `local`, `staging` and the former misspelled `stagging` environment.
