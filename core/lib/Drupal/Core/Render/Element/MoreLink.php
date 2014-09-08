<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\MoreLink.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a link render element.
 *
 * @RenderElement("more_link")
 */
class MoreLink extends Link {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    $info = parent::getInfo();
    return array(
      '#title' => $this->t('More'),
      '#theme_wrappers' => array(
        'container' => array(
          '#attributes' => array('class' => 'more-link'),
        ),
      ),
    ) + $info;
  }

}
