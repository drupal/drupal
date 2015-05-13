<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\views\field\EntityLink.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\views\ResultRow;

/**
 * Field handler to present a link to an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_link")
 */
class EntityLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $row) {
    return $this->getEntity($row) ? parent::render($row) : [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    $template = $this->getEntityLinkTemplate();
    return $this->getEntity($row)->urlInfo($template);
  }

  /**
   * Returns the entity link template name identifying the link route.
   *
   * @returns string
   *   The link template name.
   */
  protected function getEntityLinkTemplate() {
    return 'canonical';
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('view');
  }

}
