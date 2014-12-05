<?php

namespace Users\Migrations;

class UserValidKeys extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE user_valid_keys (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      user_id int not null,
      user_key varchar(16) not null,

      INDEX(user_id, user_key)
    );");
    return $q->execute();
  }

}
