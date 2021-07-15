<?php

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\entity_test\Entity\EntityTestMulBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the content translation dynamic permissions.
 *
 * @group content_translation
 *
 * @coversDefaultClass \Drupal\content_translation\ContentTranslationPermissions
 */
class ContentTranslationPermissionsTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'language', 'content_translation', 'user', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul');
    $this->installEntitySchema('entity_test_mul_with_bundle');
    EntityTestMulBundle::create([
      'id' => 'test',
      'label' => 'Test label',
      'description' => 'My test description',
    ])->save();
  }

  /**
   * Tests that enabling translation via the API triggers schema updates.
   */
  public function testPermissions() {
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul_with_bundle', 'test', TRUE);
    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertEquals(['entity_test'], $permissions['translate entity_test_mul']['dependencies']['module']);
    $this->assertEquals(['entity_test.entity_test_mul_bundle.test'], $permissions['translate test entity_test_mul_with_bundle']['dependencies']['config']);

    // Ensure bundle permission granularity works for bundles not based on
    // configuration.
    $this->container->get('state')->set('entity_test_mul.permission_granularity', 'bundle');
    $this->container->get('entity_type.manager')->clearCachedDefinitions();
    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertEquals(['entity_test'], $permissions['translate entity_test_mul entity_test_mul']['dependencies']['module']);
    $this->assertEquals(['entity_test.entity_test_mul_bundle.test'], $permissions['translate test entity_test_mul_with_bundle']['dependencies']['config']);
  }

}
