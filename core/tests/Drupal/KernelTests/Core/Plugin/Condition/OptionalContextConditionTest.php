<?php

namespace Drupal\KernelTests\Core\Plugin\Condition;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests a condition with optional context.
 *
 * @group condition_test
 */
class OptionalContextConditionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'user', 'condition_test', 'node'];

  /**
   * Tests with both contexts mapped to the same user.
   */
  public function testContextMissing() {
    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    $condition = \Drupal::service('plugin.manager.condition')
      ->createInstance('condition_test_optional_context')
      ->setContextMapping([
        'node' => 'node',
      ]);
    \Drupal::service('context.handler')->applyContextMapping($condition, []);
    $this->assertTrue($condition->execute());
  }

  /**
   * Tests with both contexts mapped to the same user.
   */
  public function testContextNoValue() {
    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    $condition = \Drupal::service('plugin.manager.condition')
      ->createInstance('condition_test_optional_context')
      ->setContextMapping([
        'node' => 'node',
      ]);
    $definition = new ContextDefinition('entity:node');
    $contexts['node'] = (new Context($definition));
    \Drupal::service('context.handler')->applyContextMapping($condition, $contexts);
    $this->assertTrue($condition->execute());
  }

  /**
   * Tests with both contexts mapped to the same user.
   */
  public function testContextAvailable() {
    NodeType::create(['type' => 'example', 'name' => 'Example'])->save();
    /** @var \Drupal\Core\Condition\ConditionPluginBase $condition */
    $condition = \Drupal::service('plugin.manager.condition')
      ->createInstance('condition_test_optional_context')
      ->setContextMapping([
        'node' => 'node',
      ]);
    $definition = new ContextDefinition('entity:node');
    $node = Node::create(['type' => 'example']);
    $contexts['node'] = new Context($definition, $node);
    \Drupal::service('context.handler')->applyContextMapping($condition, $contexts);
    $this->assertFalse($condition->execute());
  }

}
