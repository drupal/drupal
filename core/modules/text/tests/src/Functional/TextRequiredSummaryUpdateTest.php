<?php

namespace Drupal\Tests\text\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updates for adding required summary flags to widgets and fields.
 *
 * @group text
 * @group legacy
 */
class TextRequiredSummaryUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that widgets and fields are updated for required summary flag.
   *
   * @see text_post_update_add_required_summary_flag()
   */
  public function testFieldAndWidgetUpdate() {
    // No show summary flag exists pre-update.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertFalse(array_key_exists('show_summary', $options['settings']));

    $field = FieldConfig::load('node.article.body');
    $settings = $field->getSettings();
    $this->assertFalse(array_key_exists('required_summary', $settings));

    $this->runUpdates();

    // The show summary setting has been populated on the widget.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertIdentical(FALSE, $options['settings']['show_summary']);

    // And the so has the required sumamry setting on the field.
    $field = FieldConfig::load('node.article.body');
    $settings = $field->getSettings();
    $this->assertIdentical(FALSE, $settings['required_summary']);
  }

}
