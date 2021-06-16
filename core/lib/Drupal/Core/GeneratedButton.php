<?php

namespace Drupal\Core;

/**
 * This class holds a <button> generated from the <button> route.
 *
 * Unlike \Drupal\Core\Render\Element\Button, this is not for generating
 * buttons for forms. This class is for putting a button in a list of links
 * such as a multi-level menu.
 */
class GeneratedButton extends GeneratedLink {

  /**
   * {@inheritdoc}
   */
  const TAG = 'button';

}
