<?php

/**
 * @file
 * Contains \Drupal\field\Tests\Update\FieldUpdateTest.
 */

namespace Drupal\field\Tests\Update;

use Drupal\Core\Config\Config;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\system\Tests\Update\UpdatePathTestBase;

/**
 * Tests that field settings are properly updated during database updates.
 *
 * @group field
 */
class FieldUpdateTest extends UpdatePathTestBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->configFactory = $this->container->get('config.factory');
  }

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
      __DIR__ . '/../../../tests/fixtures/update/drupal-8.views_entity_reference_plugins-2429191.php',
    ];
  }

  /**
   * Tests field_update_8001().
   *
   * @see field_update_8001()
   */
  public function testFieldUpdate8001() {
    // Load the 'node.field_image' field storage config, and check that is has
    // a 'target_bundle' setting.
    $config = $this->configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertTrue(array_key_exists('target_bundle', $settings));

    // Run updates.
    $this->runUpdates();

    // Reload the config, and check that the 'target_bundle' setting has been
    // removed.
    $config = $this->configFactory->get('field.storage.node.field_image');
    $settings = $config->get('settings');
    $this->assertFalse(array_key_exists('target_bundle', $settings));
  }

  /**
   * Tests field_update_8002().
   *
   * @see field_update_8002()
   */
  public function testFieldUpdate8002() {
    // Check that 'entity_reference' is the provider and a dependency of the
    // test field storage .
    $field_storage = $this->configFactory->get('field.storage.node.field_ref_views_select_2429191');
    $this->assertIdentical($field_storage->get('module'), 'entity_reference');
    $this->assertEntityRefDependency($field_storage, TRUE);

    // Check that 'entity_reference' is a dependency of the test field.
    $field = $this->configFactory->get('field.field.node.article.field_ref_views_select_2429191');
    $this->assertEntityRefDependency($field, TRUE);

    // Check that 'entity_reference' is a dependency of the test view.
    $view = $this->configFactory->get('views.view.entity_reference_plugins_2429191');
    $this->assertEntityRefDependency($view, TRUE);

    // Run updates.
    $this->runUpdates();

    // Check that 'entity_reference' is no longer a dependency of the test field
    // and view.
    $field_storage = $this->configFactory->get('field.storage.node.field_ref_views_select_2429191');
    $this->assertIdentical($field_storage->get('module'), 'core');
    $this->assertEntityRefDependency($field_storage, FALSE);
    $field = $this->configFactory->get('field.field.node.article.field_ref_views_select_2429191');
    $this->assertEntityRefDependency($field, FALSE);
    $view = $this->configFactory->get('views.view.entity_reference_plugins_2429191');
    $this->assertEntityRefDependency($view, FALSE);

    // Check that field selection, based on the view, still works. It only
    // selects nodes whose title contains 'foo'.
    $node_1 = Node::create(['type' => 'article', 'title' => 'foobar']);
    $node_1->save();
    $node_2 = Node::create(['type' => 'article', 'title' => 'barbaz']);
    $node_2->save();
    $field = FieldConfig::load('node.article.field_ref_views_select_2429191');
    $selection = \Drupal::service('plugin.manager.entity_reference_selection')->getSelectionHandler($field);
    $referencable = $selection->getReferenceableEntities();
    $this->assertEqual(array_keys($referencable['article']), [$node_1->id()]);
  }

  /**
   * Asserts that a config depends on 'entity_reference' or not
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config to test.
   * @param bool $present
   *   TRUE to test that entity_reference is present, FALSE to test that it is
   *   absent.
   */
  protected function assertEntityRefDependency(Config $config, $present) {
    $dependencies = $config->get('dependencies');
    $dependencies += ['module' => []];
    $this->assertEqual(in_array('entity_reference', $dependencies['module']), $present);
  }
}
