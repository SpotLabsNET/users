<?php

namespace Users;

/**
 * Allows users to be logged in with OAuth2.
 * Based on https://github.com/thephpleague/oauth2-client
 */
abstract class UserOAuth2 {

  /**
   * Get the {@link League\OAuth2\Client\Provider\Provider} for this
   * authentication handler.
   *
   * @param $redirect the registered redirect URI
   */
  abstract function getProvider($redirect);

  /**
   * Try logging in as a user with the given email and password.
   *
   * @param $redirect the registered redirect URI
   * @return a valid {@link User}
   * @throws UserAuthenticationException if the user could not be logged in, with a reason
   */
  static function tryLogin(\Db\Connection $db, $redirect) {
    $provider = static::getProvider($redirect);

    if (!require_get("code", false)) {
      user_redirect($provider->getAuthorizationUrl());
      return;
    } else {
      $token = $provider->getAccessToken('authorization_code', array(
        'code' => require_get("code"),
      ));

      // now find the relevant user
      $user = $provider->getUserDetails($token);
      print_r($user);
      throw new UserAuthenticationException("Not implemented");
    }
  }

  // TODO refreshing tokens
  // see https://github.com/thephpleague/oauth2-client

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the user already exists in the database
   */
  static function trySignup(\Db\Connection $db, $email, $password) {
    // TODO
    throw new UserSignupException("Not implemented");
  }

}
