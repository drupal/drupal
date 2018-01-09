<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\system\Entity\Action;

/**
 * Tests upgrading comment and node actions to generic entity ones.
 *
 * @group Update
 */
class UpdateActionsWithEntityPluginsTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [__DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz'];
  }

  /**
   * Tests upgrading comment and node actions to generic entity ones.
   *
   * @see system_post_update_change_action_plugins()
   */
  public function testUpdateActionsWithEntityPlugins() {
    $old_new_action_id_map = [
      'comment_publish_action' => ['comment_publish_action', 'entity:publish_action:comment'],
      'comment_unpublish_action' => ['comment_unpublish_action', 'entity:unpublish_action:comment'],
      'comment_save_action' => ['comment_save_action', 'entity:save_action:comment'],
      'node_publish_action' => ['node_publish_action', 'entity:publish_action:node'],
      'node_unpublish_action' => ['node_unpublish_action', 'entity:unpublish_action:node'],
      'node_save_action' => ['node_save_action', 'entity:save_action:node'],
    ];

    foreach ($old_new_action_id_map as $key => list($before, $after)) {
      $config = \Drupal::configFactory()->get('system.action.' . $key);
      $this->assertSame($before, $config->get('plugin'));
    }

    $this->runUpdates();

    foreach ($old_new_action_id_map as $key => list($before, $after)) {
      /** @var \Drupal\system\Entity\Action $action */
      $action = Action::load($key);
      $this->assertSame($after, $action->getPlugin()->getPluginId());
      $config = \Drupal::configFactory()->get('system.action.' . $key);
      $this->assertSame($after, $config->get('plugin'));

      // Check that the type the action is based on will be a module dependency.
      $this->assertArraySubset(['module' => [$action->getPluginDefinition()['type']]], $action->getDependencies());
    }
  }

}
