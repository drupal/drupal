<?php

namespace Drupal\KernelTests\Core;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests deprecated core services.
 *
 * @group Core
 * @group legacy
 */
class LegacyServiceTest extends KernelTestBase {

  /**
   * Tests the deprecated Laminas Feed services.
   *
   * @dataProvider providerLaminasFeedServices
   */
  public function testLaminasFeedServices($type, $service, $class) {
    $service = "feed.$type.$service";
    $this->expectDeprecation("The \"$service\" service is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. You should use \\Drupal::service('feed.bridge.$type')->get('$class') instead. See https://www.drupal.org/node/2979042");
    $this->assertIsObject($this->container->get($service));
  }

  /**
   * Tests the deprecated Laminas bridge service.
   */
  public function testLaminasBridgeService() {
    $this->expectDeprecation("The \"feed.bridge.reader\" service is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Laminas\Feed\Reader\StandaloneExtensionManager or create your own service. See https://www.drupal.org/node/3258656");
    $this->expectDeprecation("The \"feed.bridge.writer\" service is deprecated in drupal:9.4.0 and is removed from drupal:10.0.0. Use \Laminas\Feed\Writer\StandaloneExtensionManager or create your own service. See https://www.drupal.org/node/3258440");
    $this->assertIsObject($this->container->get('feed.bridge.reader'));
    $this->assertIsObject($this->container->get('feed.bridge.writer'));
  }

  public function providerLaminasFeedServices() {
    return [
      ['reader', 'dublincoreentry', 'DublinCore\Entry'],
      ['reader', 'dublincorefeed', 'DublinCore\Feed'],
      ['reader', 'contententry', 'Content\Entry'],
      ['reader', 'atomentry', 'Atom\Entry'],
      ['reader', 'atomfeed', 'Atom\Feed'],
      ['reader', 'slashentry', 'Slash\Entry'],
      ['reader', 'wellformedwebentry', 'WellFormedWeb\Entry'],
      ['reader', 'threadentry', 'Thread\Entry'],
      ['reader', 'podcastentry', 'Podcast\Entry'],
      ['reader', 'podcastfeed', 'Podcast\Feed'],
      ['writer', 'atomrendererfeed', 'Atom\Renderer\Feed'],
      ['writer', 'contentrendererentry', 'Content\Renderer\Entry'],
      ['writer', 'dublincorerendererentry', 'DublinCore\Renderer\Entry'],
      ['writer', 'dublincorerendererfeed', 'DublinCore\Renderer\Feed'],
      ['writer', 'itunesentry', 'ITunes\Entry'],
      ['writer', 'itunesfeed', 'ITunes\Feed'],
      ['writer', 'itunesrendererentry', 'ITunes\Renderer\Entry'],
      ['writer', 'itunesrendererfeed', 'ITunes\Renderer\Feed'],
      ['writer', 'slashrendererentry', 'Slash\Renderer\Entry'],
      ['writer', 'threadingrendererentry', 'Threading\Renderer\Entry'],
      ['writer', 'wellformedwebrendererentry', 'WellFormedWeb\Renderer\Entry'],
    ];
  }

}
