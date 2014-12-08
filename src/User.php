<?php

namespace Users;

/**
 * Represents a user instance. Does not deal with authentication methods
 * (e.g. passwords, OpenID, OAuth2).
 */
class User {
  /**
   * Cache the user instance.
   */
  static $instance = null;

  var $params;

  var $is_auto_logged_in = false;

  /**
   * Construct a user instance from the given user parameters (from the database).
   */
  function __construct($params) {
    $this->params = $params;
  }

  /**
   * Get the current logged in user instance, or {@code null} if
   * there is none, based on session variables.
   *
   * @return the {@link User} logged in or {@code null} if none
   */
  static function getInstance(\Db\Connection $db) {
    if (User::$instance === null) {
      if (session_status() === PHP_SESSION_NONE) {
        throw new UserStatusException("Session needs to be started before requesting User instance");
      }

      // try autologin if we don't have session variables set
      $used_auto_login = false;
      if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_key']) && !isset($_SESSION['no_autologin'])) {
        User::tryAutoLogin();
        $used_auto_login = true;
      }

      // if the session variables are still not set after autologin, bail
      if (!isset($_SESSION['user_id']) && !isset($_SESSION['user_key'])) {
        return User::$instance;
      }

      // now try to find the valid user
      $q = $db->prepare("SELECT * FROM user_valid_keys WHERE user_id=? AND user_key=? LIMIT 1");
      $q->execute(array($_SESSION['user_id'], $_SESSION['user_key']));
      if ($user = $q->fetch()) {
        // find the associated user
        User::$instance = User::findUser($db, $user['user_id']);
        if (User::$instance) {
          User::$instance->is_auto_logged_in = $used_auto_login;
        }
      }

    }

    return User::$instance;
  }

  function persist(\Db\Connection $db, $use_cookies = false) {
    $user_key = sprintf("%04x%04x%04x%04x", rand(0,0xffff), rand(0,0xffff), rand(0,0xffff), rand(0,0xffff));

    // create a new valid user key
    $q = $db->prepare("INSERT INTO user_valid_keys SET user_id=?, user_key=?");
    $q->execute(array($this->getId(), $user_key));

    $_SESSION['user_id'] = $this->getId();
    $_SESSION['user_key'] = $user_key;
    unset($_SESSION['no_autologin']);

    if ($use_cookies) {
      $_COOKIE['autologin_id'] = $this->getId();
      $_COOKIE['autologin_key'] = $user_key;
    }

    // delete old web keys
    $days = \Openclerk\Config::get("autologin_expire_days");
    $q = $db->prepare("DELETE FROM user_valid_keys WHERE user_id=? AND created_at < DATE_SUB(NOW(), INTERVAL $days DAY)");
    $q->execute(array($this->getId()));
  }

  static function tryAutoLogin() {
    if (isset($_COOKIE['autologin_id']) && isset($_COOKIE['autologin_key'])) {
      $_SESSION['user_id'] = $_COOKIE['autologin_id'];
      $_SESSION['user_key'] = $_COOKIE['autologin_key'];
    }
  }

  static function logout(\Db\Connection $db) {
    unset($_SESSION['user_id']);
    unset($_SESSION['user_key']);
    unset($_SESSION['user_identity']);
    $_SESSION['no_autologin'] = true;
    User::$instance = null;
  }

  static function findUser(\Db\Connection $db, $user_id) {
    $q = $db->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
    $q->execute(array($user_id));
    if ($user = $q->fetch()) {
      return new User($user);
    } else {
      return false;
    }
  }

  function getId() {
    return $this->params['id'];
  }

  function getEmail() {
    return $this->params['email'];
  }

  function setIdentity($identity) {
    $_SESSION['user_identity'] = $identity;
  }

  /**
   * Get the identity used to log in this user; persists across requests
   * as it is stored in session.
   */
  function getIdentity() {
    return $_SESSION['user_identity'];
  }

  /**
   * Was this user logged in automatically *in this session*?
   */
  function isAutoLoggedIn() {
    return $this->is_auto_logged_in;
  }
}
