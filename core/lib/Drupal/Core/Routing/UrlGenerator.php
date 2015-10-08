<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\UrlGenerator.
 */

namespace Drupal\Core\Routing;

use Drupal\Core\GeneratedUrl;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext as SymfonyRequestContext;
use Symfony\Component\Routing\Route as SymfonyRoute;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

/**
 * Generates URLs from route names and parameters.
 */
class UrlGenerator implements UrlGeneratorInterface {

  /**
   * @var RequestContext
   */
  protected $context;

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
   * The first two elements are the first two parameters of str_replace(), so
   * if you override this variable you can also use arrays for the encoded
   * and decoded characters.
   *
   * @see \Symfony\Component\Routing\Generator\UrlGenerator
   */
  protected $decodedChars = [
    // the slash can be used to designate a hierarchical structure and we want allow using it with this meaning
    // some webservers don't allow the slash in encoded form in the path for security reasons anyway
    // see http://stackoverflow.com/questions/4069002/http-400-if-2f-part-of-get-url-in-jboss
    '%2F', // Map from these encoded characters.
    '/', // Map to these decoded characters.
  ];

  /**
   *  Constructs a new generator object.
   *
   * @param \Drupal\Core\Routing\RouteProviderInterface $provider
   *   The route provider to be searched for routes.
   * @param \Drupal\Core\PathProcessor\OutboundPathProcessorInterface $path_processor
   *   The path processor to convert the system path to one suitable for urls.
   * @param \Drupal\Core\RouteProcessor\OutboundRouteProcessorInterface $route_processor
   *   The route processor.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   A request stack object.
   * @param string[] $filter_protocols
   *   (optional) An array of protocols allowed for URL generation.
   */
  public function __construct(RouteProviderInterface $provider, OutboundPathProcessorInterface $path_processor, OutboundRouteProcessorInterface $route_processor, RequestStack $request_stack, array $filter_protocols = ['http', 'https']) {
    $this->provider = $provider;
    $this->context = new RequestContext();

    $this->pathProcessor = $path_processor;
    $this->routeProcessor = $route_processor;
    UrlHelper::setAllowedProtocols($filter_protocols);
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(SymfonyRequestContext $context) {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function setStrictRequirements($enabled) {
    // Ignore changes to this.
  }

  /**
   * {@inheritdoc}
   */
  public function isStrictRequirements() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathFromRoute($name, $parameters = array()) {
    $route = $this->getRoute($name);
    $name = $this->getRouteDebugMessage($name);
    $this->processRoute($name, $route, $parameters);
    $path = $this->getInternalPathFromRoute($name, $route, $parameters);
    // Router-based paths may have a querystring on them but Drupal paths may
    // not have one, so remove any ? and anything after it. For generate() this
    // is handled in processPath().
    $path = preg_replace('/\?.*/', '', $path);
    return trim($path, '/');
  }

  /**
   * Substitute the route parameters into the route path.
   *
   * Note: This code was copied from
   * \Symfony\Component\Routing\Generator\UrlGenerator::doGenerate() and
   * shortened by removing code that is not relevant to Drupal or that is
   * handled separately in ::generateFromRoute(). The Symfony version should be
   * examined for changes in new Symfony releases.
   *
   * @param array $variables
   *   The variables form the compiled route, corresponding to slugs in the
   *   route path.
   * @param array $defaults
   *   The defaults from the route.
   * @param array $tokens
   *   The tokens from the compiled route.
   * @param array $parameters
   *   The route parameters passed to the generator. Parameters that do not
   *   match any variables will be added to the result as query parameters.
   * @param array $query_params
   *   Query parameters passed to the generator as $options['query'].
   * @param string $name
   *   The route name or other identifying string from ::getRouteDebugMessage().
   *
   *
   * @return string
   *   The url path, without any base path, including possible query string.
   *
   * @throws MissingMandatoryParametersException
   *   When some parameters are missing that are mandatory for the route
   * @throws InvalidParameterException
   *   When a parameter value for a placeholder is not correct because it does
   *   not match the requirement
   */
  protected function doGenerate(array $variables, array $defaults, array $tokens, array $parameters, array $query_params, $name) {
    $variables = array_flip($variables);
    $mergedParams = array_replace($defaults, $this->context->getParameters(), $parameters);

    // all params must be given
    if ($diff = array_diff_key($variables, $mergedParams)) {
      throw new MissingMandatoryParametersException(sprintf('Some mandatory parameters are missing ("%s") to generate a URL for route "%s".', implode('", "', array_keys($diff)), $name));
    }

    $url = '';
    // Tokens start from the end of the path and work to the beginning. The
    // first one or several variable tokens may be optional, but once we find a
    // supplied token or a static text portion of the path, all remaining
    // variables up to the start of the path must be supplied to there is no gap.
    $optional = TRUE;
    // Structure of $tokens from the compiled route:
    // If the path is /admin/config/user-interface/shortcut/manage/{shortcut_set}/add-link-inline
    // [ [ 0 => 'text', 1 => '/add-link-inline' ], [ 0 => 'variable', 1 => '/', 2 => '[^/]++', 3 => 'shortcut_set' ], [ 0 => 'text', 1 => '/admin/config/user-interface/shortcut/manage' ] ]
    //
    // For a simple fixed path, there is just one token.
    // If the path is /admin/config
    // [ [ 0 => 'text', 1 => '/admin/config' ] ]
    foreach ($tokens as $token) {
      if ('variable' === $token[0]) {
        if (!$optional || !array_key_exists($token[3], $defaults) || (isset($mergedParams[$token[3]]) && (string) $mergedParams[$token[3]] !== (string) $defaults[$token[3]])) {
          // check requirement
          if (!preg_match('#^'.$token[2].'$#', $mergedParams[$token[3]])) {
            $message = sprintf('Parameter "%s" for route "%s" must match "%s" ("%s" given) to generate a corresponding URL.', $token[3], $name, $token[2], $mergedParams[$token[3]]);
            throw new InvalidParameterException($message);
          }

          $url = $token[1] . $mergedParams[$token[3]] . $url;
          $optional = FALSE;
        }
      }
      else {
        // Static text
        $url = $token[1] . $url;
        $optional = FALSE;
      }
    }

    if ('' === $url) {
      $url = '/';
    }

    // The contexts base URL is already encoded (see Symfony\Component\HttpFoundation\Request)
    $url = str_replace($this->decodedChars[0], $this->decodedChars[1], rawurlencode($url));

    // Drupal paths rarely include dots, so skip this processing if possible.
    if (strpos($url, '/.') !== FALSE) {
      // the path segments "." and ".." are interpreted as relative reference when
      // resolving a URI; see http://tools.ietf.org/html/rfc3986#section-3.3
      // so we need to encode them as they are not used for this purpose here
      // otherwise we would generate a URI that, when followed by a user agent
      // (e.g. browser), does not match this route
      $url = strtr($url, array('/../' => '/%2E%2E/', '/./' => '/%2E/'));
      if ('/..' === substr($url, -3)) {
        $url = substr($url, 0, -2) . '%2E%2E';
      }
      elseif ('/.' === substr($url, -2)) {
        $url = substr($url, 0, -1) . '%2E';
      }
    }

    // Add a query string if needed, including extra parameters.
    $query_params += array_diff_key($parameters, $variables, $defaults);
    if ($query_params && $query = http_build_query($query_params, '', '&')) {
      // "/" and "?" can be left decoded for better user experience, see
      // http://tools.ietf.org/html/rfc3986#section-3.4
      $url .= '?'.strtr($query, array('%2F' => '/'));
    }

    return $url;
  }

  /**
   * Gets the path of a route.
   *
   * @param $name
   *   The route name or other debug message.
   * @param \Symfony\Component\Routing\Route $route
   *  The route object.
   * @param array $parameters
   *  An array of parameters as passed to
   *  \Symfony\Component\Routing\Generator\UrlGeneratorInterface::generate().
   * @param array $query_params
   *   An array of query string parameter, which will get any extra values from
   *   $parameters merged in.
   *
   * @return string
   *  The url path corresponding to the route, without the base path.
   */
  protected function getInternalPathFromRoute($name, SymfonyRoute $route, $parameters = array(), $query_params = array()) {
    // The Route has a cache of its own and is not recompiled as long as it does
    // not get modified.
    $compiledRoute = $route->compile();

    return $this->doGenerate($compiledRoute->getVariables(), $route->getDefaults(), $compiledRoute->getTokens(), $parameters, $query_params, $name);
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
  public function generateFromRoute($name, $parameters = array(), $options = array(), $collect_bubbleable_metadata = FALSE) {
    $options += array('prefix' => '');
    $route = $this->getRoute($name);
    $generated_url = $collect_bubbleable_metadata ? new GeneratedUrl() : NULL;

    $query_params = [];
    // Symfony adds any parameters that are not path slugs as query strings.
    if (isset($options['query']) && is_array($options['query'])) {
      $query_params = $options['query'];
    }

    $fragment = '';
    if (isset($options['fragment'])) {
      if (($fragment = trim($options['fragment'])) != '') {
        $fragment = '#' . $fragment;
      }
    }

    // Generate a relative URL having no path, just query string and fragment.
    if ($route->getOption('_no_path')) {
      $query = $query_params ? '?' . http_build_query($query_params, '', '&') : '';
      $url = $query . $fragment;
      return $collect_bubbleable_metadata ? $generated_url->setGeneratedUrl($url) : $url;
    }

    $options += array('prefix' => '');
    $name = $this->getRouteDebugMessage($name);
    $this->processRoute($name, $route, $parameters, $generated_url);
    $path = $this->getInternalPathFromRoute($name, $route, $parameters, $query_params);
    $path = $this->processPath($path, $options, $generated_url);

    if (!empty($options['prefix'])) {
      $path = ltrim($path, '/');
      $prefix = empty($path) ? rtrim($options['prefix'], '/') : $options['prefix'];
      $path = '/' . str_replace('%2F', '/', rawurlencode($prefix)) . $path;
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

      $url = $base_url . $path . $fragment;
      return $collect_bubbleable_metadata ? $generated_url->setGeneratedUrl($url) : $url;
    }

    $base_url = $this->context->getBaseUrl();

    $absolute = !empty($options['absolute']);
    if (!$absolute || !$host = $this->context->getHost()) {
      $url = $base_url . $path . $fragment;
      return $collect_bubbleable_metadata ? $generated_url->setGeneratedUrl($url) : $url;
    }

    // Prepare an absolute URL by getting the correct scheme, host and port from
    // the request context.
    if (isset($options['https'])) {
      $scheme = $options['https'] ? 'https' : 'http';
    }
    else {
      $scheme = $this->context->getScheme();
    }
    $scheme_req = $route->getSchemes();
    if ($scheme_req && ($req = $scheme_req[0]) && $scheme !== $req) {
      $scheme = $req;
    }
    $port = '';
    if ('http' === $scheme && 80 != $this->context->getHttpPort()) {
      $port = ':' . $this->context->getHttpPort();
    } elseif ('https' === $scheme && 443 != $this->context->getHttpsPort()) {
      $port = ':' . $this->context->getHttpsPort();
    }
    if ($collect_bubbleable_metadata) {
      $generated_url->addCacheContexts(['url.site']);
    }
    $url = $scheme . '://' . $host . $port . $base_url . $path . $fragment;
    return $collect_bubbleable_metadata ? $generated_url->setGeneratedUrl($url) : $url;

  }

  /**
   * Passes the path to a processor manager to allow alterations.
   */
  protected function processPath($path, &$options = array(), BubbleableMetadata $bubbleable_metadata = NULL) {
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
    $path = $this->pathProcessor->processOutbound($actual_path === '/' ? $actual_path : rtrim($actual_path, '/'), $options, $this->requestStack->getCurrentRequest(), $bubbleable_metadata);
    $path .= $query_string;
    return $path;
  }

  /**
   * Passes the route to the processor manager for altering before compilation.
   *
   * @param string $name
   *   The route name.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object to process.
   * @param array $parameters
   *   An array of parameters to be passed to the route compiler.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   (optional) Object to collect route processors' bubbleable metadata.
   */
  protected function processRoute($name, SymfonyRoute $route, array &$parameters, BubbleableMetadata $bubbleable_metadata = NULL) {
    $this->routeProcessor->processOutbound($name, $route, $parameters, $bubbleable_metadata);
  }

  /**
   * Find the route using the provided route name.
   *
   * @param string|\Symfony\Component\Routing\Route $name
   *   The route name or a route object.
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

  /**
   * {@inheritDoc}
   */
  public function supports($name) {
    // Support a route object and any string as route name.
    return is_string($name) || $name instanceof SymfonyRoute;
  }

  /**
   * {@inheritDoc}
   */
  public function getRouteDebugMessage($name, array $parameters = array()) {
    if (is_scalar($name)) {
      return $name;
    }

    if ($name instanceof SymfonyRoute) {
      return 'Route with pattern ' . $name->getPath();
    }

    return serialize($name);
  }
}
