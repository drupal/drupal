<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\Language\Language;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix or domain.
 *
 * @Plugin(
 *   id = \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::METHOD_ID,
 *   types = {\Drupal\Core\Language\Language::TYPE_INTERFACE, \Drupal\Core\Language\Language::TYPE_CONTENT, \Drupal\Core\Language\Language::TYPE_URL},
 *   weight = -8,
 *   name = @Translation("URL"),
 *   description = @Translation("Language from the URL (Path prefix or domain)."),
 *   config_path = "admin/config/regional/language/detection/url"
 * )
 */
class LanguageNegotiationUrl extends LanguageNegotiationMethodBase implements InboundPathProcessorInterface, OutboundPathProcessorInterface, LanguageSwitcherInterface {

  /**
   * The language negotiation method id.
   */
  const METHOD_ID = 'language-url';

  /**
   * URL language negotiation: use the path prefix as URL language indicator.
   */
  const CONFIG_PATH_PREFIX = 'path_prefix';

  /**
   * URL language negotiation: use the domain as URL language indicator.
   */
  const CONFIG_DOMAIN = 'domain';

  /**
   * {@inheritdoc}
   */
  public function getLangcode(Request $request = NULL) {
    $langcode = NULL;

    if ($request && $this->languageManager) {
      $languages = $this->languageManager->getLanguages();
      $config = $this->config->get('language.negotiation')->get('url');

      switch ($config['source']) {
        case LanguageNegotiationUrl::CONFIG_PATH_PREFIX:
          $request_path = urldecode(trim($request->getPathInfo(), '/'));
          $path_args = explode('/', $request_path);
          $prefix = array_shift($path_args);

          // Search prefix within added languages.
          $negotiated_language = FALSE;
          foreach ($languages as $language) {
            if (isset($config['prefixes'][$language->id]) && $config['prefixes'][$language->id] == $prefix) {
              $negotiated_language = $language;
              break;
            }
          }

          if ($negotiated_language) {
            $langcode = $negotiated_language->id;
          }
          break;

        case LanguageNegotiationUrl::CONFIG_DOMAIN:
          // Get only the host, not the port.
          $http_host = $request->getHost();
          foreach ($languages as $language) {
            // Skip the check if the language doesn't have a domain.
            if (!empty($config['domains'][$language->id])) {
              // Ensure that there is exactly one protocol in the URL when
              // checking the hostname.
              $host = 'http://' . str_replace(array('http://', 'https://'), '', $config['domains'][$language->id]);
              $host = parse_url($host, PHP_URL_HOST);
              if ($http_host == $host) {
                $langcode = $language->id;
                break;
              }
            }
          }
          break;
      }
    }

    return $langcode;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processInbound().
   */
  public function processInbound($path, Request $request) {
    $config = $this->config->get('language.negotiation')->get('url');
    $parts = explode('/', $path);
    $prefix = array_shift($parts);

    // Search prefix within added languages.
    foreach ($this->languageManager->getLanguages() as $language) {
      if (isset($config['prefixes'][$language->id]) && $config['prefixes'][$language->id] == $prefix) {
        // Rebuild $path with the language removed.
        $path = implode('/', $parts);
        break;
      }
    }

    return $path;
  }

  /**
   * Implements Drupal\Core\PathProcessor\InboundPathProcessorInterface::processOutbound().
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL) {
    $url_scheme = 'http';
    $port = 80;
    if ($request) {
      $url_scheme = $request->getScheme();
      $port = $request->getPort();
    }
    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager->getCurrentLanguage(Language::TYPE_URL);
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->id])) {
      return $path;
    }
    $config = $this->config->get('language.negotiation')->get('url');
    if ($config['source'] == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      if (is_object($options['language']) && !empty($config['prefixes'][$options['language']->id])) {
        return empty($path) ? $config['prefixes'][$options['language']->id] : $config['prefixes'][$options['language']->id] . '/' . $path;
      }
    }
    elseif ($config['source'] ==  LanguageNegotiationUrl::CONFIG_DOMAIN) {
      if (is_object($options['language']) && !empty($config['domains'][$options['language']->id])) {

        // Save the original base URL. If it contains a port, we need to
        // retain it below.
        if (!empty($options['base_url'])) {
          // The colon in the URL scheme messes up the port checking below.
          $normalized_base_url = str_replace(array('https://', 'http://'), '', $options['base_url']);
        }

        // Ask for an absolute URL with our modified base URL.
        $options['absolute'] = TRUE;
        $options['base_url'] = $url_scheme . '://' . $config['domains'][$options['language']->id];

        // In case either the original base URL or the HTTP host contains a
        // port, retain it.
        if (isset($normalized_base_url) && strpos($normalized_base_url, ':') !== FALSE) {
          list(, $port) = explode(':', $normalized_base_url);
          $options['base_url'] .= ':' . $port;
        }
        elseif (($url_scheme == 'http' && $port != 80) || ($url_scheme == 'https' && $port != 443)) {
          $options['base_url'] .= ':' . $port;
        }

        if (isset($options['https']) && !empty($options['mixed_mode_sessions'])) {
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

  /**
   * {@inheritdoc}
   */
  function getLanguageSwitchLinks(Request $request, $type, $path) {
    $links = array();

    foreach ($this->languageManager->getLanguages() as $language) {
      $links[$language->id] = array(
        'href' => $path,
        'title' => $language->name,
        'language' => $language,
        'attributes' => array('class' => array('language-link')),
      );
    }

    return $links;
  }

}
