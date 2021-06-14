<?php

namespace Drupal\media\OEmbed;

use Drupal\Component\Utility\UrlHelper;

/**
 * Value object for oEmbed provider endpoints.
 *
 * @internal
 *   This class is an internal part of the oEmbed system and should only be
 *   instantiated by instances of Drupal\media\OEmbed\Provider.
 */
class Endpoint {

  /**
   * The endpoint's URL.
   *
   * @var string
   */
  protected $url;

  /**
   * The provider this endpoint belongs to.
   *
   * @var \Drupal\media\OEmbed\Provider
   */
  protected $provider;

  /**
   * List of URL schemes supported by the provider.
   *
   * @var string[]
   */
  protected $schemes;

  /**
   * List of supported formats. Only 'json' and 'xml' are allowed.
   *
   * @var string[]
   *
   * @see https://oembed.com/#section2
   */
  protected $formats;

  /**
   * Whether the provider supports oEmbed discovery.
   *
   * @var bool
   */
  protected $supportsDiscovery;

  /**
   * Endpoint constructor.
   *
   * @param string $url
   *   The endpoint URL. May contain a @code '{format}' @endcode placeholder.
   * @param \Drupal\media\OEmbed\Provider $provider
   *   The provider this endpoint belongs to.
   * @param string[] $schemes
   *   List of URL schemes supported by the provider.
   * @param string[] $formats
   *   List of supported formats. Can be "json", "xml" or both.
   * @param bool $supports_discovery
   *   Whether the provider supports oEmbed discovery.
   *
   * @throws \InvalidArgumentException
   *   If the endpoint URL is empty.
   */
  public function __construct($url, Provider $provider, array $schemes = [], array $formats = [], $supports_discovery = FALSE) {
    $this->provider = $provider;
    $this->schemes = array_map('mb_strtolower', $schemes);

    $this->formats = $formats = array_map('mb_strtolower', $formats);
    // Assert that only the supported formats are present.
    assert(array_diff($formats, ['json', 'xml']) == []);

    // Use the first provided format to build the endpoint URL. If no formats
    // are provided, default to JSON.
    $this->url = str_replace('{format}', reset($this->formats) ?: 'json', $url);

    if (!UrlHelper::isValid($this->url, TRUE) || !UrlHelper::isExternal($this->url)) {
      throw new \InvalidArgumentException('oEmbed endpoint must have a valid external URL');
    }

    $this->supportsDiscovery = (bool) $supports_discovery;
  }

  /**
   * Returns the endpoint URL.
   *
   * The URL will be built with the first available format. If the endpoint
   * does not provide any formats, JSON will be used.
   *
   * @return string
   *   The endpoint URL.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Returns the provider this endpoint belongs to.
   *
   * @return \Drupal\media\OEmbed\Provider
   *   The provider object.
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Returns list of URL schemes supported by the provider.
   *
   * @return string[]
   *   List of schemes.
   */
  public function getSchemes() {
    return $this->schemes;
  }

  /**
   * Returns list of supported formats.
   *
   * @return string[]
   *   List of formats.
   */
  public function getFormats() {
    return $this->formats;
  }

  /**
   * Returns whether the provider supports oEmbed discovery.
   *
   * @return bool
   *   Returns TRUE if the provides discovery, otherwise FALSE.
   */
  public function supportsDiscovery() {
    return $this->supportsDiscovery;
  }

  /**
   * Tries to match a URL against the endpoint schemes.
   *
   * @param string $url
   *   Media item URL.
   *
   * @return bool
   *   TRUE if the URL matches against the endpoint schemes, otherwise FALSE.
   */
  public function matchUrl($url) {
    foreach ($this->getSchemes() as $scheme) {
      // Convert scheme into a valid regular expression.
      $regexp = str_replace(['.', '*'], ['\.', '.*'], $scheme);
      if (preg_match("|^$regexp$|", $url)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Builds and returns the endpoint URL.
   *
   * In most situations this function should not be used. Your are probably
   * looking for \Drupal\media\OEmbed\UrlResolver::getResourceUrl(), because it
   * is alterable and also cached.
   *
   * @param string $url
   *   The canonical media URL.
   *
   * @return string
   *   URL of the oEmbed endpoint.
   *
   * @see \Drupal\media\OEmbed\UrlResolver::getResourceUrl()
   */
  public function buildResourceUrl($url) {
    $query = ['url' => $url];
    return $this->getUrl() . '?' . UrlHelper::buildQuery($query);
  }

}
