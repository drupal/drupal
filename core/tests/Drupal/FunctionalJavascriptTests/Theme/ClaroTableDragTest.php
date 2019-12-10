<?php

namespace Drupal\FunctionalJavascriptTests\Theme;

use Drupal\FunctionalJavascriptTests\TableDrag\TableDragTest;

/**
 * Runs TableDragTest in Claro.
 *
 * @group claro
 *
 * @see \Drupal\FunctionalJavascriptTests\TableDrag\TableDragTest
 */
class ClaroTableDragTest extends TableDragTest {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected function findWeightsToggle($expected_text) {
    $toggle = $this->getSession()->getPage()->findLink($expected_text);
    $this->assertNotEmpty($toggle);
    return $toggle;
  }

}
