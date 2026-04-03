<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\filter\FilterFormatRepository;
use Drupal\filter\FilterFormatRepositoryInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the Drupal\filter\FilterFormatRepositoryInterface service.
 */
#[CoversClass(FilterFormatRepository::class)]
#[Group('filter')]
#[RunTestsInSeparateProcesses]
class FilterFormatRepositoryTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'filter',
    'system',
    'user',
  ];

  /**
   * The filter formats repository to be tested.
   */
  protected FilterFormatRepositoryInterface $repository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['filter']);

    FilterFormat::create(['name' => 'Foo', 'format' => 'foo', 'weight' => 20])->save();
    FilterFormat::create(['name' => 'Bar', 'format' => 'bar', 'weight' => -10])->save();

    $this->repository = $this->container->get(FilterFormatRepositoryInterface::class);
  }

  /**
   * @legacy-covers ::getAllFormats
   */
  public function testGetAllFormats(): void {
    // The 'plain_text' format weight is 10.
    $this->assertSame(['bar', 'plain_text', 'foo'], array_keys($this->repository->getAllFormats()));
  }

  /**
   * @legacy-covers ::getFormatsForAccount
   */
  public function testGetFormatsForAccount(): void {
    $account = $this->createUser(['use text format foo']);
    $actual = $this->repository->getFormatsForAccount($account);

    // User has access to 'foo' based on their permissions but 'plain_text',
    // with weight 10, is also available because it's the fallback format.
    $this->assertSame(['plain_text', 'foo'], array_keys($actual));
  }

  /**
   * @legacy-covers ::getFormatsByRole
   */
  public function testGetFormatsByRole(): void {
    Role::create([
      'id' => 'role1',
      'label' => 'Role 1',
      'permissions' => ['use text format foo'],
    ])->save();
    $this->createUser(values: ['roles' => ['role1']])->save();
    // The 'plain_text' format weight is 10.
    $this->assertSame(['plain_text', 'foo'], array_keys($this->repository->getFormatsByRole('role1')));
  }

  /**
   * @legacy-covers ::getDefaultFormat
   */
  public function testGetDefaultFormatWithAccount(): void {
    $account = $this->createUser(['use text format foo', 'use text format bar']);
    $this->assertSame('bar', $this->repository->getDefaultFormat($account)->id());
    $this->assertSame('plain_text', $this->repository->getDefaultFormat()->id());
  }

  /**
   * @legacy-covers ::getFallbackFormatId
   */
  public function testGetFallbackFormatId(): void {
    $this->config('filter.settings')->set('fallback_format', 'bar')->save();
    $this->assertSame('bar', $this->repository->getFallbackFormatId());
  }

}
