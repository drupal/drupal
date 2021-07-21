<?php

namespace Drupal\KernelTests\Core;

use Drupal\KernelTests\KernelTestBase;
use Laminas\Feed\Reader\Extension\AbstractEntry;

/**
 * Tests deprecated core services.
 *
 * @group Core
 * @group legacy
 */
class LegacyServiceTest extends KernelTestBase {

  /**
   * Tests the site.path service.
   */
  public function testSitePath() {
    $this->expectDeprecation('The "site.path" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the site.path parameter instead. See https://www.drupal.org/node/3080612');
    $this->expectDeprecation('The "site.path.factory" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the site.path parameter instead. See https://www.drupal.org/node/3080612');
    $this->assertSame($this->container->get('site.path'), (string) $this->container->getParameter('site.path'));
  }

  /**
   * Tests the app.root service.
   */
  public function testAppRoot() {
    $this->expectDeprecation('The "app.root" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612');
    $this->expectDeprecation('The "app.root.factory" service is deprecated in drupal:9.0.0 and is removed from drupal:10.0.0. Use the app.root parameter instead. See https://www.drupal.org/node/3080612');
    $this->assertSame($this->container->get('app.root'), (string) $this->container->getParameter('app.root'));
  }

  /**
   * Tests the feed.reader.dublincoreentry service.
   */
  public function testFeedReaderDublinCoreEntry() {
    $this->expectDeprecation('The "feed.reader.dublincoreentry" service is deprecated. You should use \Drupal::service(\'feed.bridge.reader\')->get(\'DublinCore\Entry\') instead. See https://www.drupal.org/node/2979042');
    $this->assertInstanceOf(AbstractEntry::class, $this->container->get('feed.reader.dublincoreentry'));
  }

}
