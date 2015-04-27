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
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    $light = new \LightOpenID(\Openclerk\Config::get("openid_host"));

    if (!$light->mode) {
      // we still need to authenticate

      $light->identity = $openid;
      $light->returnUrl = $redirect;
      redirect($light->authUrl());
      return false;

    } else if ($light->mode == 'cancel') {
      // user has cancelled
      throw new UserSignupException("User has cancelled authentication.");

    } else {
      // otherwise login as necessary

      // optionally check for abuse etc
      if (!\Openclerk\Events::trigger('openid_validate', $light)) {
        throw new UserAuthenticationException("Login was cancelled.");
      }

      if ($light->validate()) {

        $q = $db->prepare("SELECT users.* FROM users
            JOIN user_openid_identities ON users.id=user_openid_identities.user_id
            WHERE identity=? LIMIT 1");
        $q->execute(array($light->identity));
        if ($user = $q->fetch()) {
          $result = new User($user);
          $result->setIdentity("openid:" . $light->identity);

          return $result;
        } else {
          throw new UserAuthenticationMissingAccountException("No account for the OpenID identity '" . $light->identity . "' was found.");
        }

      } else {
        $error = $light->validate_error ? $light->validate_error : "Please try again.";
        throw new UserSignupException("OpenID validation was not successful: " . $error);
      }
    }

  }

  /**
   * Try do OpenID validation (with the given redirect).
   * @return the validated LightOpenID object on success
   * @throws UserSignupException if anything bad happened
   */
  static function validateOpenID($openid, $redirect) {
    if (!is_valid_url($openid)) {
      throw new UserSignupException("That is not a valid OpenID identity.");
    }
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    $light = new \LightOpenID(\Openclerk\Config::get("openid_host"));

    if (!$light->mode) {
      // we still need to authenticate

      $light->identity = $openid;
      $light->returnUrl = $redirect;
      redirect($light->authUrl());
      return false;

    } else if ($light->mode == 'cancel') {
      // user has cancelled
      throw new UserSignupException("User has cancelled authentication.");

    } else {
      // otherwise login as necessary

      // optionally check for abuse etc
      if (!\Openclerk\Events::trigger('openid_validate', $light)) {
        throw new UserSignupException("Login was cancelled by the system.");
      }

      if ($light->validate()) {
        return $light;
      } else {
        $error = $light->validate_error ? $light->validate_error : "Please try again.";
        throw new UserSignupException("OpenID validation was not successful: " . $error);
      }
    }
  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the identity or email already exists in the database
   * @return the created {@link User}
   */
  static function trySignup(\Db\Connection $db, $email, $openid, $redirect) {
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    if ($email || \Openclerk\Config::get('users_require_email', false)) {
      if (!is_valid_email($email)) {
        throw new UserSignupException("That is not a valid email.");
      }

      // does a user already exist with this email?
      $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
      $q->execute(array($email));
      if ($q->fetch()) {
        throw new UserAlreadyExistsException("That email is already in use.");
      }
    }

    $light = self::validateOpenID($openid, $redirect);

    // search for existing identities
    $q = $db->prepare("SELECT * FROM user_openid_identities WHERE identity=? LIMIT 1");
    $q->execute(array($light->identity));
    if ($identity = $q->fetch()) {
      throw new UserAlreadyExistsException("An account for the OpenID identity '" . $light->identity . "' already exists.");
    }

    // otherwise create a new account
    // create a new user
    $q = $db->prepare("INSERT INTO users SET email=?");
    $q->execute(array($email));
    $user_id = $db->lastInsertId();

    // create a new identity
    $q = $db->prepare("INSERT INTO user_openid_identities SET user_id=?, identity=?");
    $q->execute(array($user_id, $light->identity));

    return User::findUser($db, $user_id);
  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the identity already exists in the database
   * @return the added OpenID identity
   */
  static function addIdentity(\Db\Connection $db, User $user, $openid, $redirect) {
    if (!$user) {
      throw new \InvalidArgumentException("No user provided.");
    }

    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    $light = self::validateOpenID($openid, $redirect);

    // search for existing identities
    $q = $db->prepare("SELECT * FROM user_openid_identities WHERE identity=? LIMIT 1");
    $q->execute(array($light->identity));
    if ($identity = $q->fetch()) {
      throw new UserAlreadyExistsException("An account for the OpenID identity '" . $light->identity . "' already exists.");
    }

    // create a new identity
    $q = $db->prepare("INSERT INTO user_openid_identities SET user_id=?, identity=?");
    $q->execute(array($user->getId(), $light->identity));

    return $light->identity;
  }

  /**
   * Remove the given OpenID identity from the given user.
   */
  static function removeIdentity(\Db\Connection $db, User $user, $openid) {
    if (!$user) {
      throw new \InvalidArgumentException("No user provided.");
    }

    $q = $db->prepare("DELETE FROM user_openid_identities WHERE user_id=? AND identity=? LIMIT 1");
    return $q->execute(array($user->getId(), $openid));
  }

}
