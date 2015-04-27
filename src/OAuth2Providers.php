<?php

namespace Users;

/**
 * Provides an interface to supported OAuth2 providers.
 * TODO this could be abstracted out into component-discovery for each provider
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
   * Get the {@link League\OAuth2\Client\Provider\Provider} for the Google
   * authentication handler.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   */
  static function google($redirect) {
    return new OAuth2Providers("google", OAuth2Providers::loadProvider("google", $redirect));
  }

  /**
   * Load the {@link AbstractProvider} with the given key, from {@link #getProviders()}.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   */
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

  /**
   * Get a list of all the provider keys supported by this component.
   */
  static function getProviders() {
    return array(
      "google",
    );
  }

}
