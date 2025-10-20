<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests the LinkAccessConstraintValidator validator.
 */
#[CoversClass(LinkAccessConstraintValidator::class)]
#[Group('validation')]
class LinkAccessConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests the access validation constraint for links.
   *
   * @legacy-covers ::validate
   */
  #[DataProvider('providerValidate')]
  public function testValidate(bool $mayLinkAnyPage, bool $urlAccess, bool $valid): void {
    // Mock a Url object that returns a boolean indicating user access.
    $url = $this->getMockBuilder('Drupal\Core\Url')
      ->disableOriginalConstructor()
      ->getMock();
    if ($mayLinkAnyPage) {
      $url->expects($this->never())
        ->method('access');
    }
    else {
      $url->expects($this->once())
        ->method('access')
        ->willReturn($urlAccess);
    }

    // Mock a link object that returns the URL object.
    $link = $this->createMock('Drupal\link\LinkItemInterface');
    $link->expects($this->any())
      ->method('getUrl')
      ->willReturn($url);

    // Mock a user object that returns a boolean indicating user access to all
    // links.
    $user = $this->createMock('Drupal\Core\Session\AccountProxyInterface');
    $user->expects($this->any())
      ->method('hasPermission')
      ->with($this->equalTo('link to any page'))
      ->willReturn($mayLinkAnyPage);

    $context = $this->createMock(ExecutionContextInterface::class);

    $constraintViolationBuilder = $this->createMock(ConstraintViolationBuilderInterface::class);
    $constraintViolationBuilder->method('atPath')
      ->with('uri')
      ->willReturn($constraintViolationBuilder);

    if ($valid) {
      $context->expects($this->never())
        ->method('buildViolation');
    }
    else {
      $context->expects($this->once())
        ->method('buildViolation')
        ->willReturn($constraintViolationBuilder);
    }

    $constraint = new LinkAccessConstraint();
    $validate = new LinkAccessConstraintValidator($user);
    $validate->initialize($context);
    $validate->validate($link, $constraint);
  }

  /**
   * Data provider for LinkAccessConstraintValidator::validate().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for testValidate.
   *
   * @see \Drupal\Tests\link\LinkAccessConstraintValidatorTest::validate()
   */
  public static function providerValidate(): \Generator {
    yield [TRUE, TRUE, TRUE];
    yield [TRUE, FALSE, TRUE];
    yield [FALSE, TRUE, TRUE];
    yield [FALSE, FALSE, FALSE];
  }

}
