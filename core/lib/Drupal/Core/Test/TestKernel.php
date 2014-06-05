<?php

/**
 * @file
 * Contains \Drupal\Core\Test\TestKernel.
 */

namespace Drupal\Core\Test;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Installer\InstallerServiceProvider;
use Composer\Autoload\ClassLoader;

/**
 * Kernel for run-tests.sh.
 */
class TestKernel extends DrupalKernel {

  /**
   * Constructs a TestKernel.
   *
   * @param \Composer\Autoload\ClassLoader $class_loader
   *   The classloader.
   */
  public function __construct(ClassLoader $class_loader) {
    parent::__construct('test_runner', $class_loader, FALSE);

    // Prime the module list and corresponding Extension objects.
    // @todo Remove System module. Needed because \Drupal\Core\Datetime\Date
    //   has a (needless) dependency on the 'date_format' entity, so calls to
    //   format_date()/format_interval() cause a plugin not found exception.
    $this->moduleList = array(
      'system' => 0,
      'simpletest' => 0,
    );
    $this->moduleData = array(
      'system' => new Extension('module', 'core/modules/system/system.info.yml', 'system.module'),
      'simpletest' => new Extension('module', 'core/modules/simpletest/simpletest.info.yml', 'simpletest.module'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function discoverServiceProviders() {
    parent::discoverServiceProviders();
    // The test runner does not require an installed Drupal site to exist.
    // Therefore, its environment is identical to that of the early installer.
    $this->serviceProviderClasses['app']['Test'] = 'Drupal\Core\Installer\InstallerServiceProvider';
  }

}
