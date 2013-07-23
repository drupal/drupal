<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlGenerator.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;

use Drupal\Component\Utility\Settings;
use Drupal\Component\Utility\Url;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;

/**
 * A Generator creates URL strings based on a specified route.
 */
class UrlGenerator extends ProviderBasedGenerator implements PathBasedGeneratorInterface {

  /**
   * A request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The path processor to convert the system path to one suitable for urls.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The base path to use for urls.
   *
   * @var string
   */
  protected $basePath;

  /**
   * The base url to use for urls.
   *
   * @var string
   */
  protected $baseUrl;

  /**
   * The script path to use for urls.
   *
   * @var string
   */
  protected $scriptPath;

  /**
   * Whether both secure and insecure session cookies can be used simultaneously.
   *
   * @var bool
   */
  protected $mixedModeSessions;

  /**
   *  Constructs a new generator object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $provider
   *   The route provider to be searched for routes.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager responsible for path aliasing.
   * @param \Symfony\Component\HttpKernel\Log\LoggerInterface $logger
   *   An optional logger for recording errors.
   */
  public function __construct(RouteProviderInterface $provider, OutboundPathProcessorInterface $path_processor, ConfigFactory $config, Settings $settings, LoggerInterface $logger = NULL) {
    parent::__construct($provider, $logger);

    $this->pathProcessor = $path_processor;
    $this->mixedModeSessions = $settings->get('mixed_mode_sessions', FALSE);
    $allowed_protocols = $config->get('system.filter')->get('protocols') ?: array('http', 'https');
    Url::setAllowedProtocols($allowed_protocols);
  }

  /**
   * Implements \Drupal\Core\Routing\PathBasedGeneratorInterface::setRequest().
   */
  public function setRequest(Request $request) {
    $this->request = $request;
    // Set some properties, based on the request, that are used during path-based
    // url generation.
    $this->basePath = $request->getBasePath() . '/';
    $this->baseUrl = $request->getSchemeAndHttpHost() . $this->basePath;
    $this->scriptPath = '';
    $base_path_with_script = $request->getBaseUrl();
    $script_name = $request->getScriptName();
    if (!empty($base_path_with_script) && strpos($base_path_with_script, $script_name) !== FALSE) {
      $length = strlen($this->basePath);
      $this->scriptPath = ltrim(substr($script_name, $length), '/') . '/';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute($name, $parameters = array()) {
    $route = $this->getRoute($name, $parameters);
    $path = $this->getInternalPathFromRoute($route, $parameters);
    // Router-based paths may have a querystring on them but Drupal paths may
    // not have one, so remove any ? and anything after it. For generate() this
    // is handled in processPath().
    $path = preg_replace('/\?.*/', '', $path);
    return trim($path, '/');
  }

  /**
   * Gets the path of a route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *  The route object.
   * @param array $parameters
   *  An array of parameters as passed to
   *  \Symfony\Component\Routing\Generator\UrlGeneratorInterface::generate().
   *
   * @return string
   *  The url path corresponding to the route, without the base path.
   */
  protected function getInternalPathFromRoute(SymfonyRoute $route, $parameters = array()) {
    // The Route has a cache of its own and is not recompiled as long as it does
    // not get modified.
    $compiledRoute = $route->compile();
    $hostTokens = $compiledRoute->getHostTokens();

    $route_requirements = $route->getRequirements();
    // We need to bypass the doGenerate() method's handling of absolute URLs as
    // we handle that ourselves after processing the path.
    if (isset($route_requirements['_scheme'])) {
      unset($route_requirements['_scheme']);
    }
    $path = $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $route_requirements, $compiledRoute->getTokens(), $parameters, $route->getPath(), FALSE, $hostTokens);

    // The URL returned from doGenerate() will include the base path if there is
    // one (i.e., if running in a subdirectory) so we need to strip that off
    // before processing the path.
    $base_url = $this->context->getBaseUrl();
    if (!empty($base_url) && strpos($path, $base_url) === 0) {
      $path = substr($path, strlen($base_url));
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function generate($name, $parameters = array(), $absolute = FALSE) {
    $route = $this->getRoute($name, $parameters);
    $path = $this->getInternalPathFromRoute($route, $parameters);
    $path = $this->processPath($path);

    $base_url = $this->context->getBaseUrl();
    if (!$absolute || !$host = $this->context->getHost()) {
      return $base_url . $path;
    }

    // Prepare an absolute URL by getting the correct scheme, host and port from
    // the request context.
    $scheme = $this->context->getScheme();
    $scheme_req = $route->getRequirement('_scheme');
    if (isset($scheme_req) && ($req = strtolower($scheme_req)) && $scheme !== $req) {
      $scheme = $req;
    }
    $port = '';
    if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
      $port = ':' . $this->context->getHttpPort();
    } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
      $port = ':' . $this->context->getHttpsPort();
    }
    return $scheme . '://' . $host . $port . $base_url . $path;
  }

  /**
   * Implements \Drupal\Core\Routing\PathBasedGeneratorInterface::generateFromPath().
   *
   * @param $path
   *   (optional) The internal path or external URL being linked to, such as
   *   "node/34" or "http://example.com/foo". The default value is equivalent to
   *   passing in '<front>'. A few notes:
   *   - If you provide a full URL, it will be considered an external URL.
   *   - If you provide only the path (e.g. "node/34"), it will be
   *     considered an internal link. In this case, it should be a system URL,
   *     and it will be replaced with the alias, if one exists. Additional query
   *     arguments for internal paths must be supplied in $options['query'], not
   *     included in $path.
   *   - If you provide an internal path and $options['alias'] is set to TRUE, the
   *     path is assumed already to be the correct path alias, and the alias is
   *     not looked up.
   *   - The special string '<front>' generates a link to the site's base URL.
   *   - If your external URL contains a query (e.g. http://example.com/foo?a=b),
   *     then you can either URL encode the query keys and values yourself and
   *     include them in $path, or use $options['query'] to let this method
   *     URL encode them.
   *
   * @param $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding) to
   *     append to the URL.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'alias': Defaults to FALSE. Whether the given path is a URL alias
   *     already.
   *   - 'external': Whether the given path is an external URL.
   *   - 'language': An optional language object. If the path being linked to is
   *     internal to the site, $options['language'] is used to look up the alias
   *     for the URL. If $options['language'] is omitted, the language will be
   *     obtained from language(Language::TYPE_URL).
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP, but HTTPS can
   *     only be enforced when the variable 'https' is set to TRUE.
   *   - 'base_url': Only used internally, to modify the base URL when a language
   *     dependent URL requires so.
   *   - 'prefix': Only used internally, to modify the path when a language
   *     dependent URL requires so.
   *   - 'script': Added to the URL between the base path and the path prefix.
   *     Defaults to empty string when clean URLs are in effect, and to
   *     'index.php/' when they are not.
   *   - 'entity_type': The entity type of the object that called url(). Only
   *     set if url() is invoked by Drupal\Core\Entity\Entity::uri().
   *   - 'entity': The entity object (such as a node) for which the URL is being
   *     generated. Only set if url() is invoked by Drupal\Core\Entity\Entity::uri().
   *
   * @return
   *   A string containing a URL to the given path.
   *
   * @throws \Drupal\Core\Routing\GeneratorNotInitializedException.
   */
  public function generateFromPath($path = NULL, $options = array()) {

    if (!$this->initialized()) {
      throw new GeneratorNotInitializedException();
    }

    // Merge in defaults.
    $options += array(
      'fragment' => '',
      'query' => array(),
      'absolute' => FALSE,
      'prefix' => '',
    );

    if (!isset($options['external'])) {
      // Return an external link if $path contains an allowed absolute URL. Only
      // call the slow drupal_strip_dangerous_protocols() if $path contains a ':'
      // before any / ? or #. Note: we could use url_is_external($path) here, but
      // that would require another function call, and performance inside url() is
      // critical.
      $colonpos = strpos($path, ':');
      $options['external'] = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && Url::stripDangerousProtocols($path) == $path);
    }

    if (isset($options['fragment']) && $options['fragment'] !== '') {
      $options['fragment'] = '#' . $options['fragment'];
    }

    if ($options['external']) {
      // Split off the fragment.
      if (strpos($path, '#') !== FALSE) {
        list($path, $old_fragment) = explode('#', $path, 2);
        // If $options contains no fragment, take it over from the path.
        if (isset($old_fragment) && !$options['fragment']) {
          $options['fragment'] = '#' . $old_fragment;
        }
      }
      // Append the query.
      if ($options['query']) {
        $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . Url::buildQuery($options['query']);
      }
      if (isset($options['https']) && $this->mixedModeSessions) {
        if ($options['https'] === TRUE) {
          $path = str_replace('http://', 'https://', $path);
        }
        elseif ($options['https'] === FALSE) {
          $path = str_replace('https://', 'http://', $path);
        }
      }
      // Reassemble.
      return $path . $options['fragment'];
    }
    else {
      $path = ltrim($this->processPath($path, $options), '/');
    }

    if (!isset($options['script'])) {
      $options['script'] = $this->scriptPath;
    }
    // The base_url might be rewritten from the language rewrite in domain mode.
    if (!isset($options['base_url'])) {
      if (isset($options['https']) && $this->mixedModeSessions) {
        if ($options['https'] === TRUE) {
          $options['base_url'] = str_replace('http://', 'https://', $this->baseUrl);
          $options['absolute'] = TRUE;
        }
        elseif ($options['https'] === FALSE) {
          $options['base_url'] = str_replace('https://', 'http://', $this->baseUrl);
          $options['absolute'] = TRUE;
        }
      }
      else {
        $options['base_url'] = $this->baseUrl;
      }
    }
    elseif (rtrim($options['base_url'], '/') == $options['base_url']) {
      $options['base_url'] .= '/';
    }
    $base = $options['absolute'] ? $options['base_url'] : $this->basePath;
    $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];

    $path = str_replace('%2F', '/', rawurlencode($prefix . $path));
    $query = $options['query'] ? ('?' . Url::buildQuery($options['query'])) : '';
    return $base . $options['script'] . $path . $query . $options['fragment'];
  }

  /**
   * Implements \Drupal\Core\Routing\PathBasedGeneratorInterface::setBaseUrl().
   */
  public function setBaseUrl($url) {
    $this->baseUrl = $url;
  }

  /**
   * Implements \Drupal\Core\Routing\PathBasedGeneratorInterface::setBasePath().
   */
  public function setBasePath($path) {
    $this->basePath = $path;
  }

  /**
   * Implements \Drupal\Core\Routing\PathBasedGeneratorInterface::setScriptPath().
   */
  public function setScriptPath($path) {
    $this->scriptPath = $path;
  }

  /**
   * Passes the path to a processor manager to allow alterations.
   */
  protected function processPath($path, &$options = array()) {
    // Router-based paths may have a querystring on them.
    if ($query_pos = strpos($path, '?')) {
      // We don't need to do a strict check here because position 0 would mean we
      // have no actual path to work with.
      $actual_path = substr($path, 0, $query_pos);
      $query_string = substr($path, $query_pos);
    }
    else {
      $actual_path = $path;
      $query_string = '';
    }
    $path = '/' . $this->pathProcessor->processOutbound(trim($actual_path, '/'), $options, $this->request);
    $path .= $query_string;
    return $path;
  }

  /**
   * Returns whether or not the url generator has been initialized.
   *
   * @return bool
   *   Returns TRUE if the basePath, baseUrl and scriptPath properties have been
   *   set, FALSE otherwise.
   */
  protected function initialized() {
    return isset($this->basePath) && isset($this->baseUrl) && isset($this->scriptPath);
  }

  /**
   * Find the route using the provided route name (and parameters).
   *
   * @param string $name
   *   The route name to fetch
   * @param array $parameters
   *   The parameters as they are passed to the UrlGeneratorInterface::generate
   *   call.
   *
   * @return \Symfony\Component\Routing\Route
   *   The found route.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown if there is no route with that name in this repository.
   *
   * @see \Drupal\Core\Routing\RouteProviderInterface
   */
  protected function getRoute($name, $parameters) {
    if ($name instanceof SymfonyRoute) {
      $route = $name;
    }
    elseif (NULL === $route = $this->provider->getRouteByName($name, $parameters)) {
      throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
    }
    return $route;
  }

}
