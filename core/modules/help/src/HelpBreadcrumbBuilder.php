<?php

namespace Drupal\help;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Cache\CacheableMetadata;
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
  public function applies(RouteMatchInterface $route_match, ?CacheableMetadata $cacheable_metadata = NULL) {
    // @todo Remove null safe operator in Drupal 12.0.0, see
    //   https://www.drupal.org/project/drupal/issues/3459277.
    $cacheable_metadata?->addCacheContexts(['route']);
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
