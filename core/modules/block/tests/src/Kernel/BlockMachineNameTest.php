<?php

namespace Drupal\Tests\block\Kernel;


use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Component\Plugin\PluginBase;
use Drupal\KernelTests\KernelTestBase;
use Drupal\simpletest\BlockCreationTrait;

/**
 * Tests block machine names.
 *
 * @group block
 */
class BlockMachineNameTest extends KernelTestBase {

  use BlockCreationTrait;
  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'block_content', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['sequence']);
    $this->installEntitySchema('block_content');
    $this->installEntitySchema('user');
  }

  /**
   * Tests machine name collisions.
   */
  public function testMachineNamesShouldNotCollideIfBlockTitlesContainPeriod() {
    // Create a block type.
    $type = BlockContentType::create([
      'id' => 'whizzy',
      'label' => 'Tis whizzy',
    ]);
    $type->save();

    // And a block content entity.
    $block_content = BlockContent::create([
      'type' => 'Tis whizzy',
      'info' => 'This. is whizzy',
    ]);
    $block_content->save();

    // Use \Drupal\Core\Block\BlockBase::getMachineNameSuggestion to generate
    // an ID.
    $plugin_id = 'block_content' . PluginBase::DERIVATIVE_SEPARATOR . $block_content->uuid();
    $block_plugin = $this->container->get('plugin.manager.block')->createInstance($plugin_id, []);
    $method = new \ReflectionMethod($block_plugin, 'getMachineNameSuggestion');
    $method->setAccessible(TRUE);
    $id = $method->invoke($block_plugin);

    // Now place the block content entity.
    $block = $this->placeBlock($plugin_id, [
      'label' => 'This. is whizzy',
      'id' => $id,
    ]);

    $theme = $this->container->get('config.factory')->get('system.theme')->get('default');

    // Create a new entity.
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $new_entity = $entity_type_manager->getStorage('block')->create(array('plugin' => $plugin_id, 'theme' => $theme));

    // Now generate a default ID like
    // \Drupal\block\BlockForm::getUniqueMachineName does.
    /** @var \Drupal\block\BlockForm $form_object */
    $form_object = $entity_type_manager->getFormObject('block', 'default');
    $form_object->setEntity($new_entity);

    $unique_id = $form_object->getUniqueMachineName($new_entity);

    // Assert that the new ID is different to the old one.
    $this->assertNotEquals($unique_id, $block->id());
  }

}
