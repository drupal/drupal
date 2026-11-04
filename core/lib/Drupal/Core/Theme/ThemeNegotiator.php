<?php

namespace Drupal\Core\Theme;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a class which determines the active theme of the page.
 *
 * It therefore uses ThemeNegotiatorInterface objects which are passed in
 * using the 'theme_negotiator' tag.
 */
class ThemeNegotiator implements ThemeNegotiatorInterface {

  /**
   * Constructs a new ThemeNegotiator.
   *
   * @param \Drupal\Core\Theme\ThemeAccessCheck $themeAccess
   *   The access checker for themes.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $classResolver
   *   The class resolver.
   * @param string[] $negotiators
   *   An array of negotiator IDs.
   */
  public function __construct(
    protected ThemeAccessCheck $themeAccess,
    protected ClassResolverInterface $classResolver,
    protected array $negotiators,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function determineActiveTheme(RouteMatchInterface $route_match) {
    foreach ($this->negotiators as $negotiator_id) {
      $negotiator = $this->classResolver->getInstanceFromDefinition($negotiator_id);

      if ($negotiator->applies($route_match)) {
        $theme = $negotiator->determineActiveTheme($route_match);
        if ($theme !== NULL && $this->themeAccess->checkAccess($theme)) {
          return $theme;
        }
      }
    }
  }

}
