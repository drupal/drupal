<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_drupal\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Test migrate_drupal module uninstall.
 *
 * @group migrate_drupal
 */
class MigrateDrupalUninstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'migrate_drupal'];

  /**
   * @covers migrate_drupal_uninstall
   */
  public function testUninstall(): void {
    $state = \Drupal::state();
    $data = [
      'key' => 'upgrade',
      'database' => [],
    ];
    $state->set('migrate_drupal_6', $data);
    $state->set('migrate_drupal_7', $data);

    $this->assertEquals($data, $state->get('migrate_drupal_6'));
    $this->assertEquals($data, $state->get('migrate_drupal_7'));

    $this->container->get('module_installer')->uninstall(['migrate_drupal']);

    $this->assertNull($state->get('migrate_drupal_6'));
    $this->assertNull($state->get('migrate_drupal_7'));
  }

}
