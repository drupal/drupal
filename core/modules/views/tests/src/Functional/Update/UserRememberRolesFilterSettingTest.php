<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests the upgrade path for the clean-up of empty remember_roles display settings for views filters.
 *
 * @group Update
 * @covers views_post_update_update_remember_role_empty
 */
class UserRememberRolesFilterSettingTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-10.3.0.filled.standard.php.gz',
    ];
  }

  /**
   * Test that filter values are updated properly.
   *
   * @see views_post_update_update_remember_role_empty()
   */
  public function testViewsPostUpdateBooleanFilterAcceptEmpty(): void {
    $view = View::load('files');
    $display = $view->get('display');
    $expected = [
      'authenticated' => 'authenticated',
      'anonymous' => '0',
      'administrator' => '0',
    ];
    $this->assertSame($expected, $display['default']['display_options']['filters']['filename']['expose']['remember_roles']);

    $this->runUpdates();

    $view = View::load('files');
    $display = $view->get('display');
    $expected = [
      'authenticated' => 'authenticated',
    ];
    $this->assertSame($expected, $display['default']['display_options']['filters']['filename']['expose']['remember_roles']);
  }

}
