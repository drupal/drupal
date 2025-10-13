<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel\Views;

use Drupal\views\Views;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the argument language handler.
 *
 * @see \Drupal\language\Plugin\views\argument\Language.php
 */
#[Group('language')]
#[RunTestsInSeparateProcesses]
class ArgumentLanguageTest extends LanguageTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_view'];

  /**
   * Tests the language argument.
   */
  public function testArgument(): void {
    $view = Views::getView('test_view');
    foreach (['en' => 'John', 'xx-lolspeak' => 'George'] as $langcode => $name) {
      $view->setDisplay();
      $view->displayHandlers->get('default')->overrideOption('arguments', [
        'langcode' => [
          'id' => 'langcode',
          'table' => 'views_test_data',
          'field' => 'langcode',
        ],
      ]);
      $this->executeView($view, [$langcode]);

      $expected = [
        ['name' => $name],
      ];
      $this->assertIdenticalResultset($view, $expected, ['views_test_data_name' => 'name']);
      $view->destroy();
    }
  }

}
