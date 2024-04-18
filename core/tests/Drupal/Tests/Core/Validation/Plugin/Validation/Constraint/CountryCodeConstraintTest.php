<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Validation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Validation\Plugin\Validation\Constraint\CountryCodeConstraint;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\Validator\Constraints\Choice;

/**
 * @covers \Drupal\Core\Validation\Plugin\Validation\Constraint\CountryCodeConstraint
 * @group validation
 */
class CountryCodeConstraintTest extends UnitTestCase {

  public function testConstraintLoadsChoicesFromCountryManager(): void {
    $countries = [
      'US' => 'United States',
      'CA' => 'Canada',
      'MX' => 'Mexico',
    ];
    // The CountryCode constraint should call the country manager's getList()
    // method to compile a list of valid choices.
    $country_manager = $this->createMock(CountryManagerInterface::class);
    $country_manager->expects($this->atLeastOnce())
      ->method('getList')
      ->willReturn($countries);
    $container = new ContainerBuilder();
    $container->set(CountryManagerInterface::class, $country_manager);

    $constraint = CountryCodeConstraint::create($container, [], 'CountryCode', []);
    $this->assertInstanceOf(Choice::class, $constraint);
    $this->assertSame(array_keys($countries), $constraint->choices);
  }

}
