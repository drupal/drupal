<?php

namespace Drupal\comment\Plugin\Action;

use Drupal\action\Plugin\Action\UnpublishByKeywordComment as ActionUnpublishByKeywordComment;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Unpublishes a comment containing certain keywords.
 *
 * @deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use
 *  \Drupal\action\Plugin\Action\UnpublishByKeywordComment instead.
 *
 * @see https://www.drupal.org/node/3424506
 */
class UnpublishByKeywordComment extends ActionUnpublishByKeywordComment {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityViewBuilderInterface $comment_view_builder, RendererInterface $renderer) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\UnpublishByKeywordComment instead. See https://www.drupal.org/node/3424506', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $comment_view_builder, $renderer);
  }

}
