<?php

namespace Users\Migrations;

class UserOpenIDIdentities extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE user_openid_identities (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      user_id int not null,
      identity varchar(255) not null,

      INDEX(user_id)
    );");
    return $q->execute();
  }

}
