<?php

namespace Drupal\Tests\contact\Functional\Rest;

use Drupal\contact\Entity\ContactForm;
use Drupal\contact\Entity\Message;
use Drupal\Core\Url;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

abstract class MessageResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'contact_message';

  /**
   * {@inheritdoc}
   */
  protected static $labelFieldName = 'subject';

  /**
   * The Message entity.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $entity;

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
  protected function getNormalizedPostEntity() {
    return [
      'subject' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'contact_form' => [
        [
          'target_id' => 'camelids',
        ],
      ],
      'message' => [
        [
          'value' => 'http://www.urbandictionary.com/define.php?term=drama%20llama',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    throw new \Exception('Not yet supported.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    if ($method === 'POST') {
      return "The 'access site-wide contact form' permission is required.";
    }
    return parent::getExpectedUnauthorizedAccessMessage($method);
  }

  /**
   * {@inheritdoc}
   */
  public function testGet() {
    // Contact Message entities are not stored, so they cannot be retrieved.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "rest.entity.contact_message.GET" does not exist.');

    $this->provisionEntityResource();
    Url::fromRoute('rest.entity.contact_message.GET')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function testPatch() {
    // Contact Message entities are not stored, so they cannot be modified.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "rest.entity.contact_message.PATCH" does not exist.');

    $this->provisionEntityResource();
    Url::fromRoute('rest.entity.contact_message.PATCH')->toString(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function testDelete() {
    // Contact Message entities are not stored, so they cannot be deleted.
    $this->expectException(RouteNotFoundException::class);
    $this->expectExceptionMessage('Route "rest.entity.contact_message.DELETE" does not exist.');

    $this->provisionEntityResource();
    Url::fromRoute('rest.entity.contact_message.DELETE')->toString(TRUE);
  }

}
