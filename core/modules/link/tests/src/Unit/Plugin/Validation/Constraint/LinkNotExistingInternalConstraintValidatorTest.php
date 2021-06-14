<?php

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\Core\Url;
use Drupal\link\Plugin\Validation\Constraint\LinkNotExistingInternalConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkNotExistingInternalConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @coversDefaultClass \Drupal\link\Plugin\Validation\Constraint\LinkNotExistingInternalConstraintValidator
 * @group Link
 */
class LinkNotExistingInternalConstraintValidatorTest extends UnitTestCase {

  /**
   * @covers ::validate
   * @dataProvider providerValidate
   */
  public function testValidate($value, $valid) {
    $context = $this->createMock(ExecutionContextInterface::class);

    if ($valid) {
      $context->expects($this->never())
        ->method('addViolation');
    }
    else {
      $context->expects($this->once())
        ->method('addViolation');
    }

    $constraint = new LinkNotExistingInternalConstraint();

    $validator = new LinkNotExistingInternalConstraintValidator();
    $validator->initialize($context);
    $validator->validate($value, $constraint);
  }

  /**
   * Data provider for ::testValidate.
   */
  public function providerValidate() {
    $data = [];

    // External URL
    $data[] = [Url::fromUri('https://www.drupal.org'), TRUE];

    // Existing routed URL.
    $url = Url::fromRoute('example.existing_route');

    $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->with('example.existing_route', [], [])
      ->willReturn('/example/existing');
    $url->setUrlGenerator($url_generator);

    $data[] = [$url, TRUE];

    // Non-existent routed URL.
    $url = Url::fromRoute('example.not_existing_route');

    $url_generator = $this->createMock('Drupal\Core\Routing\UrlGeneratorInterface');
    $url_generator->expects($this->any())
      ->method('generateFromRoute')
      ->with('example.not_existing_route', [], [])
      ->willThrowException(new RouteNotFoundException());
    $url->setUrlGenerator($url_generator);

    $data[] = [$url, FALSE];

    foreach ($data as &$single_data) {
      $link = $this->createMock('Drupal\link\LinkItemInterface');
      $link->expects($this->any())
        ->method('getUrl')
        ->willReturn($single_data[0]);

      $single_data[0] = $link;
    }

    return $data;
  }

  /**
   * @covers ::validate
   *
   * @see \Drupal\Core\Url::fromUri
   */
  public function testValidateWithMalformedUri() {
    $link = $this->createMock('Drupal\link\LinkItemInterface');
    $link->expects($this->any())
      ->method('getUrl')
      ->willThrowException(new \InvalidArgumentException());

    $context = $this->createMock(ExecutionContextInterface::class);
    $context->expects($this->never())
      ->method('addViolation');

    $constraint = new LinkNotExistingInternalConstraint();

    $validator = new LinkNotExistingInternalConstraintValidator();
    $validator->initialize($context);
    $validator->validate($link, $constraint);
  }

}
