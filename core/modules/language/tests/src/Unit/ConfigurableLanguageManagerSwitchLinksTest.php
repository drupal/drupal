<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageDefault;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\language\Config\LanguageConfigFactoryOverrideInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\language\LanguageNegotiationMethodInterface;
use Drupal\language\LanguageNegotiatorInterface;
use Drupal\language\LanguageSwitcherInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests getting the language switch links works correctly running in fibers.
 */
#[CoversClass(ConfigurableLanguageManager::class)]
#[Group('language')]
class ConfigurableLanguageManagerSwitchLinksTest extends UnitTestCase {

  /**
   * Test that change of negotiated languages stays in getLanguageSwitchLinks().
   */
  public function testSwitchLinksFiberConcurrency(): void {
    // Mock two languages and a URL per language for use in the language switch
    // links.
    foreach (['de', 'en'] as $langcode) {
      $languages[$langcode] = $this->createMock('\Drupal\Core\Language\LanguageInterface');
      $languages[$langcode]->method('getId')
        ->willReturn($langcode);

      // ConfigurableLanguageManager::getLanguageSwitchLinks() changes the
      // negotiated language to the language of each link before checking access
      // to the link's URL. If this is running in a fiber, it is possible that
      // calling ::access() on the URL object can suspend the fiber, if entities
      // are loaded as part of the access check. Simulate this in the mock URL
      // object by forcing the fiber to be suspended in ::access();
      $urls[$langcode] = $this->createMock(Url::class);
      $urls[$langcode]->method('access')
        ->willReturnCallback(function () {
          if (\Fiber::getCurrent() !== NULL) {
            \Fiber::suspend();
          }
          return TRUE;
        });
    }

    // Create mock objects needed to instantiate ConfigurableLanguageManager.
    $negotiationMethod = $this->createMockForIntersectionOfInterfaces([
      LanguageNegotiationMethodInterface::class,
      LanguageSwitcherInterface::class,
    ]);
    $negotiationMethod->method('getLanguageSwitchLinks')
      ->willReturnCallback(function () use ($languages, $urls) {
        $links = [];
        foreach ($languages as $langcode => $language) {
          $links[$langcode] = [
            'language' => $language,
            'url' => $urls[$langcode],
          ];
        }
        return $links;
      });

    $negotiator = $this->createMock(LanguageNegotiatorInterface::class);
    $negotiator->method('getNegotiationMethods')
      ->with(LanguageInterface::TYPE_INTERFACE)
      ->willReturn([
        'language-test-fiber' => [
          'class' => $negotiationMethod::class,
        ],
      ]);
    $negotiator->method('getNegotiationMethodInstance')
      ->with('language-test-fiber')
      ->willReturn($negotiationMethod);

    $defaultLanguage = $this->createMock(LanguageDefault::class);
    $defaultLanguage->method('get')
      ->willReturn($languages['en']);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->method('listAll')
      ->willReturn([]);
    $configFactory->method('loadMultiple')
      ->willReturn([]);

    $requestStack = $this->createMock(RequestStack::class);
    $requestStack->method('getCurrentRequest')
      ->willReturn($this->createStub(Request::class));

    // Instantiate the language manager and initialize the negotiator.
    $languageManager = new ConfigurableLanguageManager(
      $defaultLanguage,
      $configFactory,
      $this->createStub(ModuleHandlerInterface::class),
      $this->createStub(LanguageConfigFactoryOverrideInterface::class),
      $requestStack,
      $this->createStub(CacheBackendInterface::class),
    );
    $languageManager->setNegotiator($negotiator);
    // Initialize the negotiated languages.
    $originalLanguage = $languageManager->getCurrentLanguage();

    // Simulate ConfigurableLanguageManager::getLanguageSwitchLinks() running in
    // a fiber in parallel with another process running in a fiber. The second
    // fiber just returns the value of the current language, which should not
    // be from the original language when the second fiber is running,
    // regardless of whether the first fiber suspended while checking access on
    // the link URLs.
    $fibers[] = new \Fiber(fn () => $languageManager->getLanguageSwitchLinks(LanguageInterface::TYPE_INTERFACE, $this->createMock(Url::class)));
    $fibers[] = new \Fiber(fn () => $languageManager->getCurrentLanguage()->getId());
    $return = [];
    // Process fibers until all complete.
    do {
      foreach ($fibers as $key => $fiber) {
        if (!$fiber->isStarted()) {
          $fiber->start();
        }
        elseif ($fiber->isSuspended()) {
          $fiber->resume();
        }
        elseif ($fiber->isTerminated()) {
          $return[$key] = $fiber->getReturn();
          unset($fibers[$key]);
        }
      }
    } while (!empty($fibers));

    // Confirm that the switch links are generated correctly from the first
    // fiber.
    $expectedLinks = [
      'de' => [
        'language' => $languages['de'],
        'url' => $urls['de'],
      ],
      'en' => [
        'language' => $languages['en'],
        'url' => $urls['en'],
      ],
    ];
    $this->assertEquals($expectedLinks, $return[0]->links);
    // Confirm that the original language matches current language when the
    // second fiber ran.
    $this->assertSame($originalLanguage->getId(), $return[1]);
  }

}
