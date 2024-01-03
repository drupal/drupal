<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\StringTranslation;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationWrapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the TranslationWrapper class.
 *
 * @coversDefaultClass \Drupal\Core\StringTranslation\TranslationWrapper
 * @group StringTranslation
 */
class TranslationWrapperTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @group legacy
   */
  public function testTranslationWrapper() {
    $this->expectDeprecation('Drupal\Core\StringTranslation\TranslationWrapper is deprecated in drupal:8.0.0 and is removed from drupal:11.0.0. Use the \Drupal\Core\StringTranslation\TranslatableMarkup class instead. See https://www.drupal.org/node/2571255');
    $object = new TranslationWrapper('Deprecated');
    $this->assertInstanceOf(TranslatableMarkup::class, $object);
  }

}
