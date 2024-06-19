<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TranslationWrapper backward compatibility layer.
 *
 * @coversDefaultClass \Drupal\Core\StringTranslation\TranslationWrapper
 * @group StringTranslation
 */
class TranslationWrapperTest extends UnitTestCase {

  /**
   * @covers ::__construct
   */
  public function testTranslationWrapper(): void {
    $object = new TranslationWrapper('Backward compatibility');
    $this->assertInstanceOf(TranslatableMarkup::class, $object);
  }

}
