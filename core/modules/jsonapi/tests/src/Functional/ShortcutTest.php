<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\shortcut\Entity\ShortcutSet;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "Shortcut" content entity type.
 *
 * @group jsonapi
 */
class ShortcutTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['comment', 'shortcut'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'shortcut';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'shortcut--default';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\shortcut\ShortcutInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['access shortcuts', 'customize shortcut links']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => 'Comments',
      'weight' => -20,
      'link' => [
        'uri' => 'internal:/user/logout',
      ],
    ]);
    $shortcut->save();

    return $shortcut;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/shortcut/default/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'shortcut--default',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'title' => 'Comments',
          'link' => [
            'uri' => 'internal:/user/logout',
            'title' => NULL,
            'options' => [],
          ],
          'langcode' => 'en',
          'default_langcode' => TRUE,
          'weight' => -20,
          'drupal_internal__id' => (int) $this->entity->id(),
        ],
        'relationships' => [
          'shortcut_set' => [
            'data' => [
              'type' => 'shortcut_set--shortcut_set',
              'meta' => [
                'drupal_internal__target_id' => 'default',
              ],
              'id' => ShortcutSet::load('default')->uuid(),
            ],
            'links' => [
              'related' => ['href' => $self_url . '/shortcut_set'],
              'self' => ['href' => $self_url . '/relationships/shortcut_set'],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument(): array {
    return [
      'data' => [
        'type' => 'shortcut--default',
        'attributes' => [
          'title' => 'Comments',
          'link' => [
            'uri' => 'internal:/',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method): string {
    return "The shortcut set must be the currently displayed set for the user and the user must have 'access shortcuts' AND 'customize shortcut links' permissions.";
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess(): void {
    $label_field_name = 'title';
    // Verify the expected behavior in the common case: default shortcut set.
    $this->grantPermissionsToTestedRole(['customize shortcut links']);
    $this->doTestCollectionFilterAccessBasedOnPermissions($label_field_name, 'access shortcuts');

    $alternate_shortcut_set = ShortcutSet::create([
      'id' => 'alternate',
      'label' => 'Alternate',
    ]);
    $alternate_shortcut_set->save();
    $this->entity->shortcut_set = $alternate_shortcut_set->id();
    $this->entity->save();

    $collection_url = Url::fromRoute('jsonapi.entity_test--bar.collection');
    $collection_filter_url = $collection_url->setOption('query', ["filter[spotlight.$label_field_name]" => $this->entity->label()]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // No results because the current user does not have access to shortcuts
    // not in the user's assigned set or the default set.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = $this->getDocumentFromResponse($response);
    $this->assertCount(0, $doc['data']);

    // Assign the alternate shortcut set to the current user.
    $this->container->get('entity_type.manager')->getStorage('shortcut_set')->assignUser($alternate_shortcut_set, $this->account);

    // 1 result because the alternate shortcut set is now assigned to the
    // current user.
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = $this->getDocumentFromResponse($response);
    $this->assertCount(1, $doc['data']);
  }

  /**
   * {@inheritdoc}
   */
  protected static function getExpectedCollectionCacheability(AccountInterface $account, array $collection, ?array $sparse_fieldset = NULL, $filtered = FALSE) {
    $cacheability = parent::getExpectedCollectionCacheability($account, $collection, $sparse_fieldset, $filtered);
    if ($filtered) {
      $cacheability->addCacheContexts(['user']);
    }
    return $cacheability;
  }

}
