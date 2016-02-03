<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyFieldTidTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\Core\Render\RenderContext;
use Drupal\views\Views;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the taxonomy term VID field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldVidTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_taxonomy_vid_field');

  function testViewsHandlerVidField() {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    $view = Views::getView('test_taxonomy_vid_field');
    $this->executeView($view);

    $actual = $renderer->executeInRenderContext(new RenderContext(), function () use ($view) {
      return $view->field['vid']->advancedRender($view->result[0]);
    });
    $vocabulary = Vocabulary::load($this->term1->getVocabularyId());
    $expected = $vocabulary->get('name');

    $this->assertEqual($expected, $actual);
  }

}
