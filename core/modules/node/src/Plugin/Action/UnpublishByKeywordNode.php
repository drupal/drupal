<?php

namespace Drupal\node\Plugin\Action;

use Drupal\action\Plugin\Action\UnpublishByKeywordNode as ActionUnpublishByKeywordNode;

/**
 * Unpublishes a node containing certain keywords.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 * \Drupal\action\Plugin\Action\UnpublishByKeywordNode instead.
 *
 * @see https://www.drupal.org/node/3424506
 */
class UnpublishByKeywordNode extends ActionUnpublishByKeywordNode {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\UnpublishByKeywordNode instead. See https://www.drupal.org/node/3424506', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
