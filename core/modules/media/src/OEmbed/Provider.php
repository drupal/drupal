<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Utility\UrlHelper;

/**
 * Value object for oEmbed providers.
 */
class Provider {

  /**
   * The provider name.
   *
   * @var string
   */
  protected $name;

  /**
   * The provider URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The provider endpoints.
   *
   * @var \Drupal\media\OEmbed\Endpoint[]
   */
  protected $endpoints = [];

  /**
   * Provider constructor.
   *
   * @param string $name
   *   The provider name.
   * @param string $url
   *   The provider URL.
   * @param array[] $endpoints
   *   List of endpoints this provider exposes.
   *
   * @throws \Drupal\media\OEmbed\ProviderException
   */
  public function __construct($name, $url, array $endpoints) {
    if (!UrlHelper::isValid($url, TRUE) || !UrlHelper::isExternal($url)) {
      throw new ProviderException('Provider @name does not define a valid external URL.', $this);
    }

    $this->name = $name;
    $this->url = $url;

    try {
      foreach ($endpoints as $endpoint) {
        $endpoint += ['formats' => [], 'schemes' => [], 'discovery' => FALSE];
        $this->endpoints[] = new Endpoint($endpoint['url'], $this, $endpoint['schemes'], $endpoint['formats'], $endpoint['discovery']);
      }
    }
    catch (\InvalidArgumentException $e) {
      // Just skip all the invalid endpoints.
      // @todo Log the exception message to help with debugging in
      // https://www.drupal.org/project/drupal/issues/2972846.
    }

    if (empty($this->endpoints)) {
      throw new ProviderException('Provider @name does not define any valid endpoints.', $this);
    }
  }

  /**
   * Returns the provider name.
   *
   * @return string
   *   Name of the provider.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the provider URL.
   *
   * @return string
   *   URL of the provider.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns the provider endpoints.
   *
   * @return \Drupal\media\OEmbed\Endpoint[]
   *   List of endpoints this provider exposes.
   */
  public function getEndpoints() {
    return $this->endpoints;
  }

}
