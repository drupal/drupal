<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Element\MoreLink.
 */

namespace Drupal\Core\Render\Element;

/**
 * Provides a link render element for a "more" link, like those used in blocks.
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
          '#attributes' => array('class' => array('more-link')),
        ),
      ),
    ) + $info;
  }

}
