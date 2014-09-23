<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\LinkCancel.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to user cancel.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_link_cancel")
 */
class LinkCancel extends Link {

  /**
   * {@inheritdoc}
   */
  protected function renderLink(EntityInterface $entity, ResultRow $values) {
    if ($entity && $entity->access('delete')) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : $this->t('Cancel account');

      $this->options['alter']['path'] = $entity->getSystemPath('cancel-form');
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }

}
