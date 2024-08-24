<?php

declare(strict_types=1);

namespace Drupal\services_defaults_test;

/**
 * An autowired service to test _defaults.
 */
class TestService {

  /**
   * @var \Drupal\services_defaults_test\TestInjectionInterface
   */
  protected $testInjection;

  /**
   * @var \Drupal\services_defaults_test\TestInjection2
   */
  protected $testInjection2;

  public function __construct(TestInjectionInterface $test_injection, TestInjection2 $test_injection2) {
    $this->testInjection = $test_injection;
    $this->testInjection2 = $test_injection2;
  }

  public function getTestInjection() {
    return $this->testInjection;
  }

  public function getTestInjection2() {
    return $this->testInjection2;
  }

}
