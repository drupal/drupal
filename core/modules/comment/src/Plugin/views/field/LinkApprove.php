<?php

namespace Drupal\comment\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Attribute\ViewsField;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Provides a comment approve link.
 *
 * @ingroup views_field_handlers
 */
#[ViewsField("comment_link_approve")]
class LinkApprove extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $entity = $this->getEntity($row);
    if (!$entity) {
      return NULL;
    }
    return Url::fromRoute('comment.approve', ['comment' => $entity->id()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    $this->options['alter']['query'] = $this->getDestinationArray();
    return parent::renderLink($row);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Approve');
  }

}
