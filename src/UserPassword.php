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
   */
  static function tryLogin(\Db\Connection $db, $email, $password) {
    $hash = md5(\Openclerk\Config::get("user_password_salt") . $password);

    // find the user with the email
    $q = $db->prepare("SELECT * FROM users
        JOIN user_passwords ON user.id=user_passwords.user_id
        WHERE email=? AND password_hash=? LIMIT 1");
    $q->execute(array($email, $hash));
    if ($user = $q->fetch()) {
      return new User($user);
    } else {
      return false;
    }
  }

  // TODO forgotten password, etc

}
