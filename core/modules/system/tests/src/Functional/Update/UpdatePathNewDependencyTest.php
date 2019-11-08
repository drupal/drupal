<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Modules can introduce new dependencies and enable them in update hooks.
 *
 * @group system
 * @group legacy
 */
class UpdatePathNewDependencyTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../tests/fixtures/update/drupal-8.6.0.bare.testing.php.gz',
    ];
  }

  /**
   * Test that a module can add services that depend on new modules.
   */
  public function testUpdateNewDependency() {
    // The new_dependency_test before the update is just an empty info.yml file.
    // The code of the new_dependency_test module is after the update and
    // contains the dependency on the new_dependency_test_with_service module.
    $extension_config = $this->container->get('config.factory')->getEditable('core.extension');
    $extension_config
      ->set('module.new_dependency_test', 0)
      ->set('module', module_config_sort($extension_config->get('module')))
      ->save(TRUE);
    drupal_set_installed_schema_version('new_dependency_test', \Drupal::CORE_MINIMUM_SCHEMA_VERSION);

    // Rebuild the container and test that the service with the optional unmet
    // dependency is still available while the ones that fail are not.
    try {
      $this->rebuildContainer();
      $this->fail('The container has services with unmet dependencies and should have failed to rebuild.');
    }
    catch (ServiceNotFoundException $exception) {
      $this->assertEquals('The service "new_dependency_test.dependent" has a dependency on a non-existent service "new_dependency_test_with_service.service".', $exception->getMessage());
    }

    // Running the updates enables the dependency.
    $this->runUpdates();

    $this->assertTrue(array_key_exists('new_dependency_test', $this->container->get('config.factory')->get('core.extension')->get('module')));
    $this->assertTrue(array_key_exists('new_dependency_test_with_service', $this->container->get('config.factory')->get('core.extension')->get('module')));

    // Tests that the new services are available and working as expected.
    $this->assertEquals('Hello', $this->container->get('new_dependency_test_with_service.service')->greet());
    $this->assertEquals('Hello', $this->container->get('new_dependency_test.dependent')->greet());
    $this->assertEquals('Hello', $this->container->get('new_dependency_test.alias')->greet());
    $this->assertEquals('Hello World', $this->container->get('new_dependency_test.hard_dependency')->greet());
    $this->assertEquals('Hello World', $this->container->get('new_dependency_test.optional_dependency')->greet());

    // Tests that existing decorated services work as expected during update.
    $this->assertTrue(\Drupal::state()->get('new_dependency_test_update_8001.decorated_service'), 'The new_dependency_test.another_service service is decorated');
    $this->assertTrue(\Drupal::state()->get('new_dependency_test_update_8001.decorated_service_custom_inner'), 'The new_dependency_test.another_service_two service is decorated');

    // Tests that services are available as expected.
    $before_install = \Drupal::state()->get('new_dependency_test_update_8001.has_before_install', []);
    $this->assertSame([
      'new_dependency_test.hard_dependency' => FALSE,
      'new_dependency_test.optional_dependency' => TRUE,
      'new_dependency_test.recursion' => FALSE,
      'new_dependency_test.alias' => FALSE,
      'new_dependency_test.alias_dependency' => FALSE,
      'new_dependency_test.alias2' => FALSE,
      'new_dependency_test.alias_dependency2' => FALSE,
    ], $before_install);

    $after_install = \Drupal::state()->get('new_dependency_test_update_8001.has_after_install', []);
    $this->assertSame([
      'new_dependency_test.hard_dependency' => TRUE,
      'new_dependency_test.optional_dependency' => TRUE,
      'new_dependency_test.recursion' => TRUE,
      'new_dependency_test.alias' => TRUE,
      'new_dependency_test.alias_dependency' => TRUE,
      'new_dependency_test.alias2' => TRUE,
      'new_dependency_test.alias_dependency2' => TRUE,
    ], $after_install);
  }

}
