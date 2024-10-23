<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "MenuLinkContent" content entity type.
 *
 * @group jsonapi
 */
class MenuLinkContentTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_link_content'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'menu_link_content';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'menu_link_content--menu_link_content';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeIsVersionable = TRUE;

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\menu_link_content\MenuLinkContentInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    $this->grantPermissionsToTestedRole(['administer menu']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $menu_link = MenuLinkContent::create([
      'id' => 'llama',
      'title' => 'Llama Gabilondo',
      'description' => 'Llama Gabilondo',
      'link' => 'https://nl.wikipedia.org/wiki/Llama',
      'weight' => 0,
      'menu_name' => 'main',
    ]);
    $menu_link->save();

    return $menu_link;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $base_url = Url::fromUri('base:/jsonapi/menu_link_content/menu_link_content/' . $this->entity->uuid())->setAbsolute();
    $self_url = clone $base_url;
    $version_identifier = 'id:' . $this->entity->getRevisionId();
    $self_url = $self_url->setOption('query', ['resourceVersion' => $version_identifier]);
    $version_query_string = '?resourceVersion=' . urlencode($version_identifier);
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
        'self' => ['href' => $base_url->toString()],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'menu_link_content--menu_link_content',
        'links' => [
          'self' => ['href' => $self_url->toString()],
        ],
        'attributes' => [
          'bundle' => 'menu_link_content',
          'link' => [
            'uri' => 'https://nl.wikipedia.org/wiki/Llama',
            'title' => NULL,
            'options' => [],
          ],
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'default_langcode' => TRUE,
          'description' => 'Llama Gabilondo',
          'enabled' => TRUE,
          'expanded' => FALSE,
          'external' => FALSE,
          'langcode' => 'en',
          'menu_name' => 'main',
          'parent' => NULL,
          'rediscover' => FALSE,
          'title' => 'Llama Gabilondo',
          'weight' => 0,
          'drupal_internal__id' => 1,
          'drupal_internal__revision_id' => 1,
          'revision_created' => (new \DateTime())->setTimestamp((int) $this->entity->getRevisionCreationTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'revision_log_message' => NULL,
          // @todo Attempt to remove this in https://www.drupal.org/project/drupal/issues/2933518.
          'revision_translation_affected' => TRUE,
        ],
        'relationships' => [
          'revision_user' => [
            'data' => NULL,
            'links' => [
              'related' => [
                'href' => $base_url->toString() . '/revision_user' . $version_query_string,
              ],
              'self' => [
                'href' => $base_url->toString() . '/relationships/revision_user' . $version_query_string,
              ],
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
        'type' => 'menu_link_content--menu_link_content',
        'attributes' => [
          'title' => 'Drama llama',
          'link' => [
            'uri' => 'http://www.urbandictionary.com/define.php?term=drama%20llama',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'DELETE':
        return "The 'administer menu' permission is required.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess(): void {
    $this->doTestCollectionFilterAccessBasedOnPermissions('title', 'administer menu');
  }

  /**
   * Tests requests using a serialized field item property.
   *
   * @see https://security.drupal.org/node/161923
   */
  public function testLinkOptionsSerialization(): void {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $document = $this->getPostDocument();
    $document['data']['attributes']['link']['options'] = "O:44:\"Symfony\\Component\\Process\\Pipes\\WindowsPipes\":8:{s:51:\"\\Symfony\\Component\\Process\\Pipes\\WindowsPipes\0files\";a:1:{i:0;s:3:\"foo\";}s:57:\"\0Symfony\\Component\\Process\\Pipes\\WindowsPipes\0fileHandles\";a:0:{}s:55:\"\0Symfony\\Component\\Process\\Pipes\\WindowsPipes\0readBytes\";a:2:{i:1;i:0;i:2;i:0;}s:59:\"\0Symfony\\Component\\Process\\Pipes\\WindowsPipes\0disableOutput\";b:0;s:5:\"pipes\";a:0:{}s:58:\"\0Symfony\\Component\\Process\\Pipes\\AbstractPipes\0inputBuffer\";s:0:\"\";s:52:\"\0Symfony\\Component\\Process\\Pipes\\AbstractPipes\0input\";N;s:54:\"\0Symfony\\Component\\Process\\Pipes\\AbstractPipes\0blocked\";b:1;}";
    $url = Url::fromRoute(sprintf('jsonapi.%s.collection.post', static::$resourceTypeName));
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::BODY] = Json::encode($document);
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // Ensure 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('POST');
    $this->assertResourceErrorResponse(403, (string) $reason, $url, $response);

    $this->setUpAuthorization('POST');

    // Ensure that an exception is thrown.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(500, (string) 'The generic FieldItemNormalizer cannot denormalize string values for "options" properties of the "link" field (field item class: Drupal\link\Plugin\Field\FieldType\LinkItem).', $url, $response);

    // Create a menu link content entity without the serialized property.
    unset($document['data']['attributes']['link']['options']);
    $request_options[RequestOptions::BODY] = Json::encode($document);
    $response = $this->request('POST', $url, $request_options);
    $document = $this->getDocumentFromResponse($response);
    $internal_id = $document['data']['attributes']['drupal_internal__id'];

    // Load the created menu item and add link options to it.
    $menu_link = MenuLinkContent::load($internal_id);
    $menu_link->get('link')->first()->set('options', ['fragment' => 'test']);
    $menu_link->save();

    // Fetch the link.
    unset($request_options[RequestOptions::BODY]);
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $document['data']['id']]);
    $response = $this->request('GET', $url, $request_options);
    $response_body = (string) $response->getBody();

    // Ensure that the entity can be updated using a response document.
    $request_options[RequestOptions::BODY] = $response_body;
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, Json::decode($response_body), $response);
  }

}
