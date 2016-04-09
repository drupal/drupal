<?php

namespace Drupal\node\Tests;

use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests node field overrides.
 *
 * @group node
 */
class NodeFieldOverridesTest extends EntityUnitTestBase {

  /**
   * Current logged in user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('user', 'system', 'field', 'node');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(array('user'));
    $this->user = $this->createUser();
    \Drupal::service('current_user')->setAccount($this->user);
  }

  /**
   * Tests that field overrides work as expected.
   */
  public function testFieldOverrides() {
    if (!NodeType::load('ponies')) {
      NodeType::create(['name' => 'Ponies', 'type' => 'ponies'])->save();
    }
    $override = BaseFieldOverride::loadByName('node', 'ponies', 'uid');
    if ($override) {
      $override->delete();
    }
    $uid_field = \Drupal::entityManager()->getBaseFieldDefinitions('node')['uid'];
    $config = $uid_field->getConfig('ponies');
    $config->save();
    $this->assertEqual($config->get('default_value_callback'), 'Drupal\node\Entity\Node::getCurrentUserId');
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create(['type' => 'ponies']);
    $owner = $node->getOwner();
    $this->assertTrue($owner instanceof \Drupal\user\UserInterface);
    $this->assertEqual($owner->id(), $this->user->id());
  }

}
