<?php

namespace Users\Migrations;

class UserOAuth2Identities extends \Db\Migration {

  /**
   * Apply only the current migration.
   * @return true on success or false on failure
   */
  function apply(\Db\Connection $db) {
    $q = $db->prepare("CREATE TABLE user_oauth2_identities (
      id int not null auto_increment primary key,
      created_at timestamp not null default current_timestamp,

      user_id int not null,
      provider varchar(64) not null,
      uid varchar(64) not null,

      INDEX(user_id)
    );");
    return $q->execute();
  }

}
