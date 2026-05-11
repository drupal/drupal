<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;
use Drupal\locale\LocaleProjectRepository;
use Drupal\locale\LocaleTranslatableProject;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\locale\LocaleProjectRepository.
 */
#[CoversClass(LocaleProjectRepository::class)]
#[Group('locale')]
#[RunTestsInSeparateProcesses]
class LocaleProjectRepositoryTest extends UnitTestCase {

  /**
   * The local project storage.
   *
   * @var \Drupal\locale\LocaleProjectRepository
   */
  private LocaleProjectRepository $localeProjectRepository;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private CacheBackendInterface $cache;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueMemoryFactory
   */
  private KeyValueMemoryFactory $keyValueMemoryFactory;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private ModuleExtensionList $moduleExtensionList;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  private ThemeExtensionList $themeExtensionList;

  /**
   * The key value memory factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $config;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->cache = $this->createStub(CacheBackendInterface::class);
    $this->keyValueMemoryFactory = new KeyValueMemoryFactory();
    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->moduleExtensionList = $this->createStub(ModuleExtensionList::class);
    $this->themeExtensionList = $this->createStub(ThemeExtensionList::class);
    $this->config = $this->createStub(ConfigFactoryInterface::class);
    $this->localeProjectRepository = new LocaleProjectRepository(
      $this->cache,
      $this->keyValueMemoryFactory,
      $this->moduleHandler,
      $this->moduleExtensionList,
      $this->themeExtensionList,
      $this->config,
    );
  }

  /**
   * Tests that projects are sorted by weight and key.
   */
  public function testSorting(): void {
    // Add project 'b'.
    $b = new LocaleTranslatableProject('b', 'b', 'b', 'b', 'b');
    $this->localeProjectRepository->set($b);
    $this->assertSame(['b'], array_keys($this->localeProjectRepository->getAll()));

    // Add project 'c' and confirm alphabetical order.
    $c = new LocaleTranslatableProject('c', 'c', 'c', 'c', 'c');
    $this->localeProjectRepository->set($c);
    $this->assertSame(['b', 'c'], array_keys($this->localeProjectRepository->getAll()));

    // Add project 'a' and confirm 'a' is first.
    $a = new LocaleTranslatableProject('a', 'a', 'a', 'a', 'a');
    $this->localeProjectRepository->set($a);
    $this->assertSame(['a', 'b', 'c'], array_keys($this->localeProjectRepository->getAll()));

    // Add project 'd' with a negative weight and confirm 'd' is first.
    $d = new LocaleTranslatableProject('d', 'd', 'd', 'd', 'd', weight: -1);
    $this->localeProjectRepository->set($d);
    $this->assertSame(['d', 'a', 'b', 'c'], array_keys($this->localeProjectRepository->getAll()));

    // Add project 'aa' with a positive weight and confirm 'aa' is last.
    $aa = new LocaleTranslatableProject('aa', 'aa', 'aa', 'aa', 'aa', weight: 1);
    $this->localeProjectRepository->set($aa);
    $this->assertSame(['d', 'a', 'b', 'c', 'aa'], array_keys($this->localeProjectRepository->getAll()));

    // Delete project 'a'.
    $this->localeProjectRepository->deleteMultiple(['a']);
    $this->assertSame(['d', 'b', 'c', 'aa'], array_keys($this->localeProjectRepository->getAll()));

    // Add project 'e' with a lower negative weight than 'd' and confirm 'e' is
    // first.
    $e = new LocaleTranslatableProject('e', 'e', 'e', 'e', 'e', weight: -5);
    $this->localeProjectRepository->set($e);
    $this->assertSame(['e', 'd', 'b', 'c', 'aa'], array_keys($this->localeProjectRepository->getAll()));

    // Pretend there is a container rebuild by generating a new
    // LocaleProjectRepository object with the same data.
    $this->localeProjectRepository = new LocaleProjectRepository(
      $this->cache,
      $this->keyValueMemoryFactory,
      $this->moduleHandler,
      $this->moduleExtensionList,
      $this->themeExtensionList,
      $this->config,
    );
    $z = new LocaleTranslatableProject('z', 'z', 'z', 'z', 'z');
    $this->localeProjectRepository->set($z);
    $this->assertSame(['e', 'd', 'b', 'c', 'z', 'aa'], array_keys($this->localeProjectRepository->getAll()));

    // Now delete all projects.
    $this->localeProjectRepository->deleteAll();

    // Add project 'z' before project 'a' and confirm 'a' is first.
    $this->localeProjectRepository->set($z);
    $this->localeProjectRepository->set($a);
    $this->assertSame(['a', 'z'], array_keys($this->localeProjectRepository->getAll()));
  }

}
