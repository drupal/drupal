<?php

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;

/**
 * Tests the ConfigurableLanguage entity.
 *
 * @group language
 * @coversDefaultClass \Drupal\language\ConfigurableLanguageManager
 */
class ConfigurableLanguageManagerTest extends LanguageTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user'];

  /**
   * The language negotiator.
   *
   * @var \Drupal\language\LanguageNegotiatorInterface
   */
  protected $languageNegotiator;

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequence']);
    $this->installEntitySchema('user');

    $this->languageNegotiator = $this->container->get('language_negotiator');
    $this->languageManager = $this->container->get('language_manager');
  }

  /**
   * @covers ::getLanguageSwitchLinks
   */
  public function testLanguageSwitchLinks() {
    $this->languageNegotiator->setCurrentUser($this->prophesize('Drupal\Core\Session\AccountInterface')->reveal());
    $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, new Url('<current>'));
  }

}
