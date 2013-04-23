<?php

/**
 * @file
 * Definition of Drupal\user\Plugin\views\field\LinkCancel.
 */

namespace Drupal\user\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to present a link to user cancel.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("user_link_cancel")
 */
class LinkCancel extends Link {

  /**
   * Overrides \Drupal\user\Plugin\views\field\Link::render_link().
   */
  public function render_link(EntityInterface $entity, \stdClass $values) {
    if ($entity && $entity->access('delete')) {
      $this->options['alter']['make_link'] = TRUE;

      $text = !empty($this->options['text']) ? $this->options['text'] : t('Cancel account');

      $uri = $entity->uri();
      $this->options['alter']['path'] = $uri['path'] . '/cancel';
      $this->options['alter']['query'] = drupal_get_destination();

      return $text;
    }
  }

}
