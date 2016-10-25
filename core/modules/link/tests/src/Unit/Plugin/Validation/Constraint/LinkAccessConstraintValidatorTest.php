<?php

namespace Drupal\Tests\link\Unit\Plugin\Validation\Constraint;

use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraint;
use Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraintValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Tests the LinkAccessConstraintValidator validator.
 *
 * @coversDefaultClass \Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraintValidator
 * @group validation
 */
class LinkAccessConstraintValidatorTest extends UnitTestCase {

  /**
   * Tests the \Drupal\link\Plugin\Validation\Constraint\LinkAccessConstraintValidator::validate()
   * method.
   *
   * @param \Drupal\link\LinkItemInterface $value
   *   The link item.
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *   The user account.
   * @param bool $valid
   *   A boolean indicating if the combination is expected to be valid.
   *
   * @covers ::validate
   * @dataProvider providerValidate
   */
  public function testValidate($value, $user, $valid) {
    $context = $this->getMock(ExecutionContextInterface::class);

    if ($valid) {
      $context->expects($this->never())
        ->method('addViolation');
    }
    else {
      $context->expects($this->once())
        ->method('addViolation');
    }

    $constraint = new LinkAccessConstraint();

    $validate = new LinkAccessConstraintValidator($user);
    $validate->initialize($context);
    $validate->validate($value, $constraint);
  }

  /**
   * Data provider for LinkAccessConstraintValidator::validate().
   *
   * @return array
   *   An array of tests, matching the parameter inputs for testValidate.
   *
   * @see \Drupal\Tests\link\LinkAccessConstraintValidatorTest::validate()
   */
  public function providerValidate() {
    $data = [];

    $cases = [
      ['may_link_any_page' => TRUE, 'url_access' => TRUE, 'valid' => TRUE],
      ['may_link_any_page' => TRUE, 'url_access' => FALSE, 'valid' => TRUE],
      ['may_link_any_page' => FALSE, 'url_access' => TRUE, 'valid' => TRUE],
      ['may_link_any_page' => FALSE, 'url_access' => FALSE, 'valid' => FALSE],
    ];

    foreach ($cases as $case) {
      // Mock a Url object that returns a boolean indicating user access.
      $url = $this->getMockBuilder('Drupal\Core\Url')
        ->disableOriginalConstructor()
        ->getMock();
      $url->expects($this->once())
        ->method('access')
        ->willReturn($case['url_access']);
      // Mock a link object that returns the URL object.
      $link = $this->getMock('Drupal\link\LinkItemInterface');
      $link->expects($this->any())
        ->method('getUrl')
        ->willReturn($url);
      // Mock a user object that returns a boolean indicating user access to all
      // links.
      $user = $this->getMock('Drupal\Core\Session\AccountProxyInterface');
      $user->expects($this->any())
        ->method('hasPermission')
        ->with($this->equalTo('link to any page'))
        ->willReturn($case['may_link_any_page']);

      $data[] = [$link, $user, $case['valid']];
    }

    return $data;
  }

}
