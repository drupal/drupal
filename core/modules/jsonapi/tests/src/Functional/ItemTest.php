<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\aggregator\Entity\Feed;
use Drupal\aggregator\Entity\Item;

/**
 * JSON:API integration test for the "Item" content entity type.
 *
 * @group jsonapi
 */
class ItemTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['aggregator'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'aggregator_item';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'aggregator_item--aggregator_item';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\aggregator\ItemInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [];

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access news feeds']);
        break;

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        $this->grantPermissionsToTestedRole(['administer news feeds']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" feed.
    $feed = Feed::create([
      'title' => 'Camelids',
      'url' => 'https://groups.drupal.org/not_used/167169',
      'refresh' => 900,
      'checked' => 1389919932,
      'description' => 'Drupal Core Group feed',
    ]);
    $feed->save();

    // Create a "Llama" item.
    $item = Item::create();
    $item->setTitle('Llama')
      ->setFeedId($feed->id())
      ->setLink('https://www.drupal.org/')
      ->setPostedTime(123456789)
      ->save();

    return $item;
  }

  /**
   * {@inheritdoc}
   */
  protected function createAnotherEntity($key) {
    $duplicate = $this->getEntityDuplicate($this->entity, $key);
    $duplicate->setLink('https://www.example.org/');
    $duplicate->save();
    return $duplicate;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access news feeds' permission is required.";

      case 'POST':
      case 'PATCH':
      case 'DELETE':
        return "The 'administer news feeds' permission is required.";
    }
  }

  /**
   * {@inheritdoc}
   */
  public function testGetIndividual() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testCollection() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testRelated() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testRelationships() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testPostIndividual() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testPatchIndividual() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

  /**
   * {@inheritdoc}
   */
  public function testDeleteIndividual() {
    $this->markTestSkipped('Remove this override in https://www.drupal.org/project/drupal/issues/2149851');
  }

}
