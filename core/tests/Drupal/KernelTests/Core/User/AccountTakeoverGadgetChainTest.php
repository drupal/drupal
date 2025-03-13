<?php

declare(strict_types=1);

// cSpell:ignore phpggc, topsecret

namespace Drupal\KernelTests\Core\User;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests protection against SA-CORE-2024-007 Gadget Chain.
 *
 * @group user
 */
class AccountTakeoverGadgetChainTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field',
    'node',
    'text',
    'user',
    'system',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Views and some of its requirements need to be at least partially set up
    // in order for the payload to work.
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('view');
    $this->installConfig(['node']);
  }

  /**
   * Tests unserializing a SQLi / account takeover payload.
   */
  public function testAccountTakeoverGadgetChain(): void {
    $this->createUser([], 'topsecret', FALSE, ['uid' => 1]);
    // ./phpggc --public-properties Drupal/AT1 'evil@example.com'
    $payload = 'O:27:"Drupal\views\ViewExecutable":6:{s:17:"serializationData";a:9:{s:8:"executed";b:1;s:7:"storage";s:9:"frontpage";s:15:"current_display";s:7:"default";s:4:"args";a:0:{}s:12:"current_page";s:0:"";s:13:"exposed_input";s:0:"";s:12:"exposed_data";s:0:"";s:17:"exposed_raw_input";s:0:"";s:6:"dom_id";s:0:"";}s:5:"built";b:1;s:12:"live_preview";b:1;s:5:"query";O:33:"Drupal\Core\Database\Query\Update":8:{s:16:"connectionTarget";s:7:"default";s:13:"connectionKey";s:7:"default";s:12:"queryOptions";a:0:{}s:16:"uniqueIdentifier";s:23:"67b85459508987.47505064";s:15:"nextPlaceholder";i:0;s:5:"table";s:16:"users_field_data";s:6:"fields";a:3:{s:4:"mail";s:16:"evil@example.com";s:4:"name";s:5:"admin";s:6:"status";i:1;}s:9:"condition";O:36:"Drupal\Core\Database\Query\Condition":5:{s:10:"conditions";a:2:{s:12:"#conjunction";s:3:"AND";i:0;a:3:{s:5:"field";s:3:"uid";s:5:"value";i:1;s:8:"operator";s:1:"=";}}s:9:"arguments";a:0:{}s:7:"changed";b:1;s:26:"queryPlaceholderIdentifier";N;s:13:"stringVersion";N;}}s:15:"displayHandlers";O:36:"Drupal\views\DisplayPluginCollection":0:{}s:15:"display_handler";O:48:"Drupal\views\Plugin\views\display\DefaultDisplay":0:{}}';

    try {
      unserialize($payload);
      $this->fail('No exception was thrown');
    }
    catch (\Throwable $e) {
      $this->assertInstanceOf(\TypeError::class, $e);
      $this->assertStringContainsString('Cannot assign Drupal\Core\Database\Query\Update to property Drupal\views\ViewExecutable::$query', $e->getMessage());
    }
    $admin = User::load(1);
    $this->assertEquals('topsecret@example.com', $admin->getEmail());
  }

}
