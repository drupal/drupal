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
   * Gets the internal path (system path) for a route.
   *
   * @param string|\Symfony\Component\Routing\Route $name
   *  The route name or a route object.
   * @param array $parameters
   *  An array of parameters as passed to
   *  \Symfony\Component\Routing\Generator\UrlGeneratorInterface::generate().
   *
   * @return string
   *  The internal Drupal path corresponding to the route.
   */
  public function getPathFromRoute($name, $parameters = array());

  /**
   * Generates a URL or path for a specific route based on the given parameters.
   *
   * Parameters that reference placeholders in the route pattern will be
   * substituted for them in the pattern. Extra params are added as query
   * strings to the URL.
   *
   * @param string|\Symfony\Component\Routing\Route $name
   *   The route name or a route object.
   * @param array  $parameters
   *   An associative array of parameter names and values.
   * @param array $options
   *   (optional) An associative array of additional options, with the following
   *   elements:
   *   - 'query': An array of query key/value-pairs (without any URL-encoding)
   *     to append to the URL.
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
   *     respectively. TRUE enforces HTTPS and FALSE enforces HTTP.
   *   - 'base_url': Only used internally by a path processor, for example, to
   *     modify the base URL when a language dependent URL requires so.
   *   - 'prefix': Only used internally, to modify the path when a language
   *     dependent URL requires so.
   * @param bool $collect_bubbleable_metadata
   *   (optional) Defaults to FALSE. When TRUE, both the generated URL and its
   *   associated bubbleable metadata are returned.
   *
   * @return string|\Drupal\Core\GeneratedUrl
   *   The generated URL for the given route.
   *   When $collect_bubbleable_metadata is TRUE, a GeneratedUrl object is
   *   returned, containing the generated URL plus bubbleable metadata.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown when the named route does not exist.
   * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
   *   Thrown when some parameters are missing that are mandatory for the route.
   * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
   *   Thrown when a parameter value for a placeholder is not correct because it
   *   does not match the requirement.
   */
  public function generateFromRoute($name, $parameters = array(), $options = array(), $collect_bubbleable_metadata = FALSE);

}
