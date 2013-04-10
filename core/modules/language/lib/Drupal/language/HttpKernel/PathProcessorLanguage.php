<?php

/**
 * @file
 * Contains Drupal\language\HttpKernel\PathProcessorLanguage.
 */

namespace Drupal\language\HttpKernel;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorLanguage implements InboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * An array of enabled languages.
   *
   * @var array
   */
  protected $languages;

  /**
   * Constructs a PathProcessorLanguage object.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   A config factory object for retrieving configuration settings.
   *
   * @param array $languages
   *   An array of languages, keyed by language code, representing the languages
   *   currently enabled on the site.
   */
  public function __construct(ConfigFactory $config, array $languages = array()) {
    $this->config = $config;
    if (empty($languages)) {
      $languages = language_list();
    }
    $this->languages = $languages;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    if (!empty($path)) {
      $args = explode('/', $path);
      $prefix = array_shift($args);

      // Search prefix within enabled languages.
      $prefixes = $this->config->get('language.negotiation')->get('url.prefixes');
      foreach ($this->languages as $language) {
        if (isset($prefixes[$language->langcode]) && $prefixes[$language->langcode] == $prefix) {
          // Rebuild $path with the language removed.
          return implode('/', $args);
        }
      }
    }
    return $path;
  }

}
