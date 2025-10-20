<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\link\Plugin\Validation\Constraint\LinkExternalProtocolsConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkExternalProtocolsConstraintValidator;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

/**
 * Tests Drupal\link\Plugin\Validation\Constraint\LinkExternalProtocolsConstraintValidator.
 */
#[CoversClass(LinkExternalProtocolsConstraintValidator::class)]
#[Group('Link')]
class LinkExternalProtocolsConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests validate.
   *
   * @legacy-covers ::validate
   */
  #[DataProvider('providerValidate')]
  #[RunInSeparateProcess]
  public function testValidate($url, $valid): void {
    $link = $this->createMock('Drupal\link\LinkItemInterface');
    $link->expects($this->any())
      ->method('getUrl')
      ->willReturn(Url::fromUri($url));
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

    // Setup some more allowed protocols.
    UrlHelper::setAllowedProtocols(['http', 'https', 'magnet']);

    $constraint = new LinkExternalProtocolsConstraint();

    $validator = new LinkExternalProtocolsConstraintValidator();
    $validator->initialize($context);
    $validator->validate($link, $constraint);
  }

  /**
   * Data provider for ::testValidate.
   */
  public static function providerValidate() {
    $data = [];

    // Test allowed protocols.
    $data[] = ['http://www.example.com', TRUE];
    $data[] = ['https://www.example.com', TRUE];
    // cSpell:disable-next-line
    $data[] = ['magnet:?xt=urn:sha1:YNCKHTQCWBTRNJIV4WNAE52SJUQCZO5C', TRUE];

    // Invalid protocols.
    $data[] = ['ftp://ftp.funet.fi/pub/standards/RFC/rfc959.txt', FALSE];

    return $data;
  }

  /**
   * Tests validate with malformed uri.
   *
   * @see \Drupal\Core\Url::fromUri
   * @legacy-covers ::validate
   */
  public function testValidateWithMalformedUri(): void {
    $link = $this->createMock('Drupal\link\LinkItemInterface');
    $link->expects($this->any())
      ->method('getUrl')
      ->willThrowException(new \InvalidArgumentException());

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');

    $constraint = new LinkExternalProtocolsConstraint();

    $validator = new LinkExternalProtocolsConstraintValidator();
    $validator->initialize($context);
    $validator->validate($link, $constraint);
  }

  /**
   * Tests validate ignores internal urls.
   *
   * @legacy-covers ::validate
   */
  public function testValidateIgnoresInternalUrls(): void {
    $link = $this->createMock('Drupal\link\LinkItemInterface');
    $link->expects($this->any())
      ->method('getUrl')
      ->willReturn(Url::fromRoute('example.test'));

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('buildViolation');

    $constraint = new LinkExternalProtocolsConstraint();

    $validator = new LinkExternalProtocolsConstraintValidator();
    $validator->initialize($context);
    $validator->validate($link, $constraint);
  }

}
