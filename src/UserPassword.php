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
    // find the user with the email
    $q = $db->prepare("SELECT * FROM users
        JOIN user_passwords ON users.id=user_passwords.user_id
        WHERE email=? AND password_hash=? LIMIT 1");
    $q->execute(array($email, UserPassword::hash($password)));
    if ($user = $q->fetch()) {
      return new User($user);
    } else {
      throw new UserAuthenticationException("No such email/password found");
    }
  }

  static function hash($password) {
    return md5(\Openclerk\Config::get("user_password_salt") . $password);;
  }

  /**
   * @throws UserSignupException if the user could not be signed up, with a reason
   * @throws UserAlreadyExistsException if the user already exists in the database
   */
  static function trySignup(\Db\Connection $db, $email, $password) {
    // does a user already exist with this email?
    $q = $db->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $q->execute(array($email));
    if ($q->fetch()) {
      throw new UserAlreadyExistsException("That email is already in use");
    }

    // create a new user
    $q = $db->prepare("INSERT INTO users SET email=?");
    $q->execute(array($email));
    $user_id = $db->lastInsertId();

    // create a new password
    $q = $db->prepare("INSERT INTO user_passwords SET user_id=?, password_hash=?");
    $q->execute(array($user_id, UserPassword::hash($password)));

    return true;
  }

  // TODO forgotten password, etc

}
