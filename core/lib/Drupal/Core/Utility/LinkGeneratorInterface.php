<?php

/**
 * @file
 * Contains \Drupal\Core\Utility\LinkGeneratorInterface.
 */

namespace Drupal\Core\Utility;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines an interface for generating links from route names and parameters.
 */
interface LinkGeneratorInterface {

  /**
   * Renders a link to a URL.
   *
   * Examples:
   * @code
   * $link_generator = \Drupal::service('link_generator');
   * $installer_url = \Drupal\Core\Url::fromUri('base://core/install.php');
   * $installer_link = $link_generator->generate($text, $installer_url);
   * $external_url = \Drupal\Core\Url::fromUri('http://example.com', ['query' => ['foo' => 'bar']]);
   * $external_link = $link_generator->generate($text, $external_url);
   * $internal_url = \Drupal\Core\Url::fromRoute('system.admin');
   * $internal_link = $link_generator->generate($text, $internal_url);
   * @endcode
   * However, for links enclosed in translatable text you should use t() and
   * embed the HTML anchor tag directly in the translated string. For example:
   * @code
   * $text = t('Visit the <a href=":url">content types</a> page', array(':url' => \Drupal::url('entity.node_type.collection')));
   * @endcode
   * This keeps the context of the link title ('settings' in the example) for
   * translators.
   *
   * @param string|array $text
   *   The link text for the anchor tag as a translated string or render array.
   *   Strings will be sanitized automatically. If you need to output HTML in
   *   the link text, use a render array or an already sanitized string such as
   *   the output of \Drupal\Component\Utility\Xss::filter() or
   *   \Drupal\Component\Utility\SafeMarkup::format().
   * @param \Drupal\Core\Url $url
   *   The URL object used for the link. Amongst its options, the following may
   *   be set to affect the generated link:
   *   - attributes: An associative array of HTML attributes to apply to the
   *     anchor tag. If element 'class' is included, it must be an array; 'title'
   *     must be a string; other elements are more flexible, as they just need
   *     to work as an argument for the constructor of the class
   *     Drupal\Core\Template\Attribute($options['attributes']).
   *   - language: An optional language object. If the path being linked to is
   *     internal to the site, $options['language'] is used to determine whether
   *     the link is "active", or pointing to the current page (the language as
   *     well as the path must match).
   *   - 'set_active_class': Whether this method should compare the $route_name,
   *     $parameters, language and query options to the current URL to determine
   *     whether the link is "active". Defaults to FALSE. If TRUE, an "active"
   *     class will be applied to the link. It is important to use this
   *     sparingly since it is usually unnecessary and requires extra
   *     processing.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A GeneratedLink object containing a link to the given route and
   *   parameters and bubbleable metadata.
   *
   * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
   *   Thrown when the named route doesn't exist.
   * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
   *   Thrown when some parameters are missing that are mandatory for the route.
   * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
   *   Thrown when a parameter value for a placeholder is not correct because it
   *   does not match the requirement.
   *
   * @internal
   *   Should not be used in user code. Use \Drupal\Core\Link instead.
   */
  public function generate($text, Url $url);

  /**
   * Renders a link from a link object.
   *
   * @param \Drupal\Core\Link $link
   *   A link object to convert to a string.
   *
   * @return \Drupal\Core\GeneratedLink
   *   A GeneratedLink object containing a link to the given route and
   *   parameters and bubbleable metadata.
   *
   * @internal
   *   Should not be used in user code.
   *   Use \Drupal\Core\Link instead.
   */
  public function generateFromLink(Link $link);

}
