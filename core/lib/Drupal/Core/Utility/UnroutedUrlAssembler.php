<?php

/**
 * @file
 * Contains Drupal\Core\Utility\UnroutedUrlAssembler.
 */

namespace Drupal\Core\Utility;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
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
   * The outbound path processor.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   *  Constructs a new unroutedUrlAssembler object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *    The config factory.
   * @param \Drupal\Core\PathProcessor\OutboundPathProcessorInterface $path_processor
   *   The output path processor.
   */
  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config, OutboundPathProcessorInterface $path_processor) {
    $allowed_protocols = $config->get('system.filter')->get('protocols') ?: ['http', 'https'];
    UrlHelper::setAllowedProtocols($allowed_protocols);
    $this->requestStack = $request_stack;
    $this->pathProcessor = $path_processor;
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
    throw new \InvalidArgumentException(String::format('The URI "@uri" is invalid. You must use a valid URI scheme. Use base:// for a path, e.g., to a Drupal file that needs the base path. Do not use this for internal paths controlled by Drupal.', ['@uri' => $uri]));
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

    // Allow (outbound) path processing, if needed. A valid use case is the path
    // alias overview form:
    // @see \Drupal\path\Controller\PathController::adminOverview().
    if (!empty($options['path_processing'])) {
      $uri = $this->pathProcessor->processOutbound($uri, $options);
    }

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
    $request = $this->requestStack->getCurrentRequest();
    $current_base_path = $request->getBasePath() . '/';
    $current_script_path = '';
    $base_path_with_script = $request->getBaseUrl();

    // If the current request was made with the script name (eg, index.php) in
    // it, then extract it, making sure the leading / is gone, and a trailing /
    // is added, to allow simple string concatenation with other parts.  This
    // mirrors code from UrlGenerator::generateFromPath().
    if (!empty($base_path_with_script)) {
      $script_name = $request->getScriptName();
      if (strpos($base_path_with_script, $script_name) !== FALSE) {
        $current_script_path = ltrim(substr($script_name, strlen($current_base_path)), '/') . '/';
      }
    }

    // Merge in defaults.
    $options += [
      'fragment' => '',
      'query' => [],
      'absolute' => FALSE,
      'prefix' => '',
      'script' => $current_script_path,
    ];

    if (isset($options['fragment']) && $options['fragment'] !== '') {
      $options['fragment'] = '#' . $options['fragment'];
    }
  }

}
