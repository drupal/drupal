<?php

namespace Drupal\Tests\views\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Views;

/**
 * Tests the upgrade path for views field plugins.
 *
 * @see https://www.drupal.org/node/2455125
 *
 * @group Update
 */
class EntityViewsDataUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.views-entity-views-data-2455125.php',
    ];
  }

  /**
   * Tests that field plugins are updated properly.
   */
  public function testUpdateHookN() {
    $this->runUpdates();

    // Load and initialize our test view.
    $view = Views::getView('update_test');
    $view->initHandlers();

    // Extract the fields from the test view that were updated.
    /** @var \Drupal\views\Plugin\views\field\EntityField $field */
    $created = $view->field['created'];
    /** @var \Drupal\views\Plugin\views\field\EntityField $field */
    $created_1 = $view->field['created_1'];
    /** @var \Drupal\views\Plugin\views\field\EntityField $field */
    $created_2 = $view->field['created_2'];

    // Make sure the plugins were converted from date to field.
    $this->assertEqual($created->getPluginId(), 'field', 'created has correct plugin_id');
    $this->assertEqual($created_1->getPluginId(), 'field', 'created has correct plugin_id');
    $this->assertEqual($created_2->getPluginId(), 'field', 'created has correct plugin_id');

    // Check options on 'created'.
    $options = $created->options;
    $this->assertEqual($options['type'], 'timestamp');
    $this->assertFalse(array_key_exists('date_format', $options));
    $this->assertFalse(array_key_exists('custom_date_format', $options));
    $this->assertFalse(array_key_exists('timezone', $options));
    $this->assertEqual($options['settings']['date_format'], 'long');
    $this->assertEqual($options['settings']['custom_date_format'], '');
    $this->assertEqual($options['settings']['timezone'], 'Africa/Abidjan');

    // Check options on 'created'.
    $options = $created_1->options;
    $this->assertEqual($options['type'], 'timestamp_ago');
    $this->assertFalse(array_key_exists('date_format', $options));
    $this->assertFalse(array_key_exists('custom_date_format', $options));
    $this->assertFalse(array_key_exists('timezone', $options));
    $this->assertEqual($options['settings']['future_format'], '@interval');
    $this->assertEqual($options['settings']['past_format'], '@interval');
    $this->assertEqual($options['settings']['granularity'], 2);

    // Check options on 'created'.
    $options = $created_2->options;
    $this->assertEqual($options['type'], 'timestamp_ago');
    $this->assertFalse(array_key_exists('date_format', $options));
    $this->assertFalse(array_key_exists('custom_date_format', $options));
    $this->assertFalse(array_key_exists('timezone', $options));
    $this->assertEqual($options['settings']['future_format'], '@interval hence');
    $this->assertEqual($options['settings']['past_format'], '@interval ago');
    $this->assertEqual($options['settings']['granularity'], 2);
  }

}
