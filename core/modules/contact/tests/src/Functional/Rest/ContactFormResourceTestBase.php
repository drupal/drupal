<?php

namespace Drupal\Tests\contact\Functional\Rest;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;

abstract class ContactFormResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['contact'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'contact_form';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * @var \Drupal\contact\Entity\ContactForm
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access site-wide contact form']);
      default:
        $this->grantPermissionsToTestedRole(['administer contact forms']);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $contact_form = ContactForm::create([
      'id' => 'llama',
      'label' => 'Llama',
      'message' => 'Let us know what you think about llamas',
      'reply' => 'Llamas are indeed awesome!',
      'recipients' => [
        'llama@example.com',
        'contact@example.com',
      ],
    ]);
    $contact_form->save();

    return $contact_form;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    return [
      'dependencies' => [],
      'id' => 'llama',
      'label' => 'Llama',
      'langcode' => 'en',
      'message' => 'Let us know what you think about llamas',
      'recipients' => [
        'llama@example.com',
        'contact@example.com',
      ],
      'redirect' => NULL,
      'reply' => 'Llamas are indeed awesome!',
      'status' => TRUE,
      'uuid' => $this->entity->uuid(),
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    return "The 'access site-wide contact form' permission is required.";
  }

}
