<?php

/**
 * @file
 * Contains Drupal\Core\Utility\UnroutedUrlAssembler.
 */

namespace Drupal\Core\Utility;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a way to build external or non Drupal local domain URLs.
 *
 * It takes into account configured safe HTTP protocols.
 */
class UnroutedUrlAssembler implements UnroutedUrlAssemblerInterface {

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   *  Constructs a new unroutedUrlAssembler object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *    The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config) {
    $allowed_protocols = $config->get('system.filter')->get('protocols') ?: ['http', 'https'];
    UrlHelper::setAllowedProtocols($allowed_protocols);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   *
   * This is a helper function that calls buildExternalUrl() or buildLocalUrl()
   * based on a check of whether the path is a valid external URL.
   */
  public function assemble($uri, array $options = []) {
    // Note that UrlHelper::isExternal will return FALSE if the $uri has a
    // disallowed protocol.  This is later made safe since we always add at
    // least a leading slash.
    if (strpos($uri, 'base://') === 0) {
      return $this->buildLocalUrl($uri, $options);
    }
    elseif (UrlHelper::isExternal($uri)) {
      // UrlHelper::isExternal() only returns true for safe protocols.
      return $this->buildExternalUrl($uri, $options);
    }
    throw new \InvalidArgumentException('You must use a valid URI scheme.  Use base:// for a path e.g. to a Drupal file that needs the base path.');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildExternalUrl($uri, array $options = []) {
    $this->addOptionDefaults($options);
    // Split off the fragment.
    if (strpos($uri, '#') !== FALSE) {
      list($uri, $old_fragment) = explode('#', $uri, 2);
      // If $options contains no fragment, take it over from the path.
      if (isset($old_fragment) && !$options['fragment']) {
        $options['fragment'] = '#' . $old_fragment;
      }
    }

    if (isset($options['https'])) {
      if ($options['https'] === TRUE) {
        $uri = str_replace('http://', 'https://', $uri);
      }
      elseif ($options['https'] === FALSE) {
        $uri = str_replace('https://', 'http://', $uri);
      }
    }
    // Append the query.
    if ($options['query']) {
      $uri .= (strpos($uri, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($options['query']);
    }
    // Reassemble.
    return $uri . $options['fragment'];
  }

  /**
   * {@inheritdoc}
   */
  protected function buildLocalUrl($uri, array $options = []) {
    $this->addOptionDefaults($options);
    $request = $this->requestStack->getCurrentRequest();

    // Remove the base:// scheme.
    $uri = substr($uri, 7);
    // Add any subdirectory where Drupal is installed.
    $current_base_path = $request->getBasePath() . '/';

    if ($options['absolute']) {
      $current_base_url = $request->getSchemeAndHttpHost() . $current_base_path;
      if (isset($options['https'])) {
        if (!empty($options['https'])) {
          $base = str_replace('http://', 'https://', $current_base_url);
          $options['absolute'] = TRUE;
        }
        else {
          $base = str_replace('https://', 'http://', $current_base_url);
          $options['absolute'] = TRUE;
        }
      }
      else {
        $base = $current_base_url;
      }
    }
    else {
      $base = $current_base_path;
    }

    $prefix = empty($uri) ? rtrim($options['prefix'], '/') : $options['prefix'];

    $uri = str_replace('%2F', '/', rawurlencode($prefix . $uri));
    $query = $options['query'] ? ('?' . UrlHelper::buildQuery($options['query'])) : '';
    return $base . $options['script'] . $uri . $query . $options['fragment'];
  }

  /**
   * Merges in default defaults
   *
   * @param array $options
   *   The options to merge in the defaults.
   */
  protected function addOptionDefaults(array &$options) {
    // Merge in defaults.
    $options += [
      'fragment' => '',
      'query' => [],
      'absolute' => FALSE,
      'prefix' => '',
      'script' => '',
    ];

    if (isset($options['fragment']) && $options['fragment'] !== '') {
      $options['fragment'] = '#' . $options['fragment'];
    }
  }

}
