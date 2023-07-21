<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * @covers system_post_update_add_description_to_entity_view_mode
 * @covers system_post_update_add_description_to_entity_form_mode
 *
 * @group Update
 */
class UpdateDescriptionConfigurationPostUpdate extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-9.4.0.filled.standard.php.gz',
    ];
  }

  /**
   * Ensure that an empty string is added as a default value for description.
   */
  public function testUpdateDescriptionConfigurationPostUpdate(): void {
    $view_mode = EntityViewMode::load('block_content.full');
    $this->assertNull($view_mode->get('description'));

    $this->runUpdates();

    $view_mode = EntityViewMode::load('block_content.full');
    $this->assertSame('', $view_mode->get('description'));
  }

}
