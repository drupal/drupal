<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Test view/render hooks for entities.
 *
 * @todo Add tests for the following hooks. https://www.drupal.org/node/2755353
 * hook_entity_view_display_alter()
 * hook_entity_prepare_view()
 * hook_ENTITY_TYPE_view()
 * hook_entity_view()
 * hook_ENTITY_TYPE_view_alter()
 * hook_entity_view_alter()
 *
 * @group Entity
 */
class EntityViewHookTest extends EntityKernelTestBase {

  /**
   * Test hook_entity_display_build_alter().
   */
  public function testHookEntityDisplayBuildAlter() {
    entity_test_create_bundle('display_build_alter_bundle');
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = $this->container->get('renderer');

    $entity_ids = [];
    // Create some entities to test.
    for ($i = 0; $i < 5; $i++) {
      $entity = EntityTest::create([
        'name' => $this->randomMachineName(),
        'type' => 'display_build_alter_bundle',
      ]);
      $entity->save();
      $entity_ids[] = $entity->id();
    }

    /** @var \Drupal\entity_test\EntityTestViewBuilder $view_builder */
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder('entity_test');

    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('entity_test');
    $storage->resetCache();
    $entities = $storage->loadMultiple($entity_ids);

    $build = $view_builder->viewMultiple($entities);

    $output = $renderer->renderRoot($build);
    $this->setRawContent($output->__toString());
    // Confirm that the content added in
    // entity_test_entity_display_build_alter() appears multiple times, not
    // just for the final entity.
    foreach ($entity_ids as $id) {
      $this->assertText('Content added in hook_entity_display_build_alter for entity id ' . $id);
    }
  }

}
