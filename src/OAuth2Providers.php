<?php

namespace Users;

/**
 * Provides an interface to supported OAuth2 providers.
 */
class OAuth2Providers {

  /**
   * Get the {@link League\OAuth2\Client\Provider\Provider} for this
   * authentication handler.
   */
  static function google($redirect) {
    return new \League\OAuth2\Client\Provider\Google(array(
      'clientId' =>\Openclerk\Config::get("oauth2_google_client_id"),
      'clientSecret' => \Openclerk\Config::get("oauth2_google_client_secret"),
      'redirectUri' => $redirect,
      'scopes' => array('email'),
    ));
  }

}
