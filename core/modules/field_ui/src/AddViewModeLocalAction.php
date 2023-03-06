<?php

namespace Drupal\field_ui;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Menu\LocalActionDefault;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Routing\RouteMatchInterface;


/**
 * Defines a local action plugin with a dynamic title.
 */
class AddViewModeLocalAction extends LocalActionDefault {

  public function getOptions(RouteMatchInterface $route_match) {
    $options = parent::getOptions($route_match);
    $options += [
      'attributes' => [
      'class' => ['button', 'use-ajax'],
      'role' => 'button',
      'tabindex' => '0',
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => '85vw',
      ]),
      ],
    ];


    return $options;
  }

}
