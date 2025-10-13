<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\ConfigurableLanguageManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the ConfigurableLanguage entity.
 */
#[CoversClass(ConfigurableLanguageManager::class)]
#[Group('language')]
#[RunTestsInSeparateProcesses]
class ConfigurableLanguageManagerTest extends LanguageTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user'];

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
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');

    $this->languageNegotiator = $this->container->get('language_negotiator');
    $this->languageManager = $this->container->get('language_manager');
  }

  /**
   * Tests language switch links.
   *
   * @legacy-covers ::getLanguageSwitchLinks
   */
  public function testLanguageSwitchLinks(): void {
    $this->languageNegotiator->setCurrentUser($this->prophesize('Drupal\Core\Session\AccountInterface')->reveal());
    $this->languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, new Url('<current>'));
  }

}
