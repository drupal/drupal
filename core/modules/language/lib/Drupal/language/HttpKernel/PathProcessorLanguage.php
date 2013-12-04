<?php

/**
 * @file
 * Contains Drupal\language\HttpKernel\PathProcessorLanguage.
 */

namespace Drupal\language\HttpKernel;

use Drupal\Component\Utility\Settings;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the inbound path using path alias lookups.
 */
class PathProcessorLanguage implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Whether both secure and insecure session cookies can be used simultaneously.
   *
   * @var bool
   */
  protected $mixedModeSessions;

  /**
   * Language manager for retrieving the url language type.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected $languageManager;

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
  public function __construct(ConfigFactory $config, Settings $settings, LanguageManager $language_manager, array $languages = array()) {
    $this->config = $config;
    $this->mixedModeSessions = $settings->get('mixed_mode_sessions', FALSE);
    $this->languageManager = $language_manager;
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
        if (isset($prefixes[$language->id]) && $prefixes[$language->id] == $prefix) {
          // Rebuild $path with the language removed.
          return implode('/', $args);
        }
      }
    }
    return $path;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    if (!$this->languageManager->isMultilingual()) {
      return $path;
    }
    $url_scheme = 'http';
    $port = 80;
    if ($request) {
      $url_scheme = $request->getScheme();
      $port = $request->getPort();
    }
    $languages = array_flip(array_keys($this->languages));
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager->getLanguage(Language::TYPE_URL);
      $options['language'] = $language_url;
    }
    // We allow only enabled languages here.
    elseif (is_object($options['language']) && !isset($languages[$options['language']->id])) {
      return $path;
    }
    $url_source = $this->config->get('language.negotiation')->get('url.source');
    // @todo Go back to using a constant instead of the string 'path_prefix' once we can use a class
    //   constant.
    if ($url_source == 'path_prefix') {
      $prefixes = $this->config->get('language.negotiation')->get('url.prefixes');
      if (is_object($options['language']) && !empty($prefixes[$options['language']->id])) {
        return empty($path) ? $prefixes[$options['language']->id] : $prefixes[$options['language']->id] . '/' . $path;
      }
    }
    elseif ($url_source == 'domain') {
      $domains = $this->config->get('language.negotiation')->get('url.domains');
      if (is_object($options['language']) && !empty($domains[$options['language']->id])) {

        // Save the original base URL. If it contains a port, we need to
        // retain it below.
        if (!empty($options['base_url'])) {
          // The colon in the URL scheme messes up the port checking below.
          $normalized_base_url = str_replace(array('https://', 'http://'), '', $options['base_url']);
        }

        // Ask for an absolute URL with our modified base URL.
        $options['absolute'] = TRUE;
        $options['base_url'] = $url_scheme . '://' . $domains[$options['language']->id];

        // In case either the original base URL or the HTTP host contains a
        // port, retain it.
        if (isset($normalized_base_url) && strpos($normalized_base_url, ':') !== FALSE) {
          list( , $port) = explode(':', $normalized_base_url);
          $options['base_url'] .= ':' . $port;
        }
        elseif ($port != 80) {
          $options['base_url'] .= ':' . $port;
        }

        if (isset($options['https']) && $this->mixedModeSessions) {
          if ($options['https'] === TRUE) {
            $options['base_url'] = str_replace('http://', 'https://', $options['base_url']);
          }
          elseif ($options['https'] === FALSE) {
            $options['base_url'] = str_replace('https://', 'http://', $options['base_url']);
          }
        }

        // Add Drupal's subfolder from the base_path if there is one.
        $options['base_url'] .= rtrim(base_path(), '/');
      }
    }
    return $path;
  }

}
