<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests updates for entity displays.
 *
 * @group Update
 * @group legacy
 */
class UpdateEntityDisplayTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests that entity displays are updated with regions for their fields.
   *
   * @see system_post_update_add_region_to_entity_displays()
   */
  public function testRegionUpdate() {
    // No region key appears pre-update.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertFalse(array_key_exists('region', $options));

    $entity_view_display = EntityViewDisplay::load('node.article.default');
    $options = $entity_view_display->getComponent('body');
    $this->assertFalse(array_key_exists('region', $options));

    $this->runUpdates();

    // The region key has been populated with 'content'.
    $entity_form_display = EntityFormDisplay::load('node.article.default');
    $options = $entity_form_display->getComponent('body');
    $this->assertIdentical('content', $options['region']);

    $entity_view_display = EntityViewDisplay::load('node.article.default');
    $options = $entity_view_display->getComponent('body');
    $this->assertIdentical('content', $options['region']);
  }

  /**
   * Tests that entity displays are updated to properly store extra fields.
   *
   * @see system_post_update_extra_fields()
   */
  public function testExtraFieldsUpdate() {
    $assertion = function ($expected_keys) {
      $entity_view_display = EntityViewDisplay::load('node.article.default');
      $this->assertEquals($expected_keys, array_keys($entity_view_display->getComponent('links')));
    };

    // Before the update extra fields are missing additional configuration.
    $assertion(['weight', 'region']);
    $this->runUpdates();
    // After the update the additional configuration is present.
    $assertion(['weight', 'region', 'settings', 'third_party_settings']);
  }

}
