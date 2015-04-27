<?php

namespace Users;

use League\OAuth2\Client\Provider\ProviderInterface;

/**
 * Provides an interface to supported OAuth2 providers.
 * We need to provide a wrapper around this so we can map our `oauth2 provider` key (e.g. 'google'
 * to the relevant {@link ProviderInterface} for OAuth2.)
 * TODO this could be abstracted out into component-discovery for each provider
 */
class OAuth2Providers {

  function __construct($key, ProviderInterface $provider) {
    $this->key = $key;
    $this->provider = $provider;
  }

  function getKey() {
    return $this->key;
  }

  function getProvider() {
    return $this->provider;
  }

  /**
   * Get the {@link OAuth2Providers} for the Google
   * authentication handler.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   * @return A {@link OAuth2Providers}
   */
  static function google($redirect) {
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    return new OAuth2Providers('google', OAuth2Providers::loadProvider("google", $redirect));
  }

  /**
   * Get the {@link OAuth2Providers} for the Facebook
   * authentication handler.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   * @return A {@link OAuth2Providers}
   */
  static function facebook($redirect) {
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    return new OAuth2Providers('facebook', OAuth2Providers::loadProvider("facebook", $redirect));
  }

  /**
   * Get the {@link OAuth2Providers} for the Github
   * authentication handler.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   * @return A {@link OAuth2Providers}
   */
  static function github($redirect) {
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    return new OAuth2Providers('github', OAuth2Providers::loadProvider("github", $redirect));
  }

  /**
   * Load the {@link ProviderInterface} with the given key, from {@link #getProviders()}.
   *
   * @param $redirect the `redirectUri` to provide the provider.
   * @return A {@link ProviderInterface}
   */
  static function loadProvider($key, $redirect) {
    if (!$redirect) {
      throw new \InvalidArgumentException("No redirect provided.");
    }

    switch ($key) {
      case "google":
        return new GoogleWithOpenID(array(
          'clientId' =>\Openclerk\Config::get("oauth2_google_client_id"),
          'clientSecret' => \Openclerk\Config::get("oauth2_google_client_secret"),
          'redirectUri' => $redirect,
          'scopes' => array('email', 'openid'),
          'openid.realm' => \Openclerk\Config::get('openid_host'),
        ));

      case "facebook":
        return new \League\OAuth2\Client\Provider\Facebook(array(
          'clientId' =>\Openclerk\Config::get("oauth2_facebook_app_id"),
          'clientSecret' => \Openclerk\Config::get("oauth2_facebook_app_secret"),
          'redirectUri' => $redirect,
          'scopes' => array('email'),
        ));

      case "github":
        return new \League\OAuth2\Client\Provider\Github(array(
          'clientId' =>\Openclerk\Config::get("oauth2_github_client_id"),
          'clientSecret' => \Openclerk\Config::get("oauth2_github_client_secret"),
          'redirectUri' => $redirect,
          'scopes' => array('email'),
        ));

      default:
        throw new UserAuthenticationException("No such known OAuth2 provider '$key'");
    }
  }

  /**
   * Allows instances to be created based on {@link #getProviders()}.
   */
  static function createProvider($key, $redirect) {
    switch ($key) {
      case "google":
        return self::google($redirect);
      case "facebook":
        return self::facebook($redirect);
      case "github":
        return self::github($redirect);

      break;
        throw new UserAuthenticationException("No such known OAuth2 provider '$key'");
    }
  }

  /**
   * Get a list of all the provider keys supported by this component.
   * @see #createProvider()
   */
  static function getProviders() {
    return array(
      "google",
      "facebook",
      "github",
    );
  }

}
