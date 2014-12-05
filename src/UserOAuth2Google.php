<?php

namespace Users;

/**
 * Allows users to be logged in with OAuth2 Google.
 */
class UserOAuth2Google extends UserOAuth2 {

  /**
   * Get the {@link League\OAuth2\Client\Provider\Provider} for this
   * authentication handler.
   */
  function getProvider($redirect) {
    return new \League\OAuth2\Client\Provider\Google(array(
      'clientId' =>\Openclerk\Config::get("oauth2_google_client_id"),
      'clientSecret' => \Openclerk\Config::get("oauth2_google_client_secret"),
      'redirectUri' => $redirect,
      'scopes' => array('email'),
    ));
  }

}
