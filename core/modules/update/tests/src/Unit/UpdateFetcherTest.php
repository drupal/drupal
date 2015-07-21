<?php

/**
 * @file
 * Contains \Drupal\Tests\update\Unit\UpdateFetcherTest.
 */

namespace Drupal\Tests\update\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\update\UpdateFetcher;

if (!defined('DRUPAL_CORE_COMPATIBILITY')) {
  define('DRUPAL_CORE_COMPATIBILITY', '8.x');
}

/**
 * Tests update functionality unrelated to the database.
 *
 * @group update
 */
class UpdateFetcherTest extends UnitTestCase {

  /**
   * The update fetcher to use.
   *
   * @var \Drupal\update\UpdateFetcher
   */
  protected $updateFetcher;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $config_factory = $this->getConfigFactoryStub(array('update.settings' => array('fetch_url' => 'http://www.example.com')));
    $http_client_mock = $this->getMock('\GuzzleHttp\ClientInterface');
    $this->updateFetcher = new UpdateFetcher($config_factory, $http_client_mock);
  }

  /**
   * Tests that buildFetchUrl() builds the URL correctly.
   *
   * @param array $project
   *   A keyed array of project information matching results from
   *   \Drupal\Update\UpdateManager::getProjects().
   * @param string $site_key
   *   A string to mimic an anonymous site key hash.
   * @param string $expected
   *   The expected url returned from UpdateFetcher::buildFetchUrl()
   *
   * @dataProvider providerTestUpdateBuildFetchUrl
   *
   * @see \Drupal\update\UpdateFetcher::buildFetchUrl()
   */
  public function testUpdateBuildFetchUrl(array $project, $site_key, $expected) {
    $url = $this->updateFetcher->buildFetchUrl($project, $site_key);
    $this->assertEquals($url, $expected);
  }

  /**
   * Provide test data for self::testUpdateBuildFetchUrl().
   *
   * @return array
   *   An array of arrays, each containing:
   *   - 'project' - An array matching a project's .info file structure.
   *   - 'site_key' - An arbitrary site key.
   *   - 'expected' - The expected url from UpdateFetcher::buildFetchUrl().
   */
  public function providerTestUpdateBuildFetchUrl() {
    $data = array();

    // First test that we didn't break the trivial case.
    $project['name'] = 'update_test';
    $project['project_type'] = '';
    $project['info']['version'] = '';
    $project['info']['project status url'] = 'http://www.example.com';
    $project['includes'] = array('module1' => 'Module 1', 'module2' => 'Module 2');
    $site_key = '';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . DRUPAL_CORE_COMPATIBILITY;

    $data[] = array($project, $site_key, $expected);

    // For disabled projects it shouldn't add the site key either.
    $site_key = 'site_key';
    $project['project_type'] = 'disabled';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . DRUPAL_CORE_COMPATIBILITY;

    $data[] = array($project, $site_key, $expected);

    // For enabled projects, test adding the site key.
    $project['project_type'] = '';
    $expected = 'http://www.example.com/' . $project['name'] . '/' . DRUPAL_CORE_COMPATIBILITY;
    $expected .= '?site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');

    $data[] = array($project, $site_key, $expected);

    // Test when the URL contains a question mark.
    $project['info']['project status url'] = 'http://www.example.com/?project=';
    $expected = 'http://www.example.com/?project=/' . $project['name'] . '/' . DRUPAL_CORE_COMPATIBILITY;
    $expected .= '&site_key=site_key';
    $expected .= '&list=' . rawurlencode('module1,module2');

    $data[] = array($project, $site_key, $expected);

    return $data;
  }

}
