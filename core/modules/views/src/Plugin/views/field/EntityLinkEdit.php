<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\EntityLinkEdit.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to edit an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link_edit")
 */
class EntityLinkEdit extends EntityLink {

  /**
   * {@inheritdoc}
   */
  protected function getEntityLinkTemplate() {
    return 'edit-form';
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
    return $this->t('edit');
  }

}
