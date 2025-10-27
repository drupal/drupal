<?php

namespace Drupal\Core\Test;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a kernel used for running Functional tests and run-tests.sh.
 *
 * @internal
 */
class TestRunnerKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  public static function createFromRequest(Request $request, $class_loader, $environment = 'test_runner', $allow_dumping = TRUE, $app_root = NULL) {
    return parent::createFromRequest($request, $class_loader, $environment, $allow_dumping, $app_root);
  }

  /**
   * {@inheritdoc}
   */
  public function __construct($environment, $class_loader, $allow_dumping = FALSE, $app_root = NULL) {
    // Force $allow_dumping to FALSE, because the test runner kernel should
    // always have to rebuild its container, and potentially avoid isolation
    // issues against the tests.
    parent::__construct($environment, $class_loader, FALSE, $app_root);
  }

  /**
   * {@inheritdoc}
   */
  public function boot() {
    // Ensure that required Settings exist.
    if (!Settings::getAll()) {
      new Settings([
        'hash_salt' => 'run-tests',
        'container_yamls' => [],
        // If there is no settings.php, then there is no parent site. In turn,
        // there is no public files directory; use a custom public files path.
        'file_public_path' => 'sites/default/files',
      ]);
    }

    // Remove Drupal's error/exception handlers; they are designed for HTML
    // and there is no storage nor a (watchdog) logger here.
    if (get_error_handler() === '_drupal_error_handler') {
      restore_error_handler();
    }
    restore_exception_handler();

    // In addition, ensure that PHP errors are not hidden away in logs.
    ini_set('display_errors', TRUE);

    // This container is never going to be dumped and therefore it needs a
    // rebuild. Setting this flag avoids trying to load the container from
    // cache.
    $this->containerNeedsRebuild = TRUE;

    parent::boot();

    $this->getContainer()->get('module_handler')->loadAll();

    $test_discovery = new TestDiscovery(
      $this->getContainer()->getParameter('app.root'),
      $this->getContainer()->get('class_loader')
    );
    $test_discovery->registerTestNamespaces();

    // Register stream wrappers.
    $this->getContainer()->get('stream_wrapper_manager')->register();

    // Create the build/artifacts directory if necessary.
    if (!is_dir('public://simpletest') && !@mkdir('public://simpletest', 0777, TRUE) && !is_dir('public://simpletest')) {
      throw new \RuntimeException('Unable to create directory: public://simpletest');
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function discoverServiceProviders() {
    parent::discoverServiceProviders();
    // The test runner does not require an installed Drupal site to exist.
    // Therefore, its environment is identical to that of the early installer.
    $this->serviceProviderClasses['app']['Test'] = 'Drupal\Core\Installer\InstallerServiceProvider';
    return $this->serviceProviderClasses;
  }

}
