<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\TokenReplaceUnitTestBase.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\Language\Language;
use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Test token replacement in strings.
 */
class TokenReplaceUnitTestBase extends EntityUnitTestBase {

  /**
   * The interface language.
   *
   * @var \Drupal\Core\Language\Language
   */
  protected $languageInterface;

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

    $this->languageInterface = \Drupal::languageManager()->getCurrentLanguage();
    $this->tokenService = \Drupal::token();
  }

}
