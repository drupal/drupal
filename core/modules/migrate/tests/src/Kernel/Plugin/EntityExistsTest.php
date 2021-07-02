<?php

namespace Drupal\Tests\migrate\Kernel\Plugin;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;

/**
 * Tests the EntityExists process plugin.
 *
 * @group migrate
 */
class EntityExistsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
  }

  /**
   * Tests the EntityExists plugin.
   */
  public function testEntityExists() {
    $user = User::create([
      'name' => $this->randomString(),
    ]);
    $user->save();
    $uid = $user->id();

    $plugin = \Drupal::service('plugin.manager.migrate.process')
      ->createInstance('entity_exists', [
        'entity_type' => 'user',
      ]);
    $executable = $this->prophesize(MigrateExecutableInterface::class)->reveal();
    $row = new Row();

    // Ensure that the entity ID is returned if it really exists.
    $value = $plugin->transform($uid, $executable, $row, 'buffalo');
    $this->assertSame($uid, $value);

    // Ensure that the plugin returns FALSE if the entity doesn't exist.
    $value = $plugin->transform(420, $executable, $row, 'buffalo');
    $this->assertFalse($value);

    // Make sure the plugin can gracefully handle an array as input.
    $value = $plugin->transform([$uid, 420], $executable, $row, 'buffalo');
    $this->assertSame($uid, $value);
  }

}
