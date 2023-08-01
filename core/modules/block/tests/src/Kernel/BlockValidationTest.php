<?php

namespace Drupal\Tests\block\Kernel;

use Drupal\block\Entity\Block;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;

/**
 * Tests validation of block entities.
 *
 * @group block
 */
class BlockValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = Block::create([
      'id' => 'test_block',
      'theme' => 'stark',
      'plugin' => 'system_powered_by_block',
    ]);
    $this->entity->save();
  }

  /**
   * Tests validating a block with an unknown plugin ID.
   */
  public function testInvalidPluginId(): void {
    $this->entity->set('plugin', 'block_content:d7c9d8ba-663f-41b4-8756-86bc55c44653');
    // Block config entities with invalid block plugin IDs automatically fall
    // back to the `broken` block plugin.
    // @see https://www.drupal.org/node/2249303
    // @see \Drupal\Core\Block\BlockManager::getFallbackPluginId()
    // @see \Drupal\Core\Block\Plugin\Block\Broken
    $this->assertValidationErrors([]);

    $this->entity->set('plugin', 'non_existent');
    // @todo Expect error for this in https://www.drupal.org/project/drupal/issues/3377709
    $this->assertValidationErrors([]);
  }

  /**
   * Block names are atypical in that they allow periods in the machine name.
   */
  public function providerInvalidMachineNameCharacters(): array {
    $cases = parent::providerInvalidMachineNameCharacters();
    // Remove the existing test case that verifies a machine name containing
    // periods is invalid.
    $this->assertSame(['period.separated', FALSE], $cases['INVALID: period separated']);
    unset($cases['INVALID: period separated']);
    // And instead add a test case that verifies it is allowed for blocks.
    $cases['VALID: period separated'] = ['period.separated', TRUE];
    return $cases;
  }

}
