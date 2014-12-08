openclerk/users
===============

A library for User management in Openclerk, supporting password, OpenID
and OAuth2 login.

## Installing

Include `openclerk/users` as a requirement in your project `composer.json`,
and run `composer update` to install it into your project:

```json
{
  "require": {
    "openclerk/db": "dev-master"
  },
  "repositories": [{
    "type": "vcs",
    "url": "https://github.com/openclerk/db"
  }]
}
```

Make sure that you run all of the migrations that can be discovered
through [component-discovery](https://github.com/soundasleep/component-discovery);
see the documentation on [openclerk/db](https://github.com/openclerk/db) for more information.

```php
$migrations = new AllMigrations(db());
if ($migrations->hasPending(db())) {
  $migrations->install(db(), $logger);
}
```

## Using

This project uses [openclerk/db](https://github.com/openclerk/db) for database
management and [openclerk/config](https://github.com/openclerk/config) for config management.

First configure the component with site-specific values:

```php
Openclerk\Config::merge(array(
  "user_password_salt" => "abc123",
  "autologin_expire_days" => 30,
  "openid_host" => "localhost",
  "oauth2_google_client_id" => "abc123.apps.googleusercontent.com",
  "oauth2_google_client_secret" => "abc123",
));

session_start();
```

You can now register and login users using a variety of authentication methods.
The component assumes that only one user can own any one email address, and that
all users need to define an email address as their primary key.

### Password

```php
// signup
$user = Users\UserPassword::trySignup(db(), $email, $password);
if ($user) {
  echo "<h2>Signed up successfully</h2>";
}

// login
$user = Users\UserPassword::tryLogin(db(), $email, $password);
if ($user) {
  echo "<h2>Logged in successfully as $user</h2>";
  $user->persist(db());
}
```

### OpenID

You need to set a redirect value for all the OpenID callbacks, normally the same
URL as the current script.

```php
// signup
$user = Users\UserOpenID::trySignup(db(), $email, $openid, "http://localhost/register.php");
if ($user) {
  echo "<h2>Signed up successfully</h2>";
}

// login
$user = Users\UserOpenID::tryLogin(db(), $openid, "http://localhost/login.php");
if ($user) {
  echo "<h2>Logged in successfully as $user</h2>";
  $user->persist(db());
}
```

### OAuth2

For Google OAuth2, login to your [Google Developers Console](https://console.developers.google.com/project),
create a new Project, and visit *APIs & Auth*:

1. *APIs:* Enable _Contacts API_ and _Google+ API_

2. *Credentials:* create a new _Client ID_ of type web applicaton, setting your permissible _Redirect URI_ to the
   login and redirect URLs used in your application. Use the generated _Client ID_ and _Client Secret_ in your
   site configuration (above).

```php
// signup
$user = Users\UserOAuth2::trySignup(db(), Users\OAuth2Providers::google("http://localhost/register.php"));
if ($user) {
  echo "<h2>Signed up successfully</h2>";
}

// login
$user = Users\UserOAuth2::tryLogin(db(), Users\OAuth2Providers::google("http://localhost/openclerk2/login-oauth2.php"));
if ($user) {
  echo "<h2>Logged in successfully as $user</h2>";
  $user->persist(db());
}
```

More OAuth2 providers provided by default will be coming soon.

## TODO

1. Remove requirement for email primary key
1. Tests
1. Publish on Packagist
