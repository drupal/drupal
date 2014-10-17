<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Block\BlockBaseTest.
 */

namespace Drupal\Tests\Core\Block;

use Drupal\block_test\Plugin\Block\TestBlockInstantiation;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Block\BlockBase
 * @group block
 */
class BlockBaseTest extends UnitTestCase {

  /**
   * Tests the machine name suggestion.
   *
   * @see \Drupal\Core\Block\BlockBase::getMachineNameSuggestion().
   */
  public function testGetMachineNameSuggestion() {
    $transliteration = $this->getMockBuilder('Drupal\Core\Transliteration\PHPTransliteration')
      // @todo Inject the module handler into PHPTransliteration.
      ->setMethods(array('readLanguageOverrides'))
      ->getMock();

    $condition_plugin_manager = $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $condition_plugin_manager->expects($this->atLeastOnce())
      ->method('getDefinitions')
      ->will($this->returnValue(array()));
    $container = new ContainerBuilder();
    $container->set('plugin.manager.condition', $condition_plugin_manager);
    \Drupal::setContainer($container);

    $config = array();
    $definition = array(
      'admin_label' => 'Admin label',
      'provider' => 'block_test',
    );
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('adminlabel', $block_base->getMachineNameSuggestion());

    // Test with more unicodes.
    $definition = array(
      'admin_label' => 'über åwesome',
      'provider' => 'block_test',
    );
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $block_base->setTransliteration($transliteration);
    $this->assertEquals('uberawesome', $block_base->getMachineNameSuggestion());
  }

  /**
   * Tests initializing the condition plugins initialization.
   */
  public function testConditionsBagInitialization() {
    $plugin_manager = $this->getMock('Drupal\Core\Executable\ExecutableManagerInterface');
    $plugin_manager->expects($this->once())
      ->method('getDefinitions')
      ->will($this->returnValue(array(
        'request_path' => array(
          'id' => 'request_path',
        ),
        'user_role' => array(
          'id' => 'user_role',
        ),
        'node_type' => array(
          'id' => 'node_type',
        ),
        'language' => array(
          'id' => 'language',
        ),
      )));
    $container = new ContainerBuilder();
    $container->set('plugin.manager.condition', $plugin_manager);
    \Drupal::setContainer($container);
    $config = array();
    $definition = array(
      'admin_label' => 'Admin label',
      'provider' => 'block_test',
    );

    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $conditions_collection = $block_base->getVisibilityConditions();

    $this->assertEquals(4, $conditions_collection->count(), "There are 4 condition plugins");

    $instance_id = $this->randomMachineName();
    $pages = 'node/1';
    $condition_config = array('id' => 'request_path', 'pages' => $pages);
    $block_base->setVisibilityConfig($instance_id, $condition_config);

    $plugin_manager->expects($this->once())->method('createInstance')
      ->withAnyParameters()->will($this->returnValue('test'));

    $condition = $block_base->getVisibilityCondition($instance_id);

    $this->assertEquals('test', $condition, "The correct condition is returned.");
  }

}
