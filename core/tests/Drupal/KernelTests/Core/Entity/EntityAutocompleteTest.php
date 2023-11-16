<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\Core\Site\Settings;
use Drupal\system\Controller\EntityAutocompleteController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Tests the autocomplete functionality.
 *
 * @group Entity
 */
class EntityAutocompleteTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_reference_test',
  ];

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected $bundle = 'entity_test';

  /**
   * Tests autocompletion edge cases with slashes in the names.
   */
  public function testEntityReferenceAutocompletion() {
    // Add an entity with a slash in its name.
    $entity_1 = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => '10/16/2011']);
    $entity_1->save();

    // Add another entity that differs after the slash character.
    $entity_2 = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => '10/17/2011']);
    $entity_2->save();

    // Add another entity that has both a comma, a slash and markup.
    $entity_3 = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create(['name' => 'label with, and / test']);
    $entity_3->save();

    // Try to autocomplete an entity label that matches both entities.
    // We should get both entities in a JSON encoded string.
    $input = '10/';
    $data = $this->getAutocompleteResult($input);
    $this->assertSame(Html::escape($entity_1->name->value), $data[0]['label'], 'Autocomplete returned the first matching entity');
    $this->assertSame(Html::escape($entity_2->name->value), $data[1]['label'], 'Autocomplete returned the second matching entity');

    // Try to autocomplete an entity label that matches the first entity.
    // We should only get the first entity in a JSON encoded string.
    $input = '10/16';
    $data = $this->getAutocompleteResult($input);
    $target = [
      'value' => $entity_1->name->value . ' (1)',
      'label' => Html::escape($entity_1->name->value),
    ];
    $this->assertSame($target, reset($data), 'Autocomplete returns only the expected matching entity.');

    // Pass the first entity to the request.
    // We should get empty results.
    // First we need to have permission to pass entity.
    $user = $this->drupalCreateUser([
      'administer entity_test content',
    ]);
    $this->drupalSetCurrentUser($user);
    $data = $this->getAutocompleteResult($input, $entity_1->id());
    $this->assertSame([], $data, 'Autocomplete returns empty results as first entity is passed to autocomplete request.');

    // Try to autocomplete an entity label that matches the second entity, and
    // the first entity  is already typed in the autocomplete (tags) widget.
    $input = $entity_1->name->value . ' (1), 10/17';
    $data = $this->getAutocompleteResult($input);
    $this->assertSame(Html::escape($entity_2->name->value), $data[0]['label'], 'Autocomplete returned the second matching entity');

    // Try to autocomplete an entity label with both comma, slash and markup.
    $input = '"label with, and /"';
    $data = $this->getAutocompleteResult($input);
    $n = $entity_3->name->value . ' (3)';
    // Entity labels containing commas or quotes must be wrapped in quotes.
    $n = Tags::encode($n);
    $target = [
      'value' => $n,
      'label' => Html::escape($entity_3->name->value),
    ];
    $this->assertSame($target, reset($data), 'Autocomplete returns an entity label containing a comma and a slash.');

    // Test empty input.
    foreach (['', NULL, FALSE, 0, 0.0] as $input) {
      $data = $this->getAutocompleteResult($input);
      $this->assertSame([], $data, 'Autocomplete of empty input returns empty result');
    }

    $input = ',';
    $data = $this->getAutocompleteResult($input);
    $this->assertSame(Html::escape($entity_1->name->value), $data[0]['label'], 'Autocomplete returned the first matching entity');
    $this->assertSame(Html::escape($entity_2->name->value), $data[1]['label'], 'Autocomplete returned the second matching entity');
    $this->assertSame(Html::escape($entity_3->name->value), $data[2]['label'], 'Autocomplete returned the third matching entity');

    // Pass the first entity to the request.
    // We should not get the first entity in the results.
    $data = $this->getAutocompleteResult($input, $entity_1->id());
    $this->assertCount(2, $data, 'Autocomplete returned only 2 entities');
    $this->assertSame(Html::escape($entity_2->name->value), $data[0]['label'], 'Autocomplete returned the second matching entity');
    $this->assertSame(Html::escape($entity_3->name->value), $data[1]['label'], 'Autocomplete returned the third matching entity');

    // Strange input that is mangled by
    // \Drupal\Component\Utility\Tags::explode().
    $input = '"l!J>&Tw';
    $data = $this->getAutocompleteResult($input);
    $this->assertSame(Html::escape($entity_1->name->value), $data[0]['label'], 'Autocomplete returned the first matching entity');
    $this->assertSame(Html::escape($entity_2->name->value), $data[1]['label'], 'Autocomplete returned the second matching entity');
    $this->assertSame(Html::escape($entity_3->name->value), $data[2]['label'], 'Autocomplete returned the third matching entity');

    // Try to autocomplete an entity label with the '0' character.
    $input = '0';
    $data = $this->getAutocompleteResult($input);
    $this->assertSame(Html::escape($entity_1->name->value), $data[0]['label'], 'Autocomplete returned the first matching entity');
    $this->assertSame(Html::escape($entity_2->name->value), $data[1]['label'], 'Autocomplete returned the second matching entity');
  }

  /**
   * Tests that missing or invalid selection setting key are handled correctly.
   */
  public function testSelectionSettingsHandling() {
    $entity_reference_controller = EntityAutocompleteController::create($this->container);
    $request = Request::create('entity_reference_autocomplete/' . $this->entityType . '/default');
    $request->query->set('q', $this->randomString());

    try {
      // Pass an invalid selection settings key (i.e. one that does not exist
      // in the key/value store).
      $selection_settings_key = $this->randomString();
      $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'default', $selection_settings_key);

      $this->fail('Non-existent selection settings key throws an exception.');
    }
    catch (AccessDeniedHttpException $e) {
      // Expected exception; just continue testing.
    }

    try {
      // Generate a valid hash key but store a modified settings array.
      $selection_settings = [];
      $selection_settings_key = Crypt::hmacBase64(serialize($selection_settings) . $this->entityType . 'default', Settings::getHashSalt());

      $selection_settings[$this->randomMachineName()] = $this->randomString();
      \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

      $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'default', $selection_settings_key);
    }
    catch (AccessDeniedHttpException $e) {
      $this->assertSame('Invalid selection settings key.', $e->getMessage());
    }

  }

  /**
   * Returns the result of an Entity reference autocomplete request.
   *
   * @param string $input
   *   The label of the entity to query by.
   * @param int $entity_id
   *   The label of the entity to query by.
   *
   * @return mixed
   *   The JSON value encoded in its appropriate PHP type.
   */
  protected function getAutocompleteResult($input, $entity_id = NULL) {
    // Use "entity_test_all_except_host" EntityReferenceSelection
    // to also test passing an entity to autocomplete requests.
    $request = Request::create('entity_reference_autocomplete/' . $this->entityType . '/entity_test_all_except_host');
    $request->query->set('q', $input);
    $selection_settings = [];
    if ($entity_id) {
      $request->query->set('entity_type', $this->entityType);
      $request->query->set('entity_id', $entity_id);
    }

    $selection_settings_key = Crypt::hmacBase64(serialize($selection_settings) . $this->entityType . 'entity_test_all_except_host', Settings::getHashSalt());
    \Drupal::keyValue('entity_autocomplete')->set($selection_settings_key, $selection_settings);

    $entity_reference_controller = EntityAutocompleteController::create($this->container);
    $result = $entity_reference_controller->handleAutocomplete($request, $this->entityType, 'entity_test_all_except_host', $selection_settings_key)->getContent();

    return Json::decode($result);
  }

}
