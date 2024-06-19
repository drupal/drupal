<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Views;

use Drupal\views\Views;

/**
 * Tests the field language handler.
 *
 * @group language
 * @see \Drupal\language\Plugin\views\field\Language
 */
class FieldLanguageTest extends LanguageTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests the language field.
   */
  public function testField(): void {
    $view = Views::getView('test_view');
    $view->setDisplay();
    $view->displayHandlers->get('default')->overrideOption('fields', [
      'langcode' => [
        'id' => 'langcode',
        'table' => 'views_test_data',
        'field' => 'langcode',
      ],
    ]);
    $this->executeView($view);

    $this->assertEquals('English', $view->field['langcode']->advancedRender($view->result[0]));
    $this->assertEquals('Lolspeak', $view->field['langcode']->advancedRender($view->result[1]));
  }

}
