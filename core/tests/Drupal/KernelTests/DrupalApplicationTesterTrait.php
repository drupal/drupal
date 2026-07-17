<?php

declare(strict_types=1);

namespace Drupal\KernelTests;

use Drupal\Tests\Composer\ComposerIntegrationTrait;
use Symfony\Component\Console\Tester\ApplicationTester;

/**
 * Trait to help with testing the drupal console.
 */
trait DrupalApplicationTesterTrait {

  use ComposerIntegrationTrait;

  /**
   * Build our ApplicationTester.
   */
  private function applicationTester(array $context = []): ApplicationTester {
    $application = include static::binDir() . '/dr';
    $context['kernel.environment'] = 'testing';
    $context['kernel.allow_dumping'] = FALSE;
    $application = $application($context);
    $application->setAutoExit(FALSE);
    return new ApplicationTester($application);
  }

}
