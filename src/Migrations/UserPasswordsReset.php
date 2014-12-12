<?php

namespace Users\Migrations;

class UserPasswordsReset extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("ALTER TABLE user_passwords
      ADD reset_password_secret varchar(128) null,
      ADD reset_password_requested timestamp null;
    ");
    return $q->execute();
  }

  function getParents() {
    return array(new UserPassword());
  }

}
