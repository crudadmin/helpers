# Upgrading to 2.0

- PHP `crudadmin/helpers` 2.0 requires CrudAdmin 6 and framework 6.0.1 or the matching `6.0-dev` development branch. Use the 1.3 series with earlier CrudAdmin versions.
- `AdminHelpers\Utilities\BootstrapRequest` now extends `Admin\Core\Utilities\BootstrapRequest`. Existing project subclasses keep their imports, client/token handling, `onClient()`, and `auth()` methods.
- Section composition and bootstrap cache now live in the framework. The former `HasBootstrapCache` trait remains an alias. Cache arrays still use `minutage` (minutes), with a default of 60; `duration` is not an alias.
- The `1.4.0` release is replaced by `2.0.0`; update pinned Composer constraints.
