<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\ContentEntityNullStorage;
use PHPUnit\Framework\TestCase;

/**
 * Tests deprecated methods of ContentEntityStorageBase.
 *
 * @group Entity
 * @group legacy
 *
 * @coversDefaultClass \Drupal\Core\Entity\ContentEntityStorageBase
 */
class LegacyContentEntityStorageBaseTest extends TestCase {

  /**
   * Tests doLoadMultipleRevisionsFieldItems triggers an error.
   *
   * @covers ::doLoadMultipleRevisionsFieldItems
   *
   * @expectedDeprecation Calling Drupal\Core\EntityContentEntityStorageBase::doLoadMultipleRevisionsFieldItems() directly is deprecated in drupal:8.8.0 and the method will be made abstract in drupal:9.0.0. Storage implementations should override and implement their own loading logic. See https://www.drupal.org/node/3069692
   */
  public function testDoLoadMultipleRevisionFieldItems() {
    $storage = new TestContentEntityStorageBase();
    $items = $storage->doLoadMultipleRevisionsFieldItems([]);
    $this->assertSame([], $items);
  }

}

/**
 * Test class for ContentEntityStorageBaseTest.
 */
class TestContentEntityStorageBase extends ContentEntityNullStorage {

  /**
   * Constructs a TestContentEntityStorageBase object.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function doLoadMultipleRevisionsFieldItems($revision_ids) {
    return parent::doLoadMultipleRevisionsFieldItems($revision_ids);
  }

}
