<?php

namespace Users;

/**
 * Allows users to be logged in with emails and passwords.
 */
class UserPassword {

  /**
   * Try logging in as a user with the given email and password.
   *
   * @return a valid {@link User}
   * @throws UserAuthenticationException if the user could not be logged in, with a reason
   */
  static function tryLogin(\Db\Connection $db, $email, $password) {
    if ($email === null) {
      throw new UserAuthenticationException("Email required for password login.");
    }

    // find the user with the email
    $q = $db->prepare("SELECT users.* FROM users
        JOIN user_passwords ON users.id=user_passwords.user_id
        WHERE email=? AND user_passwords.password_hash=? LIMIT 1");
    $q->execute(array($email, UserPassword::hash($password)));
    if ($user = $q->fetch()) {
      $result = new User($user);
      $result->setIdentity("(password)");
      return $result;
    } else {
      throw new UserAuthenticationMissingAccountException("No such email/password found.");
    }
  }

  static function hash($password) {
    return md5(\Openclerk\Config::get("user_password_salt") . $password);
  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the user already exists in the database
   * @return the created {@link User}
   */
  static function trySignup(\Db\Connection $db, $email, $password) {
    if ($email === null) {
      throw new UserAuthenticationException("Email required for password signup.");
    }

    if (!is_valid_email($email)) {
      throw new UserAuthenticationException("That is not a valid email.");
    }

    // does a user already exist with this email?
    $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $q->execute(array($email));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That email is already in use.");
    }

    // create a new user
    $q = $db->prepare("INSERT INTO users SET email=?");
    $q->execute(array($email));
    $user_id = $db->lastInsertId();

    // create a new password
    $q = $db->prepare("INSERT INTO user_passwords SET user_id=?, password_hash=?");
    $q->execute(array($user_id, UserPassword::hash($password)));

    return User::findUser($db, $user_id);
  }

  /**
   * @throws UserPasswordException if something went wrong
   */
  static function getPasswordUser(\Db\Connection $db, $email) {
    if ($email === null) {
      throw new UserPasswordException("Email required");
    }

    if (!is_valid_email($email)) {
      throw new UserPasswordException("That is not a valid email.");
    }

    // does a user exist for this email?
    $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $q->execute(array($email));
    $user = $q->fetch();
    if (!$user) {
      throw new UserPasswordException("No such account exists.");
    }

    // is there a password user?
    $q = $db->prepare("SELECT * FROM user_passwords WHERE user_id=?");
    $q->execute(array($user['id']));
    $user_password = $q->fetch();
    if (!$user_password) {
      throw new UserPasswordException("That account does not have an associated password.");
    }

    return $user;
  }

  /**
   * Create a forgotten password secret which is stored in the database.
   * This will return a secret which must then be passed along with the mail to {@link completePasswordReset()}
   * in order to reset the password.
   * It is up to the application to send the appropriate email/etc.
   *
   * @throws UserPasswordException if something went wrong
   */
  static function forgottenPassword(\Db\Connection $db, $email) {
    $user = self::getPasswordUser($db, $email);

    $secret = random16(32);

    $q = $db->prepare("UPDATE user_passwords SET reset_password_secret=?, reset_password_requested=NOW() WHERE user_id=?");
    $q->execute(array($secret, $user['id']));

    return $secret;
  }

  /**
   * @throws UserPasswordException if something went wrong
   */
  static function completePasswordReset(\Db\Connection $db, $email, $secret, $new_password) {
    $user = self::getPasswordUser($db, $email);

    if (!$secret) {
      throw new UserPasswordException("No secret supplied.");
    }

    $q = $db->prepare("SELECT * FROM user_passwords WHERE user_id=?");
    $q->execute(array($user['id']));
    $user_password = $q->fetch();

    if ($user_password['reset_password_secret'] === $secret) {
      if (strtotime('-' . \Openclerk\Config::get('user_password_reset_expiry')) < strtotime($user_password['reset_password_requested'])) {
        $q = $db->prepare("UPDATE user_passwords SET reset_password_secret=NULL, password_hash=? WHERE user_id=?");
        $q->execute(array(UserPassword::hash($new_password), $user['id']));
        return true;
      } else {
        throw new UserPasswordException("Password request has expired.");
      }
    } else {
      throw new UserPasswordException("Incorrect password reset secret.");
    }
  }

  /**
   * Add a password to the given account.
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the password already exists in the database
   */
  static function addPassword(\Db\Connection $db, User $user, $password) {
    if (!$user) {
      throw new \InvalidArgumentException("No user provided.");
    }

    // does such a password already exist?
    $q = $db->prepare("SELECT * FROM user_passwords WHERE user_id=? LIMIT 1");
    $q->execute(array($user->getId()));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That account already has a password.");
    }

    // does the user have an email? required
    $email = $user->getEmail();
    if (!$email) {
      throw new UserSignupException("That account requires an email address to add a password.");
    } else if (!is_valid_email($email)) {
      throw new UserSignupException("That is not a valid email.");
    }

    // create a new password
    $q = $db->prepare("INSERT INTO user_passwords SET user_id=?, password_hash=?");
    $q->execute(array($user->getId(), UserPassword::hash($password)));

    return true;
  }

  /**
   * Delete all paswords for the given user.
   */
  static function deletePasswords(\Db\Connection $db, User $user) {
    if (!$user) {
      throw new \InvalidArgumentException("No user provided.");
    }

    // does such a password already exist?
    $q = $db->prepare("DELETE FROM user_passwords WHERE user_id=?");
    $q->execute(array($user->getId()));

    return true;
  }

  /**
   * Change the given users' password.
   * Removes all existing passwords and then adds a new password.
   *
   * @throws UserPasswordException if something went wrong
   */
  static function changePassword(\Db\Connection $db, User $user, $password) {
    self::deletePasswords($db, $user);
    return self::addPassword($db, $user, $password);
  }

  // TODO forgotten password, etc

}
