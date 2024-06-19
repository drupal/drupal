<?php

declare(strict_types=1);

namespace Drupal\Tests\comment\Unit\Action;

use Drupal\comment\Plugin\Action\UnpublishByKeywordComment;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @group comment
 * @group legacy
 */
class UnpublishByKeywordCommentTest extends UnitTestCase {

  /**
   * Tests deprecation message.
   */
  public function testUnpublishByKeywordAction(): void {
    $comment_view_builder = $this->createMock(EntityViewBuilderInterface::class);
    $renderer = $this->createMock(RendererInterface::class);
    $this->expectDeprecation('Drupal\comment\Plugin\Action\UnpublishByKeywordComment is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Use \Drupal\action\Plugin\Action\UnpublishByKeywordComment instead. See https://www.drupal.org/node/3424506');
    $this->assertIsObject(new UnpublishByKeywordComment([], 'foo', [], $comment_view_builder, $renderer));
  }

}
