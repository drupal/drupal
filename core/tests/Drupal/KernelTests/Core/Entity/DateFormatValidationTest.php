<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\KernelTests\Core\Config\ConfigEntityValidationTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests validation of date_format entities.
 */
#[Group('Entity')]
#[Group('Validation')]
#[Group('config')]
#[RunTestsInSeparateProcesses]
class DateFormatValidationTest extends ConfigEntityValidationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entity = DateFormat::create([
      'id' => 'test',
      'label' => 'Test',
      'pattern' => 'Y-m-d',
    ]);
    $this->entity->save();
  }

  /**
   * Tests that the pattern of a date format is validated.
   *
   * @param string $pattern
   *   The pattern to set.
   * @param bool $locked
   *   Whether the date format entity is locked or not.
   * @param string $expected_error
   *   The error message that should be flagged for the invalid pattern.
   */
  #[TestWith(["q", TRUE, "This is not a valid date format."])]
  #[TestWith(["", TRUE, "This value should not be blank."])]
  #[TestWith(["q", FALSE, "This is not a valid date format."])]
  #[TestWith(["", FALSE, "This value should not be blank."])]
  public function testPatternIsValidated(string $pattern, bool $locked, string $expected_error): void {
    $this->entity->setPattern($pattern)->set('locked', $locked);
    $this->assertValidationErrors(['pattern' => $expected_error]);
  }

}
