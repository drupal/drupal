<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Render\Element;

/**
 * Defines a layout class for navigation.
 *
 * @internal
 */
final class NavigationLayout extends LayoutDefault {

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    foreach (Element::children($regions) as $region_id) {
      foreach (Element::children($regions[$region_id]) as $component_uuid) {
        if (!Element::isEmpty($regions[$region_id][$component_uuid])) {
          $regions[$region_id][$component_uuid]['#theme'] = 'block__navigation';
        }
      }
    }
    return parent::build($regions);
  }

}
