<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests moving email, goto, and message actions to core namespace.
 *
 * @group Update
 * @group legacy
 */
class MoveActionsToCoreTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.actions-2815379.php',
    ];
  }

  /**
   * Tests upgrading actions to core namespace.
   *
   * @see action_post_update_move_plugins()
   */
  public function testUpdateActionPlugins() {
    $actions = ['goto_2815379', 'message_2815379', 'send_email_2815379'];
    $before = [];

    $dependencies = ['module' => ['action']];
    foreach ($actions as $key) {
      $config = \Drupal::configFactory()->get('system.action.' . $key);
      $this->assertSame($dependencies, $config->get('dependencies'));
      $before[$key] = $config->getRawData();
    }

    $this->runUpdates();

    foreach ($actions as $key) {
      /** @var \Drupal\system\Entity\Action $key */
      $action = Action::load($key);
      $this->assertSame([], $action->getDependencies());
      // Tests that other properties remains the same.
      $config = \Drupal::configFactory()->get('system.action.' . $key);
      $after = $before[$key];
      $after['dependencies'] = [];
      $this->assertSame($after, $config->getRawData());
    }
  }

}
