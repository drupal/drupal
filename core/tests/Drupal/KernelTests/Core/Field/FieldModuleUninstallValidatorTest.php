<?php

namespace Drupal\KernelTests\Core\Field;

use Drupal\Core\Extension\ModuleUninstallValidatorException;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests FieldModuleUninstallValidator functionality.
 *
 * @group Field
 */
class FieldModuleUninstallValidatorTest extends EntityKernelTestBase {

  /**
   * The entity definition update manager.
   *
   * @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface
   */
  protected $entityDefinitionUpdateManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('user', 'users_data');
    $this->entityDefinitionUpdateManager = $this->container->get('entity.definition_update_manager');

    // Setup some fields for entity_test_extra to create.
    $definitions['extra_base_field'] = BaseFieldDefinition::create('string')
      ->setName('extra_base_field')
      ->setTargetEntityTypeId('entity_test')
      ->setTargetBundle('entity_test');
    $this->state->set('entity_test.additional_base_field_definitions', $definitions);
    $definitions['extra_bundle_field'] = FieldStorageDefinition::create('string')
      ->setName('extra_bundle_field')
      ->setTargetEntityTypeId('entity_test')
      ->setTargetBundle('entity_test');
    $this->state->set('entity_test.additional_field_storage_definitions', $definitions);
    $this->state->set('entity_test.entity_test.additional_bundle_field_definitions', $definitions);
    $this->entityManager->clearCachedDefinitions();
  }

  /**
   * Tests uninstall entity_test module with and without content for the field.
   */
  public function testUninstallingModule() {
    // Test uninstall works fine without content.
    $this->assertModuleInstallUninstall('entity_test_extra');

    // Test uninstalling works fine with content having no field values.
    $entity = $this->entityManager->getStorage('entity_test')->create([
      'name' => $this->randomString(),
    ]);
    $entity->save();
    $this->assertModuleInstallUninstall('entity_test_extra');
    $entity->delete();

    // Verify uninstall works fine without content again.
    $this->assertModuleInstallUninstall('entity_test_extra');
    // Verify uninstalling entity_test is not possible when there is content for
    // the base field.
    $this->enableModules(['entity_test_extra']);
    $this->entityDefinitionUpdateManager->applyUpdates();
    $entity = $this->entityManager->getStorage('entity_test')->create([
      'name' => $this->randomString(),
      'extra_base_field' => $this->randomString(),
    ]);
    $entity->save();

    try {
      $message = 'Module uninstallation fails as the module provides a base field which has content.';
      $this->getModuleInstaller()->uninstall(['entity_test_extra']);
      $this->fail($message);
    }
    catch (ModuleUninstallValidatorException $e) {
      $this->pass($message);
      $this->assertEqual($e->getMessage(), 'The following reasons prevent the modules from being uninstalled: There is data for the field extra_base_field on entity type Test entity');
    }

    // Verify uninstalling entity_test is not possible when there is content for
    // the bundle field.
    $entity->delete();
    $this->assertModuleInstallUninstall('entity_test_extra');
    $this->enableModules(['entity_test_extra']);
    $this->entityDefinitionUpdateManager->applyUpdates();
    $entity = $this->entityManager->getStorage('entity_test')->create([
      'name' => $this->randomString(),
      'extra_bundle_field' => $this->randomString(),
    ]);
    $entity->save();
    try {
      $this->getModuleInstaller()->uninstall(['entity_test_extra']);
      $this->fail('Module uninstallation fails as the module provides a bundle field which has content.');
    }
    catch (ModuleUninstallValidatorException $e) {
      $this->pass('Module uninstallation fails as the module provides a bundle field which has content.');
    }
  }

  /**
   * Asserts the given module can be installed and uninstalled.
   *
   * @param string $module_name
   *   The module to install and uninstall.
   */
  protected function assertModuleInstallUninstall($module_name) {
    // Install the module if it is not installed yet.
    if (!\Drupal::moduleHandler()->moduleExists($module_name)) {
      $this->enableModules([$module_name]);
    }
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertTrue($this->getModuleHandler()->moduleExists($module_name), $module_name . ' module is enabled.');
    $this->getModuleInstaller()->uninstall([$module_name]);
    $this->entityDefinitionUpdateManager->applyUpdates();
    $this->assertFalse($this->getModuleHandler()->moduleExists($module_name), $module_name . ' module is disabled.');
  }

  /**
   * Returns the ModuleHandler.
   *
   * @return \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected function getModuleHandler() {
    return $this->container->get('module_handler');
  }

  /**
   * Returns the ModuleInstaller.
   *
   * @return \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected function getModuleInstaller() {
    return $this->container->get('module_installer');
  }

}
