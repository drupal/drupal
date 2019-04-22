<?php

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\BasicAuthResourceTestTrait;
use Drupal\Tests\rest\Functional\ResourceTestBase;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Base class for Layout Builder REST tests.
 */
abstract class LayoutRestTestBase extends ResourceTestBase {

  use BasicAuthResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'layout_builder',
    'serialization',
    'basic_auth',
  ];

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $assert_session = $this->assertSession();

    $this->createContentType(['type' => 'bundle_with_section_field']);

    $this->drupalLogin($this->drupalCreateUser([
      'configure any layout',
      'administer node display',
      'administer display modes',
      'bypass node access',
      'create bundle_with_section_field content',
      'edit any bundle_with_section_field content',
    ]));
    $page = $this->getSession()->getPage();
    $field_ui_prefix = 'admin/structure/types/manage/bundle_with_section_field/display';

    // Enable Layout Builder for the default view modes, and overrides.
    $this->drupalGet("$field_ui_prefix/default");
    $page->checkField('layout[enabled]');
    $page->pressButton('Save');
    $page->checkField('layout[allow_custom]');
    $page->pressButton('Save');

    // Create a node.
    $this->node = $this->createNode([
      'type' => 'bundle_with_section_field',
      'title' => 'A node at rest will stay at rest.',
    ]);

    $this->drupalGet('node/' . $this->node->id() . '/layout');
    $page->clickLink('Add block');
    $page->clickLink('Powered by Drupal');
    $page->fillField('settings[label]', 'This is an override');
    $page->checkField('settings[label_display]');
    $page->pressButton('Add block');
    $page->pressButton('Save layout');
    $assert_session->pageTextContains('This is an override');

    $this->nodeStorage = $this->container->get('entity_type.manager')->getStorage('node');
    $this->node = $this->nodeStorage->load($this->node->id());

    $this->drupalLogout();
    $this->setUpAuthorization('ALL');

    $this->provisionResource([static::$format], ['basic_auth']);
  }

  /**
   * {@inheritdoc}
   */
  protected function request($method, Url $url, array $request_options = []) {
    $request_options[RequestOptions::HEADERS] = [
      'Content-Type' => static::$mimeType,
    ];
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions($method));
    $request_options[RequestOptions::QUERY] = ['_format' => static::$format];
    return parent::request($method, $url, $request_options);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $permissions = array_keys($this->container->get('user.permissions')->getPermissions());
    // Give the test user all permissions on the site. There should be no
    // permission that gives the user access to layout sections over REST.
    $this->account = $this->drupalCreateUser($permissions);
  }

  /**
   * {@inheritdoc}
   */
  protected function assertResponseWhenMissingAuthentication($method, ResponseInterface $response) {}

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function assertAuthenticationEdgeCases($method, Url $url, array $request_options) {}

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {}

  /**
   * Gets the decoded contents.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response.
   *
   * @return array
   *   The decoded contents.
   */
  protected function getDecodedContents(ResponseInterface $response) {
    return $this->serializer->decode((string) $response->getBody(), static::$format);
  }

}
