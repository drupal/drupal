<?php

namespace Drupal\help;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a breadcrumb builder for help topic pages.
 *
 * @internal
 *   Tagged services are internal.
 */
class HelpBreadcrumbBuilder implements BreadcrumbBuilderInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(RouteMatchInterface $route_match) {
    return $route_match->getRouteName() == 'help.help_topic';
  }

  /**
   * {@inheritdoc}
   */
  public function build(RouteMatchInterface $route_match) {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->addCacheContexts(['url.path.parent']);
    $breadcrumb->addLink(Link::createFromRoute(new TranslatableMarkup('Home'), '<front>'));
    $breadcrumb->addLink(Link::createFromRoute(new TranslatableMarkup('Administration'), 'system.admin'));
    $breadcrumb->addLink(Link::createFromRoute(new TranslatableMarkup('Help'), 'help.main'));

    return $breadcrumb;
  }

}
