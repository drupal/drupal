<?php

namespace Drupal\rest\Tests;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\entity_test\Entity\EntityTest;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests the update of resources.
 *
 * @group rest
 */
class UpdateTest extends RESTTestBase {

  use CommentTestTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['hal', 'rest', 'entity_test', 'node', 'comment'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->addDefaultCommentField('entity_test', 'entity_test');
  }

  /**
   * Tests several valid and invalid partial update requests on test entities.
   */
  public function testPatchUpdate() {
    $serializer = $this->container->get('serializer');
    // @todo Test all other entity types here as well.
    $entity_type = 'entity_test';

    $this->enableService('entity:' . $entity_type, 'PATCH');
    // Create a user account that has the required permissions to create
    // resources via the REST API.
    $permissions = $this->entityPermissions($entity_type, 'update');
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $context = ['account' => $account];

    // Create an entity and save it to the database.
    $entity = $this->entityCreate($entity_type);
    $entity->save();

    // Create a second stub entity for overwriting a field.
    $patch_values['field_test_text'] = array(0 => array(
      'value' => $this->randomString(),
      'format' => 'plain_text',
    ));
    $patch_entity = $this->container->get('entity_type.manager')
      ->getStorage($entity_type)
      ->create($patch_values);
    // We don't want to overwrite the UUID.
    $patch_entity->set('uuid', NULL);
    $serialized = $serializer->serialize($patch_entity, $this->defaultFormat, $context);

    // Update the entity over the REST API but forget to specify a Content-Type
    // header, this should throw the proper exception.
    $this->httpRequest($entity->toUrl(), 'PATCH', $serialized, 'none');
    $this->assertResponse(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
    $this->assertRaw('No route found that matches &quot;Content-Type: none&quot;');

    // Update the entity over the REST API.
    $response = $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(200);

    // Make sure that the response includes an entity in the body, check the
    // updated field as an example.
    $request = Json::decode($serialized);
    $response = Json::decode($response);
    $this->assertEqual($request['field_test_text'][0]['value'], $response['field_test_text'][0]['value']);
    unset($request['_links']);
    unset($response['_links']);
    unset($response['id']);
    unset($response['uuid']);
    unset($response['name']);
    $this->assertEqual($request, $response);

    // Re-load updated entity from the database.
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($entity_type);
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->field_test_text->value, $patch_entity->field_test_text->value, 'Field was successfully updated.');

    // Make sure that the field does not get deleted if it is not present in the
    // PATCH request.
    $normalized = $serializer->normalize($patch_entity, $this->defaultFormat, $context);
    unset($normalized['field_test_text']);
    $serialized = $serializer->encode($normalized, $this->defaultFormat);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(200);

    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertNotNull($entity->field_test_text->value . 'Test field has not been deleted.');

    // Try to empty a field.
    $normalized['field_test_text'] = array();
    $serialized = $serializer->encode($normalized, $this->defaultFormat);

    // Update the entity over the REST API.
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(200);

    // Re-load updated entity from the database.
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id(), TRUE);
    $this->assertNull($entity->field_test_text->value, 'Test field has been cleared.');

    // Enable access protection for the text field.
    // @see entity_test_entity_field_access()
    $entity->field_test_text->value = 'no edit access value';
    $entity->field_test_text->format = 'plain_text';
    $entity->save();

    // Try to empty a field that is access protected.
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Re-load the entity from the database.
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->field_test_text->value, 'no edit access value', 'Text field was not deleted.');

    // Try to update an access protected field.
    $normalized = $serializer->normalize($patch_entity, $this->defaultFormat, $context);
    $normalized['field_test_text'][0]['value'] = 'no access value';
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Re-load the entity from the database.
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->field_test_text->value, 'no edit access value', 'Text field was not updated.');

    // Try to update the field with a text format this user has no access to.
    // First change the original field value so we're allowed to edit it again.
    $entity->field_test_text->value = 'test';
    $entity->save();
    $patch_entity->set('field_test_text', array(
      'value' => 'test',
      'format' => 'full_html',
    ));
    $serialized = $serializer->serialize($patch_entity, $this->defaultFormat, $context);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);

    // Re-load the entity from the database.
    $storage->resetCache([$entity->id()]);
    $entity = $storage->load($entity->id());
    $this->assertEqual($entity->field_test_text->format, 'plain_text', 'Text format was not updated.');

    // Restore the valid test value.
    $entity->field_test_text->value = $this->randomString();
    $entity->save();

    // Try to send no data at all, which does not make sense on PATCH requests.
    $this->httpRequest($entity->urlInfo(), 'PATCH', NULL, $this->defaultMimeType);
    $this->assertResponse(400);

    // Try to update a non-existing entity with ID 9999.
    $this->httpRequest($entity_type . '/9999', 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(404);
    $storage->resetCache([9999]);
    $loaded_entity = $storage->load(9999);
    $this->assertFalse($loaded_entity, 'Entity 9999 was not created.');

    // Try to send invalid data to trigger the entity validation constraints.
    // Send a UUID that is too long.
    $entity->set('uuid', $this->randomMachineName(129));
    $invalid_serialized = $serializer->serialize($entity, $this->defaultFormat, $context);
    $response = $this->httpRequest($entity->toUrl()->setRouteParameter('_format', $this->defaultFormat), 'PATCH', $invalid_serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['message'], "Unprocessable Entity: validation failed.\nuuid.0.value: <em class=\"placeholder\">UUID</em>: may not be longer than 128 characters.\n");

    // Try to update an entity without proper permissions.
    $this->drupalLogout();
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(403);

    // Try to update a resource which is not REST API enabled.
    $this->enableService(FALSE);
    $this->drupalLogin($account);
    $this->httpRequest($entity->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(405);
  }

  /**
   * Tests several valid and invalid update requests for the 'user' entity type.
   */
  public function testUpdateUser() {
    $serializer = $this->container->get('serializer');
    $entity_type = 'user';
    // Enables the REST service for 'user' entity type.
    $this->enableService('entity:' . $entity_type, 'PATCH');
    $permissions = $this->entityPermissions($entity_type, 'update');
    $account = $this->drupalCreateUser($permissions);
    $account->set('mail', 'old-email@example.com');
    $this->drupalLogin($account);

    // Create an entity and save it to the database.
    $account->save();
    $account->set('changed', NULL);

    // Try and set a new email without providing the password.
    $account->set('mail', 'new-email@example.com');
    $context = ['account' => $account];
    $normalized = $serializer->normalize($account, $this->defaultFormat, $context);
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->toUrl()->setRouteParameter('_format', $this->defaultFormat), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['message'], "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n");

    // Try and send the new email with a password.
    $normalized['pass'][0]['existing'] = 'wrong';
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->toUrl()->setRouteParameter('_format', $this->defaultFormat), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['message'], "Unprocessable Entity: validation failed.\nmail: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Email</em>.\n");

    // Try again with the password.
    $normalized['pass'][0]['existing'] = $account->pass_raw;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(200);

    // Try to change the password without providing the current password.
    $new_password = $this->randomString();
    $normalized = $serializer->normalize($account, $this->defaultFormat, $context);
    $normalized['pass'][0]['value'] = $new_password;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $response = $this->httpRequest($account->toUrl()->setRouteParameter('_format', $this->defaultFormat), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(422);
    $error = Json::decode($response);
    $this->assertEqual($error['message'], "Unprocessable Entity: validation failed.\npass: Your current password is missing or incorrect; it's required to change the <em class=\"placeholder\">Password</em>.\n");

    // Try again with the password.
    $normalized['pass'][0]['existing'] = $account->pass_raw;
    $serialized = $serializer->serialize($normalized, $this->defaultFormat, $context);
    $this->httpRequest($account->urlInfo(), 'PATCH', $serialized, $this->defaultMimeType);
    $this->assertResponse(200);

    // Verify that we can log in with the new password.
    $account->pass_raw = $new_password;
    $this->drupalLogin($account);
  }

  /**
   * Test patching a comment using both HAL+JSON and JSON.
   */
  public function testUpdateComment() {
    $entity_type = 'comment';
    // Enables the REST service for 'comment' entity type.
    $this->enableService('entity:' . $entity_type, 'PATCH', ['hal_json', 'json']);
    $permissions = $this->entityPermissions($entity_type, 'update');
    $account = $this->drupalCreateUser($permissions);
    $account->set('mail', 'old-email@example.com');
    $this->drupalLogin($account);

    // Create & save an entity to comment on, plus a comment.
    $entity_test = EntityTest::create();
    $entity_test->save();
    $entity_values = $this->entityValues($entity_type);
    $entity_values['entity_id'] = $entity_test->id();
    $entity_values['uid'] = $account->id();
    $comment = Comment::create($entity_values);
    $comment->save();

    $this->pass('Test case 1: PATCH comment using HAL+JSON.');
    $comment->setSubject('Initial subject')->save();
    $read_only_fields = [
      'name',
      'created',
      'changed',
      'status',
      'thread',
      'entity_type',
      'field_name',
      'entity_id',
      'uid',
    ];
    $this->assertNotEqual('Updated subject', $comment->getSubject());
    $comment->setSubject('Updated subject');
    $this->patchEntity($comment, $read_only_fields, $account, 'hal_json', 'application/hal+json');
    $comment = Comment::load($comment->id());
    $this->assertEqual('Updated subject', $comment->getSubject());

    $this->pass('Test case 1: PATCH comment using JSON.');
    $comment->setSubject('Initial subject')->save();
    $read_only_fields = [
      'pid', // Extra compared to HAL+JSON.
      'entity_id',
      'uid',
      'name',
      'homepage', // Extra compared to HAL+JSON.
      'created',
      'changed',
      'status',
      'thread',
      'entity_type',
      'field_name',
    ];
    $this->assertNotEqual('Updated subject', $comment->getSubject());
    $comment->setSubject('Updated subject');
    $this->patchEntity($comment, $read_only_fields, $account, 'json', 'application/json');
    $comment = Comment::load($comment->id());
    $this->assertEqual('Updated subject', $comment->getSubject());
  }

  /**
   * Patches an existing entity using the passed in (modified) entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The updated entity to send.
   * @param string[] $read_only_fields
   *   Names of the fields that are read-only, in validation order.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account to use for serialization.
   * @param string $format
   *   A serialization format.
   * @param string $mime_type
   *   The MIME type corresponding to the specified serialization format.
   */
  protected function patchEntity(EntityInterface $entity, array $read_only_fields, AccountInterface $account, $format, $mime_type) {
    $serializer = $this->container->get('serializer');

    $url = $entity->toUrl()->setRouteParameter('_format', $this->defaultFormat);
    $context = ['account' => $account];
    // Certain fields are always read-only, others this user simply is not
    // allowed to modify. For all of them, ensure they are not serialized, else
    // we'll get a 403 plus an error message.
    for ($i = 0; $i < count($read_only_fields); $i++) {
      $field = $read_only_fields[$i];

      $normalized = $serializer->normalize($entity, $format, $context);
      if ($format !== 'hal_json') {
        // The default normalizer always keeps fields, even if they are unset
        // here because they should be omitted during a PATCH request. Therefore
        // manually strip them
        // @see \Drupal\Core\Entity\ContentEntityBase::__unset()
        // @see \Drupal\serialization\Normalizer\EntityNormalizer::normalize()
        // @see \Drupal\hal\Normalizer\ContentEntityNormalizer::normalize()
        $read_only_fields_so_far = array_slice($read_only_fields, 0, $i);
        $normalized = array_diff_key($normalized, array_flip($read_only_fields_so_far));
      }
      $serialized = $serializer->serialize($normalized, $format, $context);

      $this->httpRequest($url, 'PATCH', $serialized, $mime_type);
      $this->assertResponse(403);
      $this->assertResponseBody('{"message":"Access denied on updating field \\u0027' . $field . '\\u0027."}');

      if ($format === 'hal_json') {
        // We've just tried with this read-only field, now unset it.
        $entity->set($field, NULL);
      }
    }

    // Finally, with all read-only fields unset, the request should succeed.
    $normalized = $serializer->normalize($entity, $format, $context);
    if ($format !== 'hal_json') {
      $normalized = array_diff_key($normalized, array_combine($read_only_fields, $read_only_fields));
    }
    $serialized = $serializer->serialize($normalized, $format, $context);

    // Try first without CSRF token which should fail.
    $this->httpRequest($url, 'PATCH', $serialized, $mime_type, FALSE);
    $this->assertResponse(403);
    $this->assertRaw('X-CSRF-Token request header is missing');
    // Then try with an invalid CSRF token.
    $this->httpRequest($url, 'PATCH', $serialized, $mime_type, 'invalid-csrf-token');
    $this->assertResponse(403);
    $this->assertRaw('X-CSRF-Token request header is invalid');
    // Then try with CSRF token.
    $this->httpRequest($url, 'PATCH', $serialized, $mime_type);
    $this->assertResponse(200);
  }

}
