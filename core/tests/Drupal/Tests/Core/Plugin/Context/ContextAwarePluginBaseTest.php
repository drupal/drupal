<?php

namespace Drupal\Tests\Core\Plugin\Context;

use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionInterface;
use Drupal\Component\Plugin\Definition\ContextAwarePluginDefinitionTrait;
use Drupal\Component\Plugin\Definition\PluginDefinition;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Plugin\ContextAwarePluginBase;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\ContextAwarePluginBase
 * @group Plugin
 */
class ContextAwarePluginBaseTest extends UnitTestCase {

  /**
   * The plugin instance under test.
   *
   * @var \Drupal\Core\Plugin\ContextAwarePluginBase
   */
  private $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->plugin = new TestContextAwarePlugin([], 'the_sisko', new TestPluginDefinition());
  }

  /**
   * @covers ::getContextDefinitions
   */
  public function testGetContextDefinitions() {
    $this->assertInternalType('array', $this->plugin->getContextDefinitions());
  }

  /**
   * @covers ::getContextDefinition
   */
  public function testGetContextDefinition() {
    // The context is not defined, so an exception will be thrown.
    $this->setExpectedException(ContextException::class, 'The person context is not a valid context.');
    $this->plugin->getContextDefinition('person');
  }

}

class TestPluginDefinition extends PluginDefinition implements ContextAwarePluginDefinitionInterface {

  use ContextAwarePluginDefinitionTrait;

}

class TestContextAwarePlugin extends ContextAwarePluginBase {}
