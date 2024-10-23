<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use Drupal\user\Entity\User;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "File" content entity type.
 *
 * @group jsonapi
 */
class FileTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'file';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'file--file';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\file\FileInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'uri' => NULL,
    'filemime' => NULL,
    'filesize' => NULL,
    'status' => NULL,
    'changed' => NULL,
  ];

  /**
   * The file author.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $author;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method): void {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'PATCH':
        // \Drupal\file\FileAccessControlHandler::checkAccess() grants 'update'
        // access only to the user that owns the file. So there is no permission
        // to grant: instead, the file owner must be changed from its default
        // (user 1) to the current user.
        $this->makeCurrentUserFileOwner();
        return;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete any file']);
        break;
    }
  }

  /**
   * Makes the current user the file owner.
   */
  protected function makeCurrentUserFileOwner(): void {
    $account = User::load(2);
    $this->entity->setOwnerId($account->id());
    $this->entity->setOwner($account);
    $this->entity->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $this->author = User::load(1);

    $file = File::create();
    $file->setOwnerId($this->author->id());
    $file->setFilename('drupal.txt');
    $file->setMimeType('text/plain');
    $file->setFileUri('public://drupal.txt');
    $file->set('status', FileInterface::STATUS_PERMANENT);
    $file->save();

    file_put_contents($file->getFileUri(), 'Drupal');

    return $file;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    /** @var \Drupal\file\FileInterface $duplicate */
    $duplicate = parent::createAnotherEntity($key);
    $duplicate->setFileUri("public://$key.txt");
    $duplicate->save();
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument(): array {
    $self_url = Url::fromUri('base:/jsonapi/file/file/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'file--file',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'created' => (new \DateTime())->setTimestamp($this->entity->getCreatedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'filemime' => 'text/plain',
          'filename' => 'drupal.txt',
          'filesize' => (int) $this->entity->getSize(),
          'langcode' => 'en',
          'status' => TRUE,
          'uri' => [
            'url' => base_path() . $this->siteDirectory . '/files/drupal.txt',
            'value' => 'public://drupal.txt',
          ],
          'drupal_internal__fid' => 1,
        ],
        'relationships' => [
          'uid' => [
            'data' => [
              'id' => $this->author->uuid(),
              'meta' => [
                'drupal_internal__target_id' => (int) $this->author->id(),
              ],
              'type' => 'user--user',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/uid'],
              'self' => ['href' => $self_url . '/relationships/uid'],
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
        'type' => 'file--file',
        'attributes' => [
          'filename' => 'drupal.txt',
        ],
      ],
    ];
  }

  /**
   * Tests POST/PATCH/DELETE for an individual resource.
   */
  public function testIndividual(): void {
    // @todo https://www.drupal.org/node/1927648
    // Add doTestPostIndividual().
    $this->doTestPatchIndividual();
    $this->entity = $this->resaveEntity($this->entity, $this->account);
    $this->revokePermissions();
    $this->config('jsonapi.settings')->set('read_only', TRUE)->save(TRUE);
    $this->doTestDeleteIndividual();
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return match($method) {
      'GET' => "The 'access content' permission is required.",
      'PATCH' => "Only the file owner can update the file entity.",
      'DELETE' => "The 'delete any file' permission is required.",
      default =>  parent::getExpectedUnauthorizedAccessMessage($method),
    };
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess(): void {
    $label_field_name = 'filename';
    // Verify the expected behavior in the common case: when the file is public.
    $this->doTestCollectionFilterAccessBasedOnPermissions($label_field_name, 'access content');

    $collection_url = Url::fromRoute('jsonapi.entity_test--bar.collection');
    $collection_filter_url = $collection_url->setOption('query', ["filter[spotlight.$label_field_name]" => $this->entity->label()]);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // 1 result because the current user is the file owner, even though the file
    // is private.
    $this->entity->setFileUri('private://drupal.txt');
    $this->entity->setOwner($this->account);
    $this->entity->save();
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = $this->getDocumentFromResponse($response);
    $this->assertCount(1, $doc['data']);

    // 0 results because the current user is no longer the file owner and the
    // file is private.
    $this->entity->setOwner(User::load(0));
    $this->entity->save();
    $response = $this->request('GET', $collection_filter_url, $request_options);
    $doc = $this->getDocumentFromResponse($response);
    $this->assertCount(0, $doc['data']);
  }

}
