<?php

declare(strict_types=1);

namespace Drupal\Tests\announcements_feed\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\announcements_feed\AnnounceFetcher;

/**
 * Simple test to ensure that asserts pass.
 *
 * @group announcements_feed
 */
class AnnounceFetcherUnitTest extends UnitTestCase {

  /**
   * The Fetcher service object.
   *
   * @var \Drupal\announcements_feed\AnnounceFetcher
   */
  protected AnnounceFetcher $fetcher;

  /**
   * {@inheritdoc}
   */
  public function setUp():void {
    parent::setUp();
    $httpClient = $this->createMock('GuzzleHttp\ClientInterface');
    $config = $this->getConfigFactoryStub([
      'announcements_feed.settings' => [
        'max_age' => 86400,
        'cron_interval' => 21600,
        'limit' => 10,
      ],
    ]);
    $tempStore = $this->createMock('Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface');
    $tempStore->expects($this->once())
      ->method('get')
      ->willReturn($this->createMock('Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface'));

    $logger = $this->createMock('Psr\Log\LoggerInterface');
    $this->fetcher = new AnnounceFetcher($httpClient, $config, $tempStore, $logger, 'https://www.drupal.org/announcements.json');
  }

  /**
   * Test the ValidateUrl() method.
   *
   * @covers \Drupal\announcements_feed\AnnounceFetcher::validateUrl
   *
   * @dataProvider urlProvider
   */
  public function testValidateUrl($url, $isValid): void {
    $this->assertEquals($isValid, $this->fetcher->validateUrl($url));
  }

  /**
   * Data for the testValidateUrl.
   */
  public static function urlProvider(): array {
    return [
      ['https://www.drupal.org', TRUE],
      ['https://drupal.org', TRUE],
      ['https://api.drupal.org', TRUE],
      ['https://a.drupal.org', TRUE],
      ['https://123.drupal.org', TRUE],
      ['https://api-new.drupal.org', TRUE],
      ['https://api_new.drupal.org', TRUE],
      ['https://api-.drupal.org', TRUE],
      ['https://www.example.org', FALSE],
      ['https://example.org', FALSE],
      ['https://api.example.org/project/announce', FALSE],
      ['https://-api.drupal.org', FALSE],
      ['https://a.example.org/project/announce', FALSE],
      ['https://test.drupaal.com', FALSE],
      ['https://api.drupal.org.example.com', FALSE],
      ['https://example.org/drupal.org', FALSE],
    ];
  }

}
