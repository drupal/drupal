<?php

declare(strict_types = 1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the upgrade path for in_operation filter values.
 *
 * @group views
 * @group legacy
 *
 * @coversDefaultClass \Drupal\views\ViewsConfigUpdater
 *
 * @see views_post_update_in_operator_values()
 */
class InOperatorValuesUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.8.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests the upgrade path for in_operation filter values.
   *
   * @covers ::needsInOperatorFilterValuesUpdate
   * @covers ::processInOperatorFilterValues
   */
  public function testInOperatorValuesUpdate(): void {
    $config_factory = \Drupal::configFactory();
    $view = $config_factory->get('views.view.frontpage');
    $path = 'display.default.display_options.filters.langcode.value';
    $value = $view->get($path);
    $this->assertSame([
      '***LANGUAGE_language_content***' => '***LANGUAGE_language_content***',
    ], $value);

    $this->runUpdates();

    $view = $config_factory->get('views.view.frontpage');
    $value = $view->get($path);
    $this->assertSame(['***LANGUAGE_language_content***'], $value);
  }

}
