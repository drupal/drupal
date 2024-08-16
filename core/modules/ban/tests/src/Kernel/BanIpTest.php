<?php

declare(strict_types=1);

namespace Drupal\Tests\ban\Kernel;

use Drupal\ban\BanIpManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\KernelTests\KernelTestBase;

/**
 * @group ban
 */
class BanIpTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ban'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('ban', ['ban_ip']);
  }

  /**
   * Test banning IPs.
   */
  public function testBanIp(): void {
    $banIp = $this->container->get(BanIpManagerInterface::class);

    // Test valid IP addresses.
    $ip = '1.2.3.3';
    $this->assertCount(0, $this->getIpBans($ip));
    $banIp->banIp($ip);
    $this->assertCount(1, $this->getIpBans($ip));

    // Test duplicate ip address are not present in the 'blocked_ips' table
    // when they are entered programmatically.
    $ip = '1.0.0.0';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $this->assertCount(1, $this->getIpBans($ip));

    $ip = '';
    $banIp->banIp($ip);
    $banIp->banIp($ip);
    $this->assertCount(1, $this->getIpBans($ip));
  }

  /**
   * Gets the IP bans.
   */
  protected function getIpBans(string $ip): array {
    $connection = $this->container->get(Connection::class);
    $query = $connection->select('ban_ip', 'bip');
    $query->fields('bip', ['iid']);
    $query->condition('bip.ip', $ip);
    return $query->execute()->fetchAll();
  }

}
