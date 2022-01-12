<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\d6\NodeReference as NonLegacyNodeReference;

/**
 * @deprecated in drupal:9.1.0 and is removed from drupal:10.0.0. Use
 * \Drupal\migrate_drupal\Plugin\migrate\field\d6\NodeReference instead.
 *
 * @see https://www.drupal.org/node/3159537
 */
class NodeReference extends NonLegacyNodeReference {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error('The ' . __NAMESPACE__ . '\NodeReference is deprecated in drupal:9.1.0 and will be removed from drupal:10.0.0. Instead use \Drupal\migrate_drupal\Plugin\migrate\field\d6\NodeReference. See https://www.drupal.org/node/3159537.', E_USER_DEPRECATED);

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
