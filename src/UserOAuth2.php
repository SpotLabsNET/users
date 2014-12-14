<?php

namespace Users;

/**
 * Allows users to be logged in with OAuth2.
 * Based on https://github.com/thephpleague/oauth2-client
 */
class UserOAuth2 {

  /**
   * Try logging in as a user with the given email and password.
   *
   * @param $redirect the registered redirect URI
   * @return a valid {@link User}
   * @throws UserAuthenticationException if the user could not be logged in, with a reason
   */
  static function tryLogin(\Db\Connection $db, $provider) {
    $user = UserOAuth2::auth($provider->getProvider());
    if (!$user) {
      throw new UserAuthenticationException("Could not login user with OAuth2");
    }

    $uid = $user->uid;
    if (!$uid) {
      throw new UserAuthenticationException("No UID found");
    }

    // find the user with the uid
    $q = $db->prepare("SELECT users.* FROM users
        JOIN user_oauth2_identities ON users.id=user_oauth2_identities.user_id
        WHERE uid=? AND provider=? LIMIT 1");
    $q->execute(array($uid, $provider->getKey()));

    if ($user = $q->fetch()) {
      $result = new User($user);
      $result->setIdentity($provider->getKey() . ":" . $uid);
      return $result;
    } else {
      throw new UserAuthenticationException("No such " . $provider->getKey() . " user found");
    }
  }

  /**
   * Execute OAuth2 authentication and return the user.
   */
  static function auth($provider) {
    if (!require_get("code", false)) {
      redirect($provider->getAuthorizationUrl());
      return false;
    } else {
      // optionally check for abuse etc
      if (!\Openclerk\Events::trigger('oauth2_auth', $provider)) {
        throw new UserAuthenticationException("Login was cancelled by the system");
      }

      $token = $provider->getAccessToken('authorization_code', array(
        'code' => require_get("code"),
      ));

      // now find the relevant user
      return $provider->getUserDetails($token);
    }
  }

  // TODO refreshing tokens
  // see https://github.com/thephpleague/oauth2-client

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the user already exists in the database
   */
  static function trySignup(\Db\Connection $db, $provider) {
    $identity = UserOAuth2::auth($provider->getProvider());
    if (!$identity) {
      throw new UserSignupException("Could not login with OAuth2");
    }

    $email = $identity->email;
    if ($email || \Openclerk\Config::get('users_require_email', false)) {
      if (!$email) {
        throw new UserSignupException("No email address found");
      }

      if (!is_valid_email($email)) {
        throw new UserSignupException("That is not a valid email.");
      }

      // does a user already exist with this email?
      $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
      $q->execute(array($email));
      if ($q->fetch()) {
        throw new UserAlreadyExistsException("That email is already in use");
      }

    }

    $uid = $identity->uid;
    if (!$uid) {
      throw new UserSignupException("No UID found");
    }

    // does such an identity already exist?
    $q = $db->prepare("SELECT * FROM user_oauth2_identities WHERE provider=? AND uid=? LIMIT 1");
    $q->execute(array($provider->getKey(), $uid));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That OAuth2 identity is already in use");
    }

    // create a new user
    $q = $db->prepare("INSERT INTO users SET email=?");
    $q->execute(array($email));
    $user_id = $db->lastInsertId();

    // create a new identity
    $q = $db->prepare("INSERT INTO user_oauth2_identities SET user_id=?, provider=?, uid=?");
    $q->execute(array($user_id, $provider->getKey(), $uid));

    return true;
  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the identity already exists in the database
   */
  static function addIdentity(\Db\Connection $db, User $user, $provider) {
    if (!$user) {
      throw new \InvalidArgumentException("No user provided");
    }

    $identity = UserOAuth2::auth($provider->getProvider());
    if (!$identity) {
      throw new UserSignupException("Could not login with OAuth2");
    }

    $uid = $identity->uid;
    if (!$uid) {
      throw new UserSignupException("No UID found");
    }

    // does such an identity already exist?
    $q = $db->prepare("SELECT * FROM user_oauth2_identities WHERE provider=? AND uid=? LIMIT 1");
    $q->execute(array($provider->getKey(), $uid));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That OAuth2 identity is already in use");
    }

    // create a new identity
    $q = $db->prepare("INSERT INTO user_oauth2_identities SET user_id=?, provider=?, uid=?");
    $q->execute(array($user->getId(), $provider->getKey(), $uid));

    return true;
  }

}
