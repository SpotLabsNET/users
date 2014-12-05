<?php

namespace Users;

/**
 * Provides an interface to supported OAuth2 providers.
 */
class OAuth2Providers {

  var $key;
  var $provider;

  function __construct($key, $provider) {
    $this->key = $key;
    $this->provider = $provider;
  }

  function getProvider() {
    return $this->provider;
  }

  function getKey() {
    return $this->key;
  }

  /**
   * Get the {@link League\OAuth2\Client\Provider\Provider} for this
   * authentication handler.
   */
  static function google($redirect) {
    return new OAuth2Providers("google", OAuth2Providers::loadProvider("google", $redirect));
  }

  static function loadProvider($key, $redirect) {
    switch ($key) {
      case "google":
        return new \League\OAuth2\Client\Provider\Google(array(
          'clientId' =>\Openclerk\Config::get("oauth2_google_client_id"),
          'clientSecret' => \Openclerk\Config::get("oauth2_google_client_secret"),
          'redirectUri' => $redirect,
          'scopes' => array('email'),
        ));

      default:
        throw new UserAuthenticationException("No such known OAuth2 provider '$key'");
    }
  }

}
