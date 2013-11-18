<?php

/**
 * @file
 * Contains \Drupal\block\Tests\BlockBaseTest.
 */

namespace Drupal\block\Tests;

use Drupal\block_test\Plugin\Block\TestBlockInstantiation;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Transliteration\PHPTransliteration;
use Drupal\Tests\UnitTestCase;

// @todo Remove once the constants are replaced with constants on classes.
if (!defined('DRUPAL_NO_CACHE')) {
  define('DRUPAL_NO_CACHE', -1);
}

/**
 * Tests the base block plugin.
 *
 * @see \Drupal\block\BlockBase
 */
class BlockBaseTest extends UnitTestCase {

  public static function getInfo() {
    return array(
      'name' => 'Base plugin',
      'description' => 'Tests the base block plugin.',
      'group' => 'Block',
    );
  }

  /**
   * Tests the machine name suggestion.
   *
   * @see \Drupal\block\BlockBase::getMachineNameSuggestion().
   */
  public function testGetMachineNameSuggestion() {
    $transliteraton = $this->getMockBuilder('Drupal\Core\Transliteration\PHPTransliteration')
      // @todo Inject the module handler into PHPTransliteration.
      ->setMethods(array('readLanguageOverrides'))
      ->getMock();

    $container = new ContainerBuilder();
    $container->set('transliteration', $transliteraton);
    \Drupal::setContainer($container);

    $config = array();
    $definition = array('admin_label' => 'Admin label', 'module' => 'block_test');
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $this->assertEquals('adminlabel', $block_base->getMachineNameSuggestion());

    // Test with more unicodes.
    $definition = array('admin_label' =>'über åwesome', 'module' => 'block_test');
    $block_base = new TestBlockInstantiation($config, 'test_block_instantiation', $definition);
    $this->assertEquals('uberawesome', $block_base->getMachineNameSuggestion());
  }

}
