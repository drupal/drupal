<?php

namespace Drupal\field_ui;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Defines a local action plugin with modal dialog.
 */
class DisplayModeLocalAction extends LocalActionDefault {

  /**
   * {@inheritdoc}
   */
  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options = NestedArray::mergeDeepArray([[
      'attributes' => [
        'class' => ['button', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => '880',
        ]),
      ],
    ], $options,
    ]);

    return $options;
  }

}
