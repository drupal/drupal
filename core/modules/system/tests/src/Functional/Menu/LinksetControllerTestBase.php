<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Menu;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\Tests\ApiRequestTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\UserInterface;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;

/**
 * A base class for implementing LinksetController tests.
 *
 * Provides general purpose helper methods that are commonly needed
 * when writing LinksetController tests.
 * - Perform request against the linkset endpoint.
 * - Create Menu items.
 *
 * For a full list, refer to the methods of this class.
 *
 * @group decoupled_menus
 *
 * @see https://tools.ietf.org/html/draft-ietf-httpapi-linkset-00
 */
abstract class LinksetControllerTestBase extends BrowserTestBase {

  use ApiRequestTrait;
  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'basic_auth',
    'link',
    'path_alias',
    'path',
    'user',
    'menu_link_content',
    'node',
    'page_cache',
    'dynamic_page_cache',
  ];

  /**
   * Sends a request to the kernel and makes basic response assertions.
   *
   * Only to be used when the expected response is a linkset response.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param int $expected_status
   *   The expected status code.
   * @param \Drupal\user\UserInterface $account
   *   A user account whose credentials should be used to authenticate the
   *   request.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   The response object.
   */
  protected function doRequest(string $method, Url $url, $expected_status = 200, ?UserInterface $account = NULL): Response {
    $request_options = [];
    if (!is_null($account)) {
      $credentials = $account->name->value . ':' . $account->passRaw;
      $request_options[RequestOptions::HEADERS] = [
        'Authorization' => 'Basic ' . base64_encode($credentials),
      ];
    }
    $response = $this->makeApiRequest($method, $url, $request_options);
    $this->assertSame($expected_status, $response->getStatusCode(), (string) $response->getBody());
    return $response;
  }

  /**
   * Helper to assert a cacheable value matches an expectation.
   *
   * @param string|false $expect_cache
   *   'HIT', 'MISS', or FALSE. Asserts the value of the X-Drupal-Cache header.
   *   FALSE if the page cache is not applicable.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $expected_metadata
   *   The expected cacheability metadata.
   * @param \GuzzleHttp\Psr7\Response $response
   *   The response on which to assert cacheability.
   */
  protected function assertDrupalResponseCacheability($expect_cache, CacheableDependencyInterface $expected_metadata, Response $response) {
    $this->assertTrue(in_array($expect_cache, ['HIT', 'MISS', FALSE], TRUE), 'Cache is HIT, MISS, FALSE.');
    $this->assertSame($expected_metadata->getCacheContexts(), explode(' ', $response->getHeaderLine('X-Drupal-Cache-Contexts')));
    $this->assertSame($expected_metadata->getCacheTags(), explode(' ', $response->getHeaderLine('X-Drupal-Cache-Tags')));
    $max_age_message = $expected_metadata->getCacheMaxAge();
    if ($max_age_message === 0) {
      $max_age_message = '0 (Uncacheable)';
    }
    elseif ($max_age_message === -1) {
      $max_age_message = '-1 (Permanent)';
    }
    $this->assertSame($max_age_message, $response->getHeaderLine('X-Drupal-Cache-Max-Age'));
    if ($expect_cache) {
      $this->assertSame($expect_cache, $response->getHeaderLine('X-Drupal-Cache'));
    }
  }

  /**
   * Creates, saves, and returns a new menu link content entity.
   *
   * @param array $values
   *   Menu field values.
   * @param array $options
   *   Menu options.
   *
   * @return \Drupal\menu_link_content\MenuLinkContentInterface
   *   The newly created menu link content entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @see \Drupal\menu_link_content\MenuLinkContentInterface::create()
   */
  protected function createMenuItem(array $values, array $options = []): MenuLinkContentInterface {
    if (!empty($options)) {
      $values['link'] = ['uri' => $values['link'], 'options' => $options];
    }
    $link_content = MenuLinkContent::create($values);
    assert($link_content instanceof MenuLinkContentInterface);
    $link_content->save();
    return $link_content;
  }

  /**
   * Enables or disables the menu linkset endpoint.
   *
   * @param bool $enabled
   *   Whether the endpoint should be enabled.
   */
  protected function enableEndpoint(bool $enabled) {
    $this->config('system.feature_flags')
      ->set('linkset_endpoint', $enabled)
      ->save(TRUE);
    // Using rebuildIfNeeded here to implicitly test that router is only rebuilt
    // when necessary.
    \Drupal::service('router.builder')->rebuildIfNeeded();
  }

  /**
   * Retrieve reference linkset controller output adjusted for proper base URL.
   *
   * @param string $filename
   *   Name of the file to read.
   *
   * @return mixed
   *   The Json representation of the reference data in the file.
   */
  protected function getReferenceLinksetDataFromFile(string $filename) {
    $data = Json::decode(file_get_contents($filename));
    // Ensure that the URLs are correct if Drupal is being served from a
    // subdirectory.
    $data['linkset'][0]['anchor'] = Url::fromUri('base:' . $data['linkset'][0]['anchor'])->toString();
    foreach ($data['linkset'][0]['item'] as &$item) {
      $item['href'] = Url::fromUri('base:' . $item['href'])->toString();
    }
    return $data;
  }

  /**
   * Rebuild the router only if needed.
   */
  public function rebuildIfNeeded() {
    /** @var \Drupal\Core\Routing\RouteBuilderInterface $router_builder */
    $router_builder = $this->container->get('router.builder');
    $router_builder->rebuildIfNeeded();
  }

}
