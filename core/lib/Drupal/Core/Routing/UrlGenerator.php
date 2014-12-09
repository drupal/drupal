<?php

/**
 * @file
 * Contains Drupal\Core\Routing\UrlGenerator.
 */

namespace Drupal\Core\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

use Symfony\Cmf\Component\Routing\ProviderBasedGenerator;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;

/**
 * Generates URLs from route names and parameters.
 */
class UrlGenerator extends ProviderBasedGenerator implements UrlGeneratorInterface {

  /**
   * A request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The path processor to convert the system path to one suitable for urls.
   *
   * @var \Drupal\Core\PathProcessor\OutboundPathProcessorInterface
   */
  protected $pathProcessor;

  /**
   * The route processor.
   *
   * @var \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface
   */
  protected $routeProcessor;

  /**
   * Overrides characters that will not be percent-encoded in the path segment.
   *
   * @see \Symfony\Component\Routing\Generator\UrlGenerator
   */
  protected $decodedChars = array(
    // the slash can be used to designate a hierarchical structure and we want allow using it with this meaning
    // some webservers don't allow the slash in encoded form in the path for security reasons anyway
    // see http://stackoverflow.com/questions/4069002/http-400-if-2f-part-of-get-url-in-jboss
    '%2F' => '/',
  );

  /**
   *  Constructs a new generator object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $provider
   *   The route provider to be searched for routes.
   * @param \Drupal\Core\PathProcessor\OutboundPathProcessorInterface $path_processor
   *   The path processor to convert the system path to one suitable for urls.
   * @param \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface $route_processor
   *   The route processor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *    The config factory.
   * @param \Psr\Log\LoggerInterface $logger
   *   An optional logger for recording errors.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   */
  public function __construct(RouteProviderInterface $provider, OutboundPathProcessorInterface $path_processor, OutboundRouteProcessorInterface $route_processor, ConfigFactoryInterface $config, LoggerInterface $logger = NULL, RequestStack $request_stack) {
    parent::__construct($provider, $logger);

    $this->pathProcessor = $path_processor;
    $this->routeProcessor = $route_processor;
    $allowed_protocols = $config->get('system.filter')->get('protocols') ?: array('http', 'https');
    UrlHelper::setAllowedProtocols($allowed_protocols);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute($name, $parameters = array()) {
    $route = $this->getRoute($name);
    $this->processRoute($name, $route, $parameters);
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
    $options['absolute'] = $absolute;
    return $this->generateFromRoute($name, $parameters, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromRoute($name, $parameters = array(), $options = array()) {
    $options += array('prefix' => '');
    $route = $this->getRoute($name);
    $this->processRoute($name, $route, $parameters);

    // Symfony adds any parameters that are not path slugs as query strings.
    if (isset($options['query']) && is_array($options['query'])) {
      $parameters = (array) $parameters + $options['query'];
    }

    $path = $this->getInternalPathFromRoute($route, $parameters);
    $path = $this->processPath($path, $options);

    if (!empty($options['prefix'])) {
      $path = ltrim($path, '/');
      $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];
      $path = '/' . str_replace('%2F', '/', rawurlencode($prefix)) . $path;
    }

    $fragment = '';
    if (isset($options['fragment'])) {
      if (($fragment = trim($options['fragment'])) != '') {
        $fragment = '#' . $fragment;
      }
    }

    // The base_url might be rewritten from the language rewrite in domain mode.
    if (isset($options['base_url'])) {
      $base_url = $options['base_url'];

      if (isset($options['https'])) {
        if ($options['https'] === TRUE) {
          $base_url = str_replace('http://', 'https://', $base_url);
        }
        elseif ($options['https'] === FALSE) {
          $base_url = str_replace('https://', 'http://', $base_url);
        }
      }

      return $base_url . $path . $fragment;
    }

    $base_url = $this->context->getBaseUrl();

    $absolute = !empty($options['absolute']);
    if (!$absolute || !$host = $this->context->getHost()) {
      return $base_url . $path . $fragment;
    }

    // Prepare an absolute URL by getting the correct scheme, host and port from
    // the request context.
    if (isset($options['https'])) {
      $scheme = $options['https'] ? 'https' : 'http';
    }
    else {
      $scheme = $this->context->getScheme();
    }
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
    return $scheme . '://' . $host . $port . $base_url . $path . $fragment;
  }

  /**
   * {@inheritdoc}
   */
  public function generateFromPath($path = NULL, $options = array()) {
    $request = $this->requestStack->getCurrentRequest();
    $current_base_path = $request->getBasePath() . '/';
    $current_base_url = $request->getSchemeAndHttpHost() . $current_base_path;
    $current_script_path = '';
    $base_path_with_script = $request->getBaseUrl();
    if (!empty($base_path_with_script)) {
      $script_name = $request->getScriptName();
      if (strpos($base_path_with_script, $script_name) !== FALSE) {
        $current_script_path = ltrim(substr($script_name, strlen($current_base_path)), '/') . '/';
      }
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
      // call the slow
      // \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() if $path
      // contains a ':' before any / ? or #. Note: we could use
      // \Drupal\Component\Utility\UrlHelper::isExternal($path) here, but that
      // would require another function call, and performance inside _url() is
      // critical.
      $colonpos = strpos($path, ':');
      $options['external'] = ($colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && UrlHelper::stripDangerousProtocols($path) == $path);
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
        $path .= (strpos($path, '?') !== FALSE ? '&' : '?') . UrlHelper::buildQuery($options['query']);
      }
      if (isset($options['https'])) {
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
      $options['script'] = $current_script_path;
    }
    // The base_url might be rewritten from the language rewrite in domain mode.
    if (!isset($options['base_url'])) {
      if (isset($options['https'])) {
        if ($options['https'] === TRUE) {
          $options['base_url'] = str_replace('http://', 'https://', $current_base_url);
          $options['absolute'] = TRUE;
        }
        elseif ($options['https'] === FALSE) {
          $options['base_url'] = str_replace('https://', 'http://', $current_base_url);
          $options['absolute'] = TRUE;
        }
      }
      else {
        $options['base_url'] = $current_base_url;
      }
    }
    elseif (rtrim($options['base_url'], '/') == $options['base_url']) {
      $options['base_url'] .= '/';
    }
    $base = $options['absolute'] ? $options['base_url'] : $current_base_path;
    $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];

    $path = str_replace('%2F', '/', rawurlencode($prefix . $path));
    $query = $options['query'] ? ('?' . UrlHelper::buildQuery($options['query'])) : '';
    return $base . $options['script'] . $path . $query . $options['fragment'];
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
    $path = '/' . $this->pathProcessor->processOutbound(trim($actual_path, '/'), $options, $this->requestStack->getCurrentRequest());
    $path .= $query_string;
    return $path;
  }

  /**
   * Passes the route to the processor manager for altering before compilation.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route object to process.
   * @param array $parameters
   *   An array of parameters to be passed to the route compiler.
   * @param string $name
   *   The route name.
   */
  protected function processRoute($name, SymfonyRoute $route, array &$parameters) {
    $this->routeProcessor->processOutbound($name, $route, $parameters);
  }

  /**
   * Find the route using the provided route name.
   *
   * @param string $name
   *   The route name to fetch
   *
   * @return \Symfony\Component\Routing\Route
   *   The found route.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown if there is no route with that name in this repository.
   *
   * @see \Drupal\Core\Routing\RouteProviderInterface
   */
  protected function getRoute($name) {
    if ($name instanceof SymfonyRoute) {
      $route = $name;
    }
    elseif (NULL === $route = clone $this->provider->getRouteByName($name)) {
      throw new RouteNotFoundException(sprintf('Route "%s" does not exist.', $name));
    }
    return $route;
  }

}
