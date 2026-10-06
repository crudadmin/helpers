# Testing backlog

Behaviour added without tests ("tests come last"). Each line is a test to write.

## SMS
- `SmartSms::sendSMS()` sends `POST https://smartsms.sk/api/send.do`, credentials in the form body, empty query string (Guzzle MockHandler + history; the client is created in the constructor, inject it or use reflection).

## Bootstrap request
- `setToken('login')` + two `auth()` calls create one Sanctum token and return the same payload; `setToken('other')` creates a new one.
- `auth()` of a guest returns `[]`; `only(['auth'])` for a guest does not fail.
- `withClient($user)` sets the client and calls `onClient()` once.
- `BootstrapResolver::getClass()`: default class, `app-type` mapped class, unknown type falls back to the default, empty config falls back to `AdminHelpers\Utilities\BootstrapRequest`, a class not extending the framework request throws.
- `BootstrapResolver::make()` returns a new instance each call; `bootstrapRequest()` helper.
- `BootstrapController@index` parses `?only=a,b` and `only[]=a` and wraps the sections in `store`.

## Auth
- `admin_helpers.auth.guard`: login sets the user into that guard, `getAuthModel()` reads the logged user of that guard and the model of its provider.
- `admin_helpers.auth.model` overrides the model.
- `admin_helpers.auth.login_event = true` dispatches `Illuminate\Auth\Events\Login` with the guard name on login, registration and password reset; not dispatched by default.
- `admin_helpers.auth.response = 'bootstrap'`: response has `store.auth.token` plus the `authenticated()` sections; `auth` is added when `authenticated()` does not compose it; `GET user` creates no token.
- `AuthResponse` with a model without `setAuthResponse()` returns the model.
- Password: `auth/password/forgot` returns the same message for unknown e-mails; `auth/password/reset` sets the hashed password, marks the e-mail verified, logs in with a `password` token; invalid token answers 422; broker config is created for a provider without `auth.passwords`.
- `PasswordReset::sendSetPasswordLink()` sends `SetPasswordNotification` with `reset_url` placeholders replaced; `getResetLink()` of the model wins.
- E-shop `Client` (Sanctum guard `api`) end to end: login, register, reset with the eshop model.

## 5. 10. 2026

- `getVerificator()` with `otp.enabled` false / true (registration without code, OTP login).
- `GET /user` without a message in both response modes (auth, bootstrap); login keeps it.
