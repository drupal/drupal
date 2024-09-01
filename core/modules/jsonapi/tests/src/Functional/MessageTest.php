<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Utility\NestedArray;
use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * JSON:API integration test for the "Message" content entity type.
 *
 * @group jsonapi
 * @group #slow
 */
class MessageTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'contact_message';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'contact_message--camelids';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $labelFieldName = 'subject';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['access site-wide contact form']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    if (!ContactForm::load('camelids')) {
      // Create a "Camelids" contact form.
      ContactForm::create([
        'id' => 'camelids',
        'label' => 'Llama',
        'message' => 'Let us know what you think about llamas',
        'reply' => 'Llamas are indeed awesome!',
        'recipients' => [
          'llama@example.com',
          'contact@example.com',
        ],
      ])->save();
    }

    $message = Message::create([
      'contact_form' => 'camelids',
      'subject' => 'Llama Gabilondo',
      'message' => 'Llamas are awesome!',
    ]);
    $message->save();

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    throw new \Exception('Not yet supported.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'contact_message--camelids',
        'attributes' => [
          'subject' => 'Drama llama',
          'message' => 'http://www.urbandictionary.com/define.php?term=drama%20llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($method === 'POST') {
      return "The 'access site-wide contact form' permission is required.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  public function testGetIndividual(): void {
    // Contact Message entities are not stored, so they cannot be retrieved.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.individual" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.individual')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestPatchIndividual(): void {
    // Contact Message entities are not stored, so they cannot be modified.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.individual" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.individual')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestDeleteIndividual(): void {
    // Contact Message entities are not stored, so they cannot be deleted.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.individual" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.individual')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function testRelated(): void {
    // Contact Message entities are not stored, so they cannot be retrieved.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.related" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.related')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function testRelationships(): void {
    // Contact Message entities are not stored, so they cannot be retrieved.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.relationship.get" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.relationship.get')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function testCollection(): void {
    $collection_url = Url::fromRoute('jsonapi.contact_message--camelids.collection.post')->setAbsolute(TRUE);
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // 405 because Message entities are not stored, so they cannot be retrieved,
    // yet the same URL can be used to POST them.
    $response = $this->request('GET', $collection_url, $request_options);
    $this->assertSame(405, $response->getStatusCode());
    $this->assertSame(['POST'], $response->getHeader('Allow'));
  }

  /**
   * {@inheritdoc}
   */
  public function testRevisions(): void {
    // Contact Message entities are not stored, so they cannot be retrieved.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "jsonapi.contact_message--camelids.individual" does not exist.');

    Url::fromRoute('jsonapi.contact_message--camelids.individual')->toString(TRUE);
  }

}
