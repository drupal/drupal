<?php

namespace Drupal\Tests\auto_updates\Unit;

use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerInterface;
use Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\system\SystemManager;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult
 *
 * @group auto_updates
 */
class ReadinessCheckerResultTest extends UnitTestCase {

  /**
   * @covers ::createWarningResult
   *
   * @dataProvider providerValidConstructorArguments
   */
  public function testCreateWarningResult(array $messages, ?string $summary): void {
    $checker = $this->prophesize(ReadinessCheckerInterface::class);
    $checker = $checker->reveal();
    $checker->_serviceId = 'the_id';
    $summary = $summary ? t($summary) : NULL;
    $result = ReadinessCheckerResult::createWarningResult($checker, $messages, $summary);
    $this->assertResultValid($result, $messages, $summary, SystemManager::REQUIREMENT_WARNING);
  }

  /**
   * @covers ::createErrorResult
   *
   * @dataProvider providerValidConstructorArguments
   */
  public function testCreateErrorResult(array $messages, ?string $summary): void {
    $checker = $this->prophesize(ReadinessCheckerInterface::class);
    $checker = $checker->reveal();
    $checker->_serviceId = 'the_id';
    $summary = $summary ? t($summary) : NULL;
    $result = ReadinessCheckerResult::createErrorResult($checker, $messages, $summary);
    $this->assertResultValid($result, $messages, $summary, SystemManager::REQUIREMENT_ERROR);
  }

  /**
   * @covers ::createWarningResult
   */
  public function testCreateWarningResultException(): void {
    $checker = $this->prophesize(ReadinessCheckerInterface::class);
    $checker = $checker->reveal();
    $checker->_serviceId = 'the_id';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('If more than 1 messages is provided the summary is required.');
    ReadinessCheckerResult::createWarningResult($checker, ['Something is wrong', 'Something else is also wrong'], NULL);
  }

  /**
   * @covers ::createErrorResult
   */
  public function testCreateErrorResultException(): void {
    $checker = $this->prophesize(ReadinessCheckerInterface::class);
    $checker = $checker->reveal();
    $checker->_serviceId = 'the_id';
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('If more than 1 messages is provided the summary is required.');
    ReadinessCheckerResult::createErrorResult($checker, ['Something is wrong', 'Something else is also wrong'], NULL);
  }

  /**
   * Data provider for testCreateWarningResult().
   *
   * @return mixed[]
   *   Test cases for testCreateWarningResult().
   */
  public function providerValidConstructorArguments(): array {
    return [
      '1 message no summary' => [
        'messages' => ['Something is wrong'],
        'summary' => NULL,
      ],
      '2 messages has summary' => [
        'messages' => ['Something is wrong', 'Something else is also wrong'],
        'summary' => 'This sums it up.',
      ],
    ];
  }

  /**
   * Asserts a check result is valid.
   *
   * @param \Drupal\auto_updates\ReadinessChecker\ReadinessCheckerResult $result
   *   The checker result to check.
   * @param array $expected_messages
   *   The expected messages.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $summary
   *   The expected summary or NULL if not summary is expected.
   * @param int $severity
   *   The severity.
   */
  protected function assertResultValid(ReadinessCheckerResult $result, array $expected_messages, ?TranslatableMarkup $summary, int $severity): void {
    $this->assertSame($expected_messages, $result->getMessages());
    if ($summary === NULL) {
      $this->assertNull($result->getSummary());
    }
    else {
      $this->assertSame($summary->getUntranslatedString(), $result->getSummary()
        ->getUntranslatedString());
    }
    $this->assertSame($severity, $result->getSeverity());
  }

}
