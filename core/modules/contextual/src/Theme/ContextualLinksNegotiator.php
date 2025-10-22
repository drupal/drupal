<?php

namespace Drupal\contextual\Theme;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Set the theme according to the parameter passed to the controller.
 */
final class ContextualLinksNegotiator implements ThemeNegotiatorInterface {

  public function __construct(
    protected readonly RouteMatchInterface $route_match,
    protected readonly RequestStack $requestStack,
    protected readonly ThemeHandlerInterface $themeHandler,
    protected readonly ConfigFactoryInterface $configFactory,
  ) {}

  public function applies(RouteMatchInterface $route_match): bool {
    return $route_match->getRouteName() === 'contextual.render';
  }

  public function determineActiveTheme(RouteMatchInterface $route_match): string {
    $request = $this->requestStack->getCurrentRequest();
    $theme = $request?->query->get('theme', '') ?? '';

    if ($this->themeHandler->themeExists($theme)) {
      return $theme;
    }

    return $this->configFactory->get('system.theme')->get('default');
  }

}
