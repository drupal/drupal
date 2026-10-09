<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Gettext;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\TestTools\PhpUnitCompatibility\ForwardCompatibilityTrait;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Gettext PO file header handling features.
 *
 * @see Drupal\Component\Gettext\PoHeader.
 */
#[Group('Gettext')]
class PoStreamReaderTest extends TestCase {

  use ForwardCompatibilityTrait;

  /**
   * Validates that calling open with an invalid URI throws an exception.
   */
  public function testOpeningFileError(): void {
    $reader = new PoStreamReader();
    $reader->setURI('fake');
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessageIs('Cannot open stream for uri fake');
    $reader->open();
  }

}
