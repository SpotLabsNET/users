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
    "openclerk/users": "dev-master"
  },
  "repositories": [{
    "type": "vcs",
    "url": "https://github.com/openclerk/users"
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

## Features

1. Automatic session management
1. Autologin
1. Optionally require emails for all non-password users with `users_require_email` config parameter
1. Forgot password/reset password functionality

## Using

This project uses [openclerk/db](https://github.com/openclerk/db) for database
management and [openclerk/config](https://github.com/openclerk/config) for config management.

First configure the component with site-specific values:

```php
Openclerk\Config::merge(array(
  "users_require_email" => false,
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

```php
// get current user
$user = Users\User::getInstance(db());

// logout any current user
Users\User::logout(db());

// get a user instance
$user = Users\User::findUser(db(), $user_id);
```

### Password

```php
// signup
$user = Users\UserPassword::trySignup(db(), $email /* may not be null */, $password);
if ($user) {
  echo "<h2>Signed up successfully</h2>";
}

// login
$user = Users\UserPassword::tryLogin(db(), $email /* may not be null */, $password);
if ($user) {
  echo "<h2>Logged in successfully as $user</h2>";
  $user->persist(db());
}

// forgot password
$secret = Users\UserPassword::forgottenPassword(db(), $email);
echo "Secret = $secret\n";

// complete forgot password
Users\UserPassword::completePasswordReset(db(), $email, $secret, $new_password);
```

### OpenID

You need to set a redirect value for all the OpenID callbacks, normally the same
URL as the current script.

```php
// signup
$user = Users\UserOpenID::trySignup(db(), $email /* may be null */, $openid, "http://localhost/register.php");
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

1. Track last_login
1. Add and remove identities
1. Tests
1. Publish on Packagist
1. Add user names, other user properties
1. Documentation on adding additional user parameters
