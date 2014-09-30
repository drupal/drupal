<?php

/**
 * @file
 * Contains \Drupal\Core\Routing\UrlGeneratorInterface.
 */

namespace Drupal\Core\Routing;

use Symfony\Cmf\Component\Routing\VersatileGeneratorInterface;

/**
 * Defines an interface for generating a url from a route or system path.
 *
 * Provides additional methods and options not present in the base interface.
 */
interface UrlGeneratorInterface extends VersatileGeneratorInterface {

  /**
   * Generates an internal or external URL.
   *
   * @param string $path
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
   * @param array $options
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
   *     obtained from
   *     \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_URL).
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
   *   - 'entity_type': The entity type of the object that called _url(). Only
   *     set if _url() is invoked by Drupal\Core\Entity\Entity::uri().
   *   - 'entity': The entity object (such as a node) for which the URL is being
   *     generated. Only set if _url() is invoked by Drupal\Core\Entity\Entity::uri().
   *
   * @return
   *   A string containing a URL to the given path.
   *
   * @throws \Drupal\Core\Routing\GeneratorNotInitializedException.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   System paths should not be used - use route names and parameters.
   */
  public function generateFromPath($path = NULL, $options = array());

  /**
   * Gets the internal path (system path) of a route.
   *
   * @param string $name
   *  The route name.
   * @param array $parameters
   *  An array of parameters as passed to
   *  \Symfony\Component\Routing\Generator\UrlGeneratorInterface::generate().
   *
   * @return string
   *  The internal Drupal path corresponding to the route.
   *
   * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
   *   System paths should not be used - use route names and parameters.
   */
  public function getPathFromRoute($name, $parameters = array());

  /**
   * Generates a URL or path for a specific route based on the given parameters.
   *
   * Parameters that reference placeholders in the route pattern will be
   * substituted for them in the pattern. Extra params are added as query
   * strings to the URL.
   *
   * @param string $name
   *   The name of the route
   * @param array  $parameters
   *   An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL. Merged with the parameters array.
   *   - 'fragment': A fragment identifier (named anchor) to append to the URL.
   *     Do not include the leading '#' character.
   *   - 'absolute': Defaults to FALSE. Whether to force the output to be an
   *     absolute link (beginning with http:). Useful for links that will be
   *     displayed outside the site, such as in an RSS feed.
   *   - 'language': An optional language object used to look up the alias
   *     for the URL. If $options['language'] is omitted, it defaults to the
   *     current language for the language type LanguageInterface::TYPE_URL.
   *   - 'https': Whether this URL should point to a secure location. If not
   *     defined, the current scheme is used, so the user stays on HTTP or HTTPS
   *     respectively. if mixed mode sessions are permitted, TRUE enforces HTTPS
   *     and FALSE enforces HTTP.
   *   - 'prefix': Only used internally, to modify the path when a language
   *     dependent URL requires so.
   *
   * @return string
   *   The generated URL for the given route.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown when the named route doesn't exist.
   * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
   *   Thrown when some parameters are missing that are mandatory for the route.
   * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
   *   Thrown when a parameter value for a placeholder is not correct because it
   *   does not match the requirement.
   */
  public function generateFromRoute($name, $parameters = array(), $options = array());

}
