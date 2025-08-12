<?php

declare(strict_types=1);

namespace Drupal\element_info_test\Element;

use Drupal\Core\Render\Attribute\RenderElement;
use Drupal\Core\Render\Element\RenderElementBase;

/**
 * Provides deprecated render element for testing.
 */
#[RenderElement('deprecated')]
class Deprecated extends RenderElementBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.1.0 and is removed from drupal:11.0.0. See https://www.drupal.org/node/3068104', E_USER_DEPRECATED);
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [];
  }

}
