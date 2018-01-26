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
  public static $modules = ['system', 'user', 'block', 'views'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['system', 'block', 'views', 'user']);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');

  }

  /**
   * Test the Who's Online block.
   */
  public function testWhosOnlineBlock() {
    // Generate users.
    $user1 = User::create([
      'name' => 'user1',
      'mail' => 'user1@example.com',
    ]);
    $user1->addRole('administrator');
    $user1->activate();
    $user1->setLastAccessTime(\Drupal::time()->getRequestTime());
    $user1->save();

    $user2 = User::create([
      'name' => 'user2',
      'mail' => 'user2@example.com',
    ]);
    $user2->activate();
    $user2->setLastAccessTime(\Drupal::time()->getRequestTime() + 1);
    $user2->save();

    $user3 = User::create([
      'name' => 'user3',
      'mail' => 'user2@example.com',
    ]);
    $user3->activate();
    // Insert an inactive user who should not be seen in the block.
    $inactive_time = \Drupal::time()->getRequestTime() - (15 * 60) - 1;
    $user3->setLastAccessTime($inactive_time);
    $user3->save();

    // Test block output.
    \Drupal::currentUser()->setAccount($user1);
    // Create a block with only required values.
    $block = Block::create([
      'plugin' => 'views_block:who_s_online-who_s_online_block',
      'region' => 'sidebar_first',
      'id' => 'views_block__who_s_online_who_s_online_block',
      'theme' => \Drupal::configFactory()->get('system.theme')->get('default'),
      'label' => "Who's online",
      'visibility' => [],
      'weight' => 0,
    ]);
    $block->save();
    $this->container->get('cache.render')->deleteAll();
    $render_controller = \Drupal::entityTypeManager()->getViewBuilder($block->getEntityTypeId());
    $content = $render_controller->view($block, 'block');
    $this->setRawContent($this->render($content));
    $this->assertRaw('2 users', 'Correct number of online users (2 users).');
    $this->assertText($user1->getUsername(), 'Active user 1 found in online list.');
    $this->assertText($user2->getUsername(), 'Active user 2 found in online list.');
    $this->assertNoText($user3->getUsername(), 'Inactive user not found in online list.');
    $this->assertTrue(strpos($this->getRawContent(), $user1->getUsername()) > strpos($this->getRawContent(), $user2->getUsername()), 'Online users are ordered correctly.');
  }

}
