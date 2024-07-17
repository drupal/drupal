<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * Tests PluginNotFoundException.
 *
 * @group language
 */
class LanguageNegotiatorPluginTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language', 'user'];

  /**
   * Tests for PluginNotFoundException.
   */
  public function testLanguageNegotiatorNoPlugin(): void {
    $logger = new BufferingLogger();
    $logger_factory = $this->createMock(LoggerChannelFactory::class);
    $logger_factory->expects($this->once())
      ->method('get')
      ->with('language')
      ->willReturn($logger);
    $this->container->set('logger.factory', $logger_factory);
    $this->installEntitySchema('user');

    // Test unavailable plugin.
    $config = $this->config('language.types');
    $config->set('configurable', [LanguageInterface::TYPE_URL]);
    $config->set('negotiation.language_url.enabled', [
      self::CLASS => -3,
    ]);
    $config->save();
    $languageNegotiator = $this->container->get('language_negotiator');
    $languageNegotiator->setCurrentUser($this->prophesize('Drupal\Core\Session\AccountInterface')->reveal());
    try {
      $languageNegotiator->initializeType(LanguageInterface::TYPE_URL);
    }
    catch (PluginNotFoundException) {
      $this->fail('Plugin not found exception unhandled.');
    }
    $log_message = $logger->cleanLogs()[0];
    $this->assertEquals('error', $log_message[0]);
    $this->assertStringContainsString('The "Drupal\Tests\language\Kernel\LanguageNegotiatorPluginTest" plugin does not exist.', $log_message[1]);
  }

}
