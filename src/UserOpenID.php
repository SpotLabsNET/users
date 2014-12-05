<?php

namespace Users;

/**
 * Allows users to be logged in with OpenID.
 * Based on LightOpenID
 */
class UserOpenID {

  /**
   * Try logging in as a user with the given email and password.
   *
   * @param $redirect the registered redirect URI
   * @return a valid {@link User}
   * @throws UserAuthenticationException if the user could not be logged in, with a reason
   */
  static function tryLogin(\Db\Connection $db, $openid, $redirect) {
    if (!is_valid_url($openid)) {
      throw new UserSignupException("That is not a valid OpenID identity.");
    }

    $light = new LightOpenID(\Openclerk\Config::get("openid_host"));

    if (!$light->mode) {
      // we still need to authenticate

      $light->identity = $openid;
      $light->returnUrl = $redirect;
      redirect($light->authUrl());
      return false;

    } else if ($light->mode == 'cancel') {
      // user has cancelled
      throw new UserSignupException("User has cancelled authentication");

    } else {
      // otherwise login as necessary

      // TODO check heavy requests

      if ($light->validate()) {

        $q = $db->prepare("SELECT users.* FROM users
            JOIN users_openid_identities ON users.id=users_openid_identities.user_id
            WHERE identity=? LIMIT 1");
        $q->execute(array($light->identity));
        if ($identity = $q->fetch()) {
          $result = new User($user);
          $result->setIdentity("openid:" . $light->identity);
        } else {
          throw new UserAuthenticationException("No account for the OpenID identity '" . $light->identity . "' was found.");
        }

      } else {
        $error = $light->validate_error ? $light->validate_error : "Please try again.";
        throw new UserSignupException("OpenID validation was not successful: " . $error);
      }
    }

  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the user already exists in the database
   */
  static function trySignup(\Db\Connection $db, $email, $openid, $redirect) {
    if (!is_valid_url($openid)) {
      throw new UserSignupException("That is not a valid OpenID identity.");
    }

    // does a user already exist with this email?
    $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $q->execute(array($email));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That email is already in use");
    }

    $light = new LightOpenID(\Openclerk\Config::get("openid_host"));

    if (!$light->mode) {
      // we still need to authenticate

      $light->identity = $openid;
      $light->returnUrl = $redirect;
      redirect($light->authUrl());
      return false;

    } else if ($light->mode == 'cancel') {
      // user has cancelled
      throw new UserSignupException("User has cancelled authentication");

    } else {
      // otherwise login as necessary

      // TODO check heavy requests

      if ($light->validate()) {

        $q = $db->prepare("SELECT * FROM users_openid_identities WHERE identity=? LIMIT 1");
        $q->execute(array($light->identity));
        if ($identity = $q->fetch()) {
          throw new UserAlreadyExistsException("An account for the OpenID identity '" . $light->identity . "' already exists.");
        }

        // otherwise create a new account
        // create a new user
        $q = $db->prepare("INSERT INTO users SET email=?");
        $q->execute(array($email));
        $user_id = $db->lastInsertId();

        // create a new password
        $q = $db->prepare("INSERT INTO users_openid_identities SET user_id=?, identity=?");
        $q->execute(array($user_id, $light->identity));

        return true;

      } else {
        $error = $light->validate_error ? $light->validate_error : "Please try again.";
        throw new UserSignupException("OpenID validation was not successful: " . $error);
      }
    }
  }

}
