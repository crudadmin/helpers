# Upgrading to 2.0

- PHP `crudadmin/helpers` 2.0 requires CrudAdmin 6 and framework 6.0.1 or the matching `6.0-dev` development branch. Use the 1.3 series with earlier CrudAdmin versions.
- `AdminHelpers\Utilities\BootstrapRequest` now extends `Admin\Core\Bootstrap\BootstrapRequest` (crudadmin/framework 6; the old `Admin\Core\Utilities\BootstrapRequest` name is only mapped by the `ClassMap` of `crudadmin/upgrade`). Existing project subclasses keep their imports, client/token handling, `onClient()`, and `auth()` methods.
- Section composition and bootstrap cache now live in the framework. The former `HasBootstrapCache` trait remains an alias. Cache arrays use `minutage` (minutes), with a default of 60; `duration` is an alias of `minutage` since framework 6.0-dev (October 2026). Projects which used `duration` silently got 60 minutes before and get their configured minutes now.
- The `1.4.0` release is replaced by `2.0.0`; update pinned Composer constraints.
- `AdminHelpers\Providers\ConfigServiceProvider` was removed, every provider of the package extends `Admin\Providers\AdminPackageServiceProvider`. `config/admin_helpers.php` of the project keeps its values and gets missing keys from the package, but a list of the project now replaces the list of the package instead of being appended to it (e.g. `notifications.platforms`, `notifications.whitelisted_tokens`).
- Only the `get`, `guest`, `authenticated` and `auth` sections and public methods added by the project can be loaded from `BootstrapRequest::only()`. Infrastructure methods (`getBundlePath`, `getBundleKey`, `cache`, `onClient`, `setToken`, `only`, `all`...) are no longer sections, and `authenticated` is not loaded for a guest.
- `logging.channels.notification` and `session.lottery` are set only when the project did not define them (the lottery only when it is missing or the Laravel default `[2, 100]`).
- `AppNotification::getColorAttribute()` returns `data.color` or `#49BFF2`; the orange color of notifications with `termine_id` was project specific, override the accessor in the project model.
- `isTestEnvironment()` is true for `local`, `staging` and the former misspelled `stagging` environment.

## Bootstrap classes moved to `AdminHelpers\Bootstrap` (6. 10. 2026, breaking)

Everything of the bootstrap moved from `Utilities` to `src/Bootstrap`. The old names were removed,
there are no aliases. Rename the imports in the project:

| Old (removed) | New |
| --- | --- |
| `AdminHelpers\Utilities\BootstrapRequest` | `AdminHelpers\Bootstrap\BootstrapRequest` |
| `AdminHelpers\Utilities\BootstrapResolver` | `AdminHelpers\Bootstrap\BootstrapResolver` |
| `AdminHelpers\Utilities\Concerns\HasBuildVersion` | `AdminHelpers\Bootstrap\Concerns\HasBuildVersion` |
| `AdminHelpers\Http\Controllers\BootstrapController` | `AdminHelpers\Bootstrap\BootstrapController` |

A project still importing an old name fails with "Class not found" when the bootstrap request is
loaded (`/api/bootstrap`, the login response with `auth.response = bootstrap`, `bootstrapRequest()`).
Search the project for `AdminHelpers\Utilities\Bootstrap`, `Utilities\Concerns\HasBuildVersion` and
`Http\Controllers\BootstrapController` (routes registered with the old controller class).

`AdminHelpers\Utilities\Concerns\HasBootstrapCache` stays the alias of the framework cache trait.

## Changes in 2.0 (October 2026)

All changes are backward compatible; new behaviour is opt-in by config unless noted.

- Bootstrap cache (framework): `forgetCache($section, $key = null, $locale = null)` invalidates a cached section, all locales and keys without arguments. Cache keys get a `.v{n}` suffix after the first `forgetCache()` of the section.
- Opt-in section whitelist (framework): `protected $sections = [...]` or the `Admin\Core\Bootstrap\Attributes\BootstrapSection` attribute (inherited by overrides, both combine). Without them every public method added by the project stays a section.
- `BootstrapRequest::setToken($name, $abilities = ['*'])` creates the token once per instance; repeated `auth()` calls return the same token. New `withClient($client)`.
- `AdminHelpers\Bootstrap\BootstrapResolver` (`getClass()`, `make()`, `appType()`, `routes()`), `bootstrapRequest()` function and `AdminHelpers\Bootstrap\BootstrapController`: the `app-type` header selects the bootstrap class from `admin_helpers.bootstrap.app_types`, the default `admin_helpers.bootstrap.class` (or the helpers `BootstrapRequest`) otherwise.
