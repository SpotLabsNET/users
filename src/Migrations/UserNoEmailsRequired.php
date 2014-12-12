<?php

namespace Users\Migrations;

class UserNoEmailsRequired extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("ALTER TABLE users
      MODIFY email varchar(255) null;");
    return $q->execute();
  }

  function getParents() {
    return array(new User());
  }

}
