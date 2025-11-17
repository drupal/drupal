<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DefaultContent;

use Drupal\Core\DefaultContent\Finder;
use Drupal\Core\DefaultContent\Importer;
use Drupal\Core\DefaultContent\PreEntityImportEvent;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the Default Content Importer.
 */
#[Group('DefaultContent')]
#[CoversClass(Importer::class)]
#[RunTestsInSeparateProcesses]
class ImporterTest extends KernelTestBase implements EventSubscriberInterface {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'entity_test'];

  /**
   * Whether the imported content entities were syncing.
   *
   * @var bool
   *
   * @see ::onPreSave()
   */
  private bool $wasSyncing = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test');

    $this->container->get(EventDispatcherInterface::class)
      ->addSubscriber($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PreEntityImportEvent::class => 'onPreEntityImport',
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_presave() for entity_test entities.
   */
  #[Hook('entity_test_presave')]
  public function onPreSave(ContentEntityInterface $entity): void {
    $this->wasSyncing = $entity->isSyncing();
  }

  /**
   * Modify entity data before it is imported.
   *
   * @param \Drupal\Core\DefaultContent\PreEntityImportEvent $event
   *   The event being handled.
   */
  public function onPreEntityImport(PreEntityImportEvent $event): void {
    self::assertSame('entity_test', $event->metadata['entity_type']);

    if ($event->metadata['uuid'] === '01234567-89ab-cdef-0123-456789abcdef') {
      $event->data['default']['name'] = [
        ['value' => 'Changed name'],
      ];
    }
    // Anything we put in `_meta` should be discarded.
    $event->data['_meta']['entity_type'] = 'This will be ignored.';
  }

  /**
   * Tests changing entity data upon import.
   */
  public function testChangeDataOnImport(): void {
    $this->setUpCurrentUser(admin: TRUE);

    $finder = new Finder($this->getDrupalRoot() . '/core/tests/fixtures/pre_entity_import_default_content');
    $this->container->get(Importer::class)->importContent($finder);
    self::assertTrue($this->wasSyncing);

    $entity = $this->container->get(EntityRepositoryInterface::class)
      ->loadEntityByUuid('entity_test', '01234567-89ab-cdef-0123-456789abcdef');
    self::assertSame('Changed name', $entity?->label());
  }

}
