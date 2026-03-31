<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Password;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\KernelTests\KernelTestBase;

/**
 * Integration tests for the PHP password hashing service.
 */
abstract class PasswordTestBase extends KernelTestBase {

  /**
   * The password algorithm to be used during this test.
   */
  protected ?string $passwordAlgorithm = NULL;

  /**
   * The password options to be used during this test.
   */
  protected ?array $passwordOptions = [];

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    // The password.* kernel parameters are modified by parent::register().
    // Ensure this test is operating with the parameters set by the subclass.
    // Falls back to the original defaults if they are left unset.
    $originalAlgorithm = $container->getParameter('password.algorithm');
    $originalOptions = $container->getParameter('password.options');
    parent::register($container);
    $container->setParameter('password.algorithm', $this->passwordAlgorithm ?? $originalAlgorithm);
    $container->setParameter('password.options', $this->passwordOptions ?? $originalOptions);
  }

  /**
   * Checks system runtime requirements.
   *
   * @return array
   *   An array of system requirements.
   */
  protected function checkSystemRequirements() {
    // This loadInclude() is to ensure that the install API is available.
    // Since we're loading an include of type 'install', this will also
    // include core/includes/install.inc for us, which is where
    // drupal_verify_install_file() is currently defined.
    // @todo Remove this once the function lives in a better place.
    // @see https://www.drupal.org/project/drupal/issues/3526388
    $this->container->get('module_handler')->loadInclude('system', 'install');
    return \Drupal::moduleHandler()->invoke('system', 'runtime_requirements');
  }

}
