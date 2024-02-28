<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\FunctionalTests\Rest\EntityViewDisplayResourceTestBase;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

/**
 * Provides a base class for testing LayoutBuilderEntityViewDisplay resources.
 */
abstract class LayoutBuilderEntityViewDisplayResourceTestBase extends EntityViewDisplayResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_builder'];

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
  protected function getExpectedNormalizedEntity() {
    $expected = parent::getExpectedNormalizedEntity();
    array_unshift($expected['dependencies']['module'], 'layout_builder');
    $expected['hidden'][OverridesSectionStorage::FIELD_NAME] = TRUE;
    $expected['third_party_settings']['layout_builder'] = [
      'enabled' => TRUE,
      'allow_custom' => TRUE,
    ];
    return $expected;
  }

}
