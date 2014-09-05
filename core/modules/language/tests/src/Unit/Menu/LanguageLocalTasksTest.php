<?php

/**
 * @file
 * Contains \Drupal\Tests\language\Unit\Menu\LanguageLocalTasksTest.
 */

namespace Drupal\Tests\language\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of language local tasks.
 *
 * @group language
 */
class LanguageLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
    $this->directoryList = array(
      'language' => 'core/modules/language',
    );
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
    return array(
      array('language.admin_overview', array(array('language.admin_overview', 'language.negotiation'))),
      array('language.negotiation', array(array('language.admin_overview', 'language.negotiation'))),
    );
  }

  /**
   * Tests language edit local tasks existence.
   */
  public function testLanguageEditLocalTasks() {
    $this->assertLocalTasks('entity.configurable_language.edit_form', array(
      0 => array('entity.configurable_language.edit_form'),
    ));
  }

}
