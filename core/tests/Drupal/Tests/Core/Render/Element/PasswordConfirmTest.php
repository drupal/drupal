<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Render\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PasswordConfirm;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Render\Element\PasswordConfirm.
 */
#[CoversClass(PasswordConfirm::class)]
#[Group('Render')]
class PasswordConfirmTest extends UnitTestCase {

  /**
   * Tests value callback.
   */
  #[DataProvider('providerTestValueCallback')]
  public function testValueCallback($expected, $element, $input): void {
    $form_state = $this->prophesize(FormStateInterface::class)->reveal();
    $this->assertSame($expected, PasswordConfirm::valueCallback($element, $input, $form_state));
  }

  /**
   * Data provider for testValueCallback().
   */
  public static function providerTestValueCallback(): array {
    $data = [];
    $data[] = [['pass1' => '', 'pass2' => ''], [], NULL];
    $data[] = [['pass1' => '', 'pass2' => ''], ['#default_value' => ['pass2' => 'value']], NULL];
    $data[] = [['pass2' => 'value', 'pass1' => ''], ['#default_value' => ['pass2' => 'value']], FALSE];
    $data[] = [['pass1' => '123456', 'pass2' => 'qwerty'], [], ['pass1' => '123456', 'pass2' => 'qwerty']];
    $data[] = [['pass1' => '123', 'pass2' => '234'], [], ['pass1' => 123, 'pass2' => 234]];
    $data[] = [['pass1' => '', 'pass2' => '234'], [], ['pass1' => ['array'], 'pass2' => 234]];

    return $data;
  }

}
