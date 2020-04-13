<?php

namespace Drupal\Tests\user\Kernel;

use Drupal\block\Entity\Block;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the Who's Online Block.
 *
 * @group user
 */
class WhosOnlineBlockTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'block', 'views'];

  /**
   * The block being tested.
   *
   * @var \Drupal\block\Entity\BlockInterface
   */
  protected $block;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $controller;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'block', 'views', 'user']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');

    $this->controller = $this->container
      ->get('entity_type.manager')
      ->getStorage('block');

    // Create a block with only required values.
    $this->block = $this->controller->create([
      'plugin' => 'views_block:who_s_online-who_s_online_block',
      'region' => 'sidebar_first',
      'id' => 'views_block__who_s_online_who_s_online_block',
      'theme' => \Drupal::configFactory()->get('system.theme')->get('default'),
      'label' => "Who's online",
      'visibility' => [],
      'weight' => 0,
    ]);
    $this->block->save();

    $this->container->get('cache.render')->deleteAll();
    $this->renderer = $this->container->get('renderer');
  }

  /**
   * Test the Who's Online block.
   */
  public function testWhosOnlineBlock() {
    $request_time = \Drupal::time()->getRequestTime();
    // Generate users.
    $user1 = User::create([
      'name' => 'user1',
      'mail' => 'user1@example.com',
    ]);
    $user1->addRole('administrator');
    $user1->activate();
    $user1->setLastAccessTime($request_time);
    $user1->save();

    $user2 = User::create([
      'name' => 'user2',
      'mail' => 'user2@example.com',
    ]);
    $user2->activate();
    $user2->setLastAccessTime($request_time + 1);
    $user2->save();

    $user3 = User::create([
      'name' => 'user3',
      'mail' => 'user2@example.com',
    ]);
    $user3->activate();
    // Insert an inactive user who should not be seen in the block.
    $inactive_time = $request_time - (60 * 60);
    $user3->setLastAccessTime($inactive_time);
    $user3->save();

    // Test block output.
    \Drupal::currentUser()->setAccount($user1);

    // Test the rendering of a block.
    $entity = Block::load('views_block__who_s_online_who_s_online_block');
    $output = \Drupal::entityTypeManager()
      ->getViewBuilder($entity->getEntityTypeId())
      ->view($entity, 'block');
    $this->setRawContent($this->renderer->renderRoot($output));
    $this->assertRaw('2 users', 'Correct number of online users (2 users).');
    $this->assertText($user1->getAccountName(), 'Active user 1 found in online list.');
    $this->assertText($user2->getAccountName(), 'Active user 2 found in online list.');
    $this->assertNoText($user3->getAccountName(), 'Inactive user not found in online list.');
    $this->assertTrue(strpos($this->getRawContent(), $user1->getAccountName()) > strpos($this->getRawContent(), $user2->getAccountName()), 'Online users are ordered correctly.');
  }

}
