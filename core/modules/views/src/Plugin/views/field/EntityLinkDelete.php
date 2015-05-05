<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\EntityLinkDelete.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to delete an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link_delete")
 */
class EntityLinkDelete extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'delete-form';
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
    return $this->t('delete');
  }

}
