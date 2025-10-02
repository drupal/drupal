<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Jsonapi;

use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\Tests\jsonapi\Functional\EntityViewDisplayTest;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * JSON:API integration test for the "EntityViewDisplay" config entity type.
 */
#[Group('jsonapi')]
#[Group('layout_builder')]
#[RunTestsInSeparateProcesses]
class LayoutBuilderEntityViewDisplayTest extends EntityViewDisplayTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

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
  protected function getExpectedDocument(): array {
    $document = parent::getExpectedDocument();
    array_unshift($document['data']['attributes']['dependencies']['module'], 'layout_builder');
    $document['data']['attributes']['hidden'][OverridesSectionStorage::FIELD_NAME] = TRUE;
    $document['data']['attributes']['hidden']['links'] = TRUE;
    $document['data']['attributes']['third_party_settings']['layout_builder'] = [
      'enabled' => TRUE,
      'allow_custom' => TRUE,
    ];
    $document['data']['attributes']['content'] = [];
    return $document;
  }

}
