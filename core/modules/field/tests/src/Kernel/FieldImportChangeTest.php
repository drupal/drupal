<?php

declare(strict_types=1);

namespace Drupal\Tests\field\Kernel;

use Drupal\entity_test\EntityTestHelper;
use Drupal\field\Entity\FieldConfig;

/**
 * Update field storage and fields during config change method invocation.
 *
 * @group field
 */
class FieldImportChangeTest extends FieldKernelTestBase {

  /**
   * Modules to install.
   *
   * The default configuration provided by field_test_config is imported by
   * \Drupal\Tests\field\Kernel\FieldKernelTestBase::setUp() when it installs
   * field configuration.
   *
   * @var array
   */
  protected static $modules = ['field_test_config'];

  /**
   * Tests importing an updated field.
   */
  public function testImportChange(): void {
    EntityTestHelper::createBundle('test_bundle');

    $this->installConfig(['field_test_config']);
    $field_storage_id = 'field_test_import';
    $field_id = "entity_test.entity_test.$field_storage_id";
    $field_config_name = "field.field.$field_id";

    $active = $this->container->get('config.storage');
    $sync = $this->container->get('config.storage.sync');
    $this->copyConfig($active, $sync);

    // Save as files in the sync directory.
    $field = $active->read($field_config_name);
    $new_label = 'Test update import field';
    $field['label'] = $new_label;
    $sync->write($field_config_name, $field);

    // Import the content of the sync directory.
    $this->configImporter()->import();

    // Check that the updated config was correctly imported.
    $field = FieldConfig::load($field_id);
    $this->assertEquals($new_label, $field->getLabel(), 'field label updated');
  }

}
