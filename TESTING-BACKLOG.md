# Testing backlog

Behaviour added without tests ("tests come last"). Each line is a test to write.

## Bootstrap request
- `setToken('login')` + two `auth()` calls create one Sanctum token and return the same payload; `setToken('other')` creates a new one.
- `auth()` of a guest returns `[]`; `only(['auth'])` for a guest does not fail.
- `withClient($user)` sets the client and calls `onClient()` once.
- `BootstrapResolver::getClass()`: default class, `app-type` mapped class, unknown type falls back to the default, empty config falls back to `AdminHelpers\Utilities\BootstrapRequest`, a class not extending the framework request throws.
- `BootstrapResolver::make()` returns a new instance each call; `bootstrapRequest()` helper.
- `BootstrapController@index` parses `?only=a,b` and `only[]=a` and wraps the sections in `store`.
