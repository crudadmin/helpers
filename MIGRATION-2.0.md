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

## Bootstrap route for crudadmin/eshop (6. 10. 2026)

`BootstrapResolver::routes()` registers the route in the namespace of the controller, so the visible
routes of `crudadmin/website` name it `BootstrapController@index`. `crudadmin/eshop` 5 registers its
`GET /bootstrap` by it and sets `admin_helpers.bootstrap.class` to its `BootstrapRequest` when empty.

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

- `AdminHelpers\Sms\SmartSms` sends `POST https://smartsms.sk/api/send.do` with the parameters in the form body. Credentials are no longer part of the URL. Config keys (`smartsms.*`, `admin_helpers.smartsms.*`) are unchanged. Error `105` (IP of the calling server not allowed for the API) has its own message now.
- Bootstrap cache (framework): `forgetCache($section, $key = null, $locale = null)` invalidates a cached section, all locales and keys without arguments. Cache keys get a `.v{n}` suffix after the first `forgetCache()` of the section.
- Opt-in section whitelist (framework): `protected $sections = [...]` or the `Admin\Core\Bootstrap\Attributes\BootstrapSection` attribute (inherited by overrides, both combine). Without them every public method added by the project stays a section.
- `BootstrapRequest::setToken($name, $abilities = ['*'])` creates the token once per instance; repeated `auth()` calls return the same token. New `withClient($client)`.
- `AdminHelpers\Bootstrap\BootstrapResolver` (`getClass()`, `make()`, `appType()`, `routes()`), `bootstrapRequest()` function and `AdminHelpers\Bootstrap\BootstrapController`: the `app-type` header selects the bootstrap class from `admin_helpers.bootstrap.app_types`, the default `admin_helpers.bootstrap.class` (or the helpers `BootstrapRequest`) otherwise.
- Auth config `admin_helpers.auth.guard`, `auth.model`, `auth.response` (`auth` | `bootstrap`), `auth.login_event`, `auth.password.*`. With `auth.guard` set, `makeAuthResponse()` sets the user into that guard instead of `$user->getGuard()`, and `getAuthModel()` reads the logged user of that guard.
- `AuthResponse` returns the model itself when it has no `setAuthResponse()` method.
- Password reset: `AdminAuth::password()` (`POST auth/password/forgot`, `POST auth/password/reset`), `AdminHelpers\Auth\Controllers\PasswordController`, `HasPasswordReset`, model trait `HasPasswordResetLink`, `AdminHelpers\Auth\Utilities\PasswordReset::sendSetPasswordLink($user, $guard = null)` with `SetPasswordNotification`.

## OTP verificator and GET /user (5. 10. 2026)

- `getVerificator()` returns `null` while `admin_helpers.auth.otp.enabled` is not `true`: the
  registration and the OTP login need no code then. With OTP off the OTP models are not
  registered, a configured `auth.verificator` (default `email`) made the registration wait for a
  code nobody could create. Projects with OTP verification turn `otp.enabled` on.
- `GET /user` answers without the message "Boli ste úspešne prihlásený." (only a login,
  registration or password reset carries it, `authorizedMessage($type)`).
