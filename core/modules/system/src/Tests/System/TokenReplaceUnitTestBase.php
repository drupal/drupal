<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\TokenReplaceUnitTestBase.
 */

namespace Drupal\system\Tests\System;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Base class for token replacement tests.
 */
abstract class TokenReplaceUnitTestBase extends EntityUnitTestBase {

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
  public static $modules = array('system');

  public function setUp() {
    parent::setUp();
    // Install default system configuration.
    $this->installConfig(array('system'));

    $this->interfaceLanguage = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();
  }

}
