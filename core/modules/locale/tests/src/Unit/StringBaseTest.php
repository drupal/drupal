<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\locale\SourceString;
use Drupal\locale\StringBase;
use Drupal\locale\StringStorageException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\locale\StringBase.
 */
#[CoversClass(StringBase::class)]
#[Group('locale')]
class StringBaseTest extends UnitTestCase {

  /**
   * Tests save without storage.
   */
  public function testSaveWithoutStorage(): void {
    $string = new SourceString(['source' => 'test']);
    $this->expectException(StringStorageException::class);
    $this->expectExceptionMessage('The string cannot be saved because its not bound to a storage: test');
    $string->save();
  }

  /**
   * Tests delete without storage.
   */
  public function testDeleteWithoutStorage(): void {
    $string = new SourceString(['lid' => 1, 'source' => 'test']);
    $this->expectException(StringStorageException::class);
    $this->expectExceptionMessage('The string cannot be deleted because its not bound to a storage: test');
    $string->delete();
  }

}
