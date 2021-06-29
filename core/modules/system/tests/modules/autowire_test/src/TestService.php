<?php

namespace Drupal\autowire_test;

class TestService {

  /**
   * @var \Drupal\autowire_test\TestInjectionInterface
   */
  protected $testInjection;

  /**
   * @var \Drupal\autowire_test\TestInjection2
   */
  protected $testInjection2;

  public function __construct(TestInjectionInterface $test_injection, TestInjection2 $test_injection2) {
    $this->testInjection = $test_injection;
    $this->testInjection2 = $test_injection2;
  }

  public function getTestInjection(): TestInjectionInterface {
    return $this->testInjection;
  }

  public function getTestInjection2(): TestInjection2 {
    return $this->testInjection2;
  }

}
