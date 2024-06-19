<?php

declare(strict_types=1);

namespace Drupal\Tests\node\Unit\Action;

use Drupal\node\Plugin\Action\UnpublishByKeywordNode;
use Drupal\Tests\UnitTestCase;

/**
 * @group node
 * @group legacy
 */
class UnpublishByKeywordActionTest extends UnitTestCase {

  /**
   * Tests deprecation of \Drupal\node\Plugin\Action\UnpublishByKeywordNode.
   */
  public function testUnpublishByKeywordAction(): void {
    $this->expectDeprecation('Drupal\node\Plugin\Action\UnpublishByKeywordNode is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\UnpublishByKeywordNode instead. See https://www.drupal.org/node/3424506');
    $this->assertIsObject(new UnpublishByKeywordNode([], 'foo', []));
  }

}
