<?php

namespace Drupal\Tests\system\Kernel\Token;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Base class for token replacement tests.
 */
abstract class TokenReplaceKernelTestBase extends EntityKernelTestBase {

  /**
   * The interface language.
   *
   * @var \Drupal\Core\Language\LanguageInterface
   */
  protected $interfaceLanguage;

  /**
   * Token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $tokenService;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system'];

  protected function setUp() {
    parent::setUp();
    // Install default system configuration.
    $this->installConfig(['system']);
    \Drupal::service('router.builder')->rebuild();

    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();
  }

}
