<?php

namespace Drupal\Tests\layout_builder\Functional\Jsonapi;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\Tests\jsonapi\Functional\EntityViewDisplayTest;

/**
 * JSON:API integration test for the "EntityViewDisplay" config entity type.
 *
 * @group jsonapi
 * @group layout_builder
 */
class LayoutBuilderEntityViewDisplayTest extends EntityViewDisplayTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['layout_builder'];

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    /** @var \Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay $entity */
    $entity = parent::createEntity();
    $entity
      ->enableLayoutBuilder()
      ->setOverridable()
      ->save();
    $this->assertCount(1, $entity->getThirdPartySetting('layout_builder', 'sections'));
    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $document = parent::getExpectedDocument();
    array_unshift($document['data']['attributes']['dependencies']['module'], 'layout_builder');
    $document['data']['attributes']['hidden'][OverridesSectionStorage::FIELD_NAME] = TRUE;
    $document['data']['attributes']['third_party_settings']['layout_builder'] = [
      'enabled' => TRUE,
      'allow_custom' => TRUE,
    ];
    return $document;
  }

}
