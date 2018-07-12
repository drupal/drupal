<?php

namespace Drupal\Tests\field\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests the update for the entity reference 'handler' setting.
 *
 * @group field
 * @group legacy
 */
class EntityReferenceHandlerSettingUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests field_post_update_entity_reference_handler_setting().
   *
   * @see field_post_update_entity_reference_handler_setting()
   */
  public function testFieldPostUpdateERHandlerSetting() {
    $configFactory = $this->container->get('config.factory');

    // Load the 'node.article.field_image' field config, and check that its
    // 'handler' setting is wrong.
    /** @var \Drupal\Core\Config\Config */
    $config = $configFactory->get('field.field.node.article.field_image');
    $settings = $config->get('settings');
    $this->assertEqual($settings['handler'], 'default:node');

    // Run updates.
    $this->runUpdates();

    // Reload the config, and check that the 'handler' setting has been fixed.
    $config = $configFactory->get('field.field.node.article.field_image');
    $settings = $config->get('settings');
    $this->assertEqual($settings['handler'], 'default:file');
  }

}
