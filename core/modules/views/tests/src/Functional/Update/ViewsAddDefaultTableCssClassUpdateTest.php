<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the addition of the default table style `class` setting.
 *
 * @see views_post_update_table_css_class()
 *
 * @group Update
 * @group legacy
 */
class ViewsAddDefaultTableCssClassUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/test_table_css_class.php',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installModulesFromClassProperty($this->container);
  }

  /**
   * Tests the upgrade path adding table style default CSS class.
   */
  public function testViewsPostUpdateAddDefaultTableCssClass(): void {
    $view = View::load('test_table_css_class');
    $data = $view->toArray();
    $counter = 0;
    foreach ($data['display'] as $display) {
      if (array_key_exists('style', $display['display_options'])) {
        $style = $display['display_options']['style'];
        // The style has type and options so we need to ensure we are reviewing options.
        if (is_array($style) && array_key_exists('options', $style)) {
          $this->assertArrayNotHasKey('class', $style['options']);
          $counter++;
        }
      }
    }

    $this->assertEquals(3, $counter);
    $this->runUpdates();

    $view = View::load('test_table_css_class');
    $data = $view->toArray();

    $counter = 0;
    foreach ($data['display'] as $display) {
      if (array_key_exists('style', $display['display_options'])) {
        $style = $display['display_options']['style'];
        // The style has type and options so we need to ensure we are reviewing options.
        if (is_array($style) && array_key_exists('options', $style)) {
          if (is_array($style) && array_key_exists('options', $style)) {
            $this->assertArrayHasKey('class', $style['options']);
            $counter++;
          }
        }
      }
    }

    $this->assertEquals(3, $counter);
  }

}
