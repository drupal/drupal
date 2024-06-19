<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\Core\Cache\Cache;
use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * Test normalization of computed field.
 *
 * @group rest
 */
class EntityTestComputedFieldNormalizerTest extends EntityTestResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'entity_test_computed_field';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer entity_test content']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    if ($this->config('rest.settings')->get('bc_entity_resource_permissions')) {
      return parent::getExpectedUnauthorizedAccessMessage($method);
    }

    return "The 'administer entity_test content' permission is required.";
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedNormalizedEntity() {
    $expected = parent::getExpectedNormalizedEntity();
    $expected['computed_reference_field'] = [];
    $expected['computed_string_field'] = [];
    unset($expected['field_test_text'], $expected['langcode'], $expected['type'], $expected['uuid']);
    // @see \Drupal\entity_test\Plugin\Field\ComputedTestCacheableStringItemList::computeValue().
    $expected['computed_test_cacheable_string_field'] = [
      [
        'value' => 'computed test cacheable string field',
      ],
    ];
    // @see \Drupal\entity_test\Plugin\Field\ComputedTestCacheableIntegerItemList::computeValue().
    $expected['computed_test_cacheable_integer_field'] = [
      [
        'value' => 0,
      ],
    ];

    $expected['uuid'] = [
      0 => [
        'value' => $this->entity->uuid(),
      ],
    ];

    return $expected;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['url.query_args:computed_test_cacheable_string_field']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['field:computed_test_cacheable_string_field']);
  }

  /**
   * {@inheritdoc}
   */
  public function testPost(): void {
    // Post test not required.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testPatch(): void {
    // Patch test not required.
    $this->markTestSkipped();
  }

  /**
   * {@inheritdoc}
   */
  public function testDelete(): void {
    // Delete test not required.
    $this->markTestSkipped();
  }

}
