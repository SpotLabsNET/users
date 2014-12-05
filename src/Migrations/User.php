<?php

namespace Users\Migrations;

class User extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE users (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,
      updated_at timestamp null,

      email varchar(255) not null,

      last_login timestamp null,

      INDEX(email)
    );");
    return $q->execute();
  }

}
