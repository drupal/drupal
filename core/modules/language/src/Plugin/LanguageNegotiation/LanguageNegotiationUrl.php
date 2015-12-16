<?php

/**
 * @file
 * Contains \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl.
 */

namespace Drupal\language\Plugin\LanguageNegotiation;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\language\LanguageNegotiationMethodBase;
use Drupal\language\LanguageSwitcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for identifying language via URL prefix or domain.
 *
 * @LanguageNegotiation(
 *   id = \Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl::METHOD_ID,
 *   types = {\Drupal\Core\Language\LanguageInterface::TYPE_INTERFACE,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_CONTENT,
 *   \Drupal\Core\Language\LanguageInterface::TYPE_URL},
 *   weight = -8,
 *   name = @Translation("URL"),
 *   description = @Translation("Language from the URL (Path prefix or domain)."),
 *   config_route_name = "language.negotiation_url"
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
            if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
              $negotiated_language = $language;
              break;
            }
          }

          if ($negotiated_language) {
            $langcode = $negotiated_language->getId();
          }
          break;

        case LanguageNegotiationUrl::CONFIG_DOMAIN:
          // Get only the host, not the port.
          $http_host = $request->getHost();
          foreach ($languages as $language) {
            // Skip the check if the language doesn't have a domain.
            if (!empty($config['domains'][$language->getId()])) {
              // Ensure that there is exactly one protocol in the URL when
              // checking the hostname.
              $host = 'http://' . str_replace(array('http://', 'https://'), '', $config['domains'][$language->getId()]);
              $host = parse_url($host, PHP_URL_HOST);
              if ($http_host == $host) {
                $langcode = $language->getId();
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
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    $config = $this->config->get('language.negotiation')->get('url');
    $parts = explode('/', trim($path, '/'));
    $prefix = array_shift($parts);

    // Search prefix within added languages.
    foreach ($this->languageManager->getLanguages() as $language) {
      if (isset($config['prefixes'][$language->getId()]) && $config['prefixes'][$language->getId()] == $prefix) {
        // Rebuild $path with the language removed.
        $path = '/' . implode('/', $parts);
        break;
      }
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $url_scheme = 'http';
    $port = 80;
    if ($request) {
      $url_scheme = $request->getScheme();
      $port = $request->getPort();
    }
    $languages = array_flip(array_keys($this->languageManager->getLanguages()));
    // Language can be passed as an option, or we go for current URL language.
    if (!isset($options['language'])) {
      $language_url = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_URL);
      $options['language'] = $language_url;
    }
    // We allow only added languages here.
    elseif (!is_object($options['language']) || !isset($languages[$options['language']->getId()])) {
      return $path;
    }
    $config = $this->config->get('language.negotiation')->get('url');
    if ($config['source'] == LanguageNegotiationUrl::CONFIG_PATH_PREFIX) {
      if (is_object($options['language']) && !empty($config['prefixes'][$options['language']->getId()])) {
        $options['prefix'] = $config['prefixes'][$options['language']->getId()] . '/';
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL]);
        }
      }
    }
    elseif ($config['source'] ==  LanguageNegotiationUrl::CONFIG_DOMAIN) {
      if (is_object($options['language']) && !empty($config['domains'][$options['language']->getId()])) {

        // Save the original base URL. If it contains a port, we need to
        // retain it below.
        if (!empty($options['base_url'])) {
          // The colon in the URL scheme messes up the port checking below.
          $normalized_base_url = str_replace(array('https://', 'http://'), '', $options['base_url']);
        }

        // Ask for an absolute URL with our modified base URL.
        $options['absolute'] = TRUE;
        $options['base_url'] = $url_scheme . '://' . $config['domains'][$options['language']->getId()];

        // In case either the original base URL or the HTTP host contains a
        // port, retain it.
        if (isset($normalized_base_url) && strpos($normalized_base_url, ':') !== FALSE) {
          list(, $port) = explode(':', $normalized_base_url);
          $options['base_url'] .= ':' . $port;
        }
        elseif (($url_scheme == 'http' && $port != 80) || ($url_scheme == 'https' && $port != 443)) {
          $options['base_url'] .= ':' . $port;
        }

        if (isset($options['https'])) {
          if ($options['https'] === TRUE) {
            $options['base_url'] = str_replace('http://', 'https://', $options['base_url']);
          }
          elseif ($options['https'] === FALSE) {
            $options['base_url'] = str_replace('https://', 'http://', $options['base_url']);
          }
        }

        // Add Drupal's subfolder from the base_path if there is one.
        $options['base_url'] .= rtrim(base_path(), '/');
        if ($bubbleable_metadata) {
          $bubbleable_metadata->addCacheContexts(['languages:' . LanguageInterface::TYPE_URL, 'url.site']);
        }
      }
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function getLanguageSwitchLinks(Request $request, $type, Url $url) {
    $links = array();

    foreach ($this->languageManager->getNativeLanguages() as $language) {
      $links[$language->getId()] = array(
        // We need to clone the $url object to avoid using the same one for all
        // links. When the links are rendered, options are set on the $url
        // object, so if we use the same one, they would be set for all links.
        'url' => clone $url,
        'title' => $language->getName(),
        'language' => $language,
        'attributes' => array('class' => array('language-link')),
      );
    }

    return $links;
  }

}
