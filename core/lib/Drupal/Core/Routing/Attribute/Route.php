<?php

declare(strict_types=1);

namespace Drupal\Core\Routing\Attribute;

use Symfony\Component\Routing\Attribute\DeprecatedAlias;
use Symfony\Component\Routing\Attribute\Route as SymfonyRoute;

/**
 * Adds Drupal-specific properties to the Symfony Route attribute.
 *
 * In addition to all the properties supported by the Symfony Route attribute,
 * this allows the page title to be set with a top level "title" property
 * instead of nesting it in the "_title" default:
 *
 * @code
 * #[Route(
 *   path: '/admin/config/system/site-information',
 *   name: 'system.site_information_settings',
 *   title: new TranslatableMarkup('Basic site settings'),
 *   requirements: [
 *     '_permission' => 'administer site configuration',
 *   ],
 * )]
 * @endcode
 *
 * The title may also be a static closure, called when the page is rendered:
 * @code
 * #[Route(
 *   path: '/block/add/{block_content_type}',
 *   name: 'block_content.add_form',
 *   title: static function (BlockContentTypeInterface $block_content_type) {
 *     return new TranslatableMarkup('Add %type content block', [
 *       '%type' => $block_content_type->label(),
 *     ]);
 *   },
 * )]
 * @endcode
 *
 * Closure arguments are resolved like those of a "_title_callback", but a
 * closure has no access to the container. Use "_title_callback" when the title
 * depends on injected services.
 *
 * @see \Symfony\Component\Routing\Attribute\Route
 * @see \Drupal\Core\Routing\Attribute\Route::getTitleClosure()
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class Route extends SymfonyRoute {

  /**
   * Constructs a Route attribute.
   *
   * @param string|array<string,string>|null $path
   *   The route path (i.e. "/user/login").
   * @param string|null $name
   *   The route name (i.e. "user.login").
   * @param array<string|\Stringable> $requirements
   *   Requirements for the route attributes.
   * @param array<string, mixed> $options
   *   Options for the route (i.e. ['_admin_route' => TRUE]).
   * @param array<string, mixed> $defaults
   *   Default values for the route attributes and query parameters.
   * @param string|null $host
   *   The host for which this route should be active.
   * @param string|string[] $methods
   *   The list of HTTP methods allowed by this route.
   * @param string|string[] $schemes
   *   The list of schemes allowed by this route (i.e. "https").
   * @param string|null $condition
   *   An expression that must evaluate to true for the route to be matched.
   * @param int|null $priority
   *   The priority of the route if multiple ones are defined for the same path.
   * @param string|null $locale
   *   The locale accepted by the route.
   * @param string|null $format
   *   The format returned by the route (i.e. "json", "xml").
   * @param bool|null $utf8
   *   Whether the route accepts UTF-8 in its parameters.
   * @param bool|null $stateless
   *   Whether the route is defined as stateless or stateful.
   * @param string|string[]|null $env
   *   The env(s) in which the route is defined.
   * @param string|\Symfony\Component\Routing\Attribute\DeprecatedAlias|array<string|\Symfony\Component\Routing\Attribute\DeprecatedAlias> $alias
   *   The list of aliases for this route.
   * @param string|\Stringable|\Closure|null $title
   *   The page title for the route. This is a convenience for setting the
   *   "_title" default. A closure may be used to calculate the title when the
   *   page is rendered.
   */
  public function __construct(
    string|array|null $path = NULL,
    ?string $name = NULL,
    array $requirements = [],
    array $options = [],
    array $defaults = [],
    ?string $host = NULL,
    array|string $methods = [],
    array|string $schemes = [],
    ?string $condition = NULL,
    ?int $priority = NULL,
    ?string $locale = NULL,
    ?string $format = NULL,
    ?bool $utf8 = NULL,
    ?bool $stateless = NULL,
    string|array|null $env = NULL,
    string|DeprecatedAlias|array $alias = [],
    string|\Stringable|\Closure|null $title = NULL,
  ) {
    parent::__construct($path, $name, $requirements, $options, $defaults, $host, $methods, $schemes, $condition, $priority, $locale, $format, $utf8, $stateless, $env, $alias);
    if ($title !== NULL) {
      assert(!array_key_exists('_title', $this->defaults), 'The "title" property cannot be used together with a "_title" default on the Route attribute.');
      $this->defaults['_title'] = $title;
    }
  }

  /**
   * Gets the title closure that a "_title_closure" route default refers to.
   *
   * Closures cannot be serialized into the router table, so route discovery
   * stores a reference to the attribute instead of the closure itself.
   *
   * @param string $class
   *   The class declaring the attributed method.
   * @param string $method
   *   The attributed method.
   * @param int $index
   *   The position of the Route attribute among the route attributes on the
   *   method.
   *
   * @return \Closure
   *   The title closure.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the reference does not point at a Route attribute with a
   *   closure title, which means the router needs rebuilding.
   *
   * @see \Drupal\Core\Routing\AttributeRouteDiscovery::getAttributes()
   */
  public static function getTitleClosure(string $class, string $method, int $index = 0): \Closure {
    // Filter on the Symfony attribute, so that the index matches the one
    // recorded by route discovery.
    $reflection = new \ReflectionMethod($class, $method);
    $attributes = $reflection->getAttributes(SymfonyRoute::class, \ReflectionAttribute::IS_INSTANCEOF);
    if (!isset($attributes[$index])) {
      throw new \InvalidArgumentException(sprintf('There is no Route attribute at index %d on %s::%s().', $index, $class, $method));
    }
    $title = $attributes[$index]->newInstance()->defaults['_title'] ?? NULL;
    if (!$title instanceof \Closure) {
      throw new \InvalidArgumentException(sprintf('The Route attribute at index %d on %s::%s() does not have a closure title.', $index, $class, $method));
    }
    return $title;
  }

}
