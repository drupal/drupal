<?php

namespace Drupal\Tests\language\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of language local tasks.
 *
 * @group language
 */
class LanguageLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = [
      'language' => 'core/modules/language',
    ];
    parent::setUp();
  }

  /**
   * Tests language admin overview local tasks existence.
   *
   * @dataProvider getLanguageAdminOverviewRoutes
   */
  public function testLanguageAdminLocalTasks($route, $expected) {
    $this->assertLocalTasks($route, $expected);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getLanguageAdminOverviewRoutes() {
    return [
      ['entity.configurable_language.collection', [['entity.configurable_language.collection', 'language.negotiation']]],
      ['language.negotiation', [['entity.configurable_language.collection', 'language.negotiation']]],
    ];
  }

  /**
   * Tests language edit local tasks existence.
   */
  public function testLanguageEditLocalTasks() {
    $this->assertLocalTasks('entity.configurable_language.edit_form', [
      0 => ['entity.configurable_language.edit_form'],
    ]);
  }

}
