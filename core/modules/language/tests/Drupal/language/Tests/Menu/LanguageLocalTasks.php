<?php

/**
 * @file
 * Contains \Drupal\language\Tests\Menu\LanguageLocalTasks.
 */

namespace Drupal\language\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of language local tasks.
 *
 * @group Drupal
 * @group language
 */
class LanguageLocalTasks extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Language local tasks test',
      'description' => 'Test existence of language local tasks.',
      'group' => 'Language',
    );
  }

  public function setUp() {
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
    $this->assertLocalTasks('language.edit', array(
      0 => array('language.edit'),
    ));
  }

}
