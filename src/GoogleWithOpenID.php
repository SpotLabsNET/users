<?php

namespace Users;

use League\OAuth2\Client\Provider\Google;

/**
 * Allows us to get OpenID identity information through Google OAuth2,
 * as described at https://developers.google.com/identity/protocols/OpenID2Migration#adjust-uri
 */
class GoogleWithOpenID extends Google {

  var $id_token = false;

  public function getAuthorizationUrl($options = []) {
    return parent::getAuthorizationUrl($options) . '&openid.realm=' . "http://" . \Openclerk\Config::get('openid_host');
  }

  protected function prepareAccessTokenResult(array $result) {
    if (isset($result['id_token'])) {
      $id_token = $result['id_token'];

      // [signature, token, ???]
      $id_token_bits = explode(".", $result['id_token']);

      // we could validate the token here but eh
      if (count($id_token_bits) >= 2) {
        $this->id_token = json_decode(base64_decode($id_token_bits[1]), true /* assoc */);
      }
    }

    return parent::prepareAccessTokenResult($result);
  }

}
