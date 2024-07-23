<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\Core\Config\Action\ConfigActionManager;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * @group language
 */
class ConfigActionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  private readonly ConfigActionManager $configActionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig('language');
    $this->configActionManager = $this->container->get('plugin.manager.config_action');
  }

  public function testConfigActions(): void {
    $language = ConfigurableLanguage::load('en');
    $this->assertSame('English', $language->getName());
    $this->assertSame(0, $language->getWeight());

    $this->configActionManager->applyAction(
      'entity_method:language.entity:setName',
      $language->getConfigDependencyName(),
      'Wacky language',
    );
    $this->configActionManager->applyAction(
      'entity_method:language.entity:setWeight',
      $language->getConfigDependencyName(),
      39,
    );

    $language = ConfigurableLanguage::load('en');
    $this->assertSame('Wacky language', $language->getName());
    $this->assertSame(39, $language->getWeight());
  }

}
