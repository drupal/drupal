<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Update\EmailWidgetSizeSettingUpdateTest.
 */

namespace Drupal\field\Tests\Update;

use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests the update for the 'size' setting of the 'email_default' field widget.
 *
 * @group field
 */
class EmailWidgetSizeSettingUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.email_widget_size_setting-2578741.php',
    ];
  }

  /**
   * Tests field_post_update_email_widget_size_setting().
   *
   * @see field_post_update_email_widget_size_setting()
   */
  public function testFieldPostUpdateEmailWidgetSizeSetting() {
    $configFactory = $this->container->get('config.factory');

    // Load the 'node.article.default' entity form display and check that the
    // widget for 'field_email_2578741' does not have a 'size' setting.
    /** @var \Drupal\Core\Config\Config $config */
    $config = $configFactory->get('core.entity_form_display.node.article.default');
    $settings = $config->get('content.field_email_2578741.settings');
    $this->assertTrue(!isset($settings['size']), 'The size setting does not exist prior to running the update functions.');

    // Run updates.
    $this->runUpdates();

    // Reload the config and check that the 'size' setting has been populated.
    $config = $configFactory->get('core.entity_form_display.node.article.default');
    $settings = $config->get('content.field_email_2578741.settings');
    $this->assertEqual($settings['size'], 60, 'The size setting exists and it has the correct default value.');
  }

}
