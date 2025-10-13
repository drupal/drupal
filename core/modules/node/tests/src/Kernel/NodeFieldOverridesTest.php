<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Kernel;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests node field overrides.
 */
#[Group('node')]
#[RunTestsInSeparateProcesses]
class NodeFieldOverridesTest extends EntityKernelTestBase {

  /**
   * Current logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'system', 'field', 'node'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['user']);
    $this->user = $this->createUser();
    \Drupal::service('current_user')->setAccount($this->user);
  }

  /**
   * Tests that field overrides work as expected.
   */
  public function testFieldOverrides(): void {
    if (!NodeType::load('ponies')) {
      NodeType::create(['name' => 'Ponies', 'type' => 'ponies'])->save();
    }
    $override = BaseFieldOverride::loadByName('node', 'ponies', 'uid');
    if ($override) {
      $override->delete();
    }
    $uid_field = \Drupal::service('entity_field.manager')->getBaseFieldDefinitions('node')['uid'];
    $config = $uid_field->getConfig('ponies');
    $config->save();
    $this->assertEquals('Drupal\node\Entity\Node::getDefaultEntityOwner', $config->get('default_value_callback'));
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create(['type' => 'ponies']);
    $owner = $node->getOwner();
    $this->assertInstanceOf(UserInterface::class, $owner);
    $this->assertEquals($this->user->id(), $owner->id());
  }

}
