<?php

/**
 * @file
 * Contains \Drupal\field_ui\Element\FieldUiTable.
 */

namespace Drupal\field_ui\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a field_ui table element.
 *
 * @todo Annotate once https://www.drupal.org/node/2326409 is in.
 *   RenderElement("field_ui_table")
 */
class FieldUiTable extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array(
      '#theme' => 'field_ui_table',
      '#regions' => array('' => array()),
    );
  }

}
