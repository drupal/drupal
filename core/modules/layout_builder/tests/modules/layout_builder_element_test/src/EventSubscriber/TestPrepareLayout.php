<?php

namespace Drupal\layout_builder_element_test\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\Event\PrepareLayoutEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Drupal\layout_builder\LayoutTempstoreRepositoryInterface;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * An event subscriber to test altering section storage via the
 * \Drupal\layout_builder\Event\PrepareLayoutEvent.
 *
 * @see \Drupal\layout_builder\Event\PrepareLayoutEvent
 * @see \Drupal\layout_builder\Element\LayoutBuilder::prepareLayout()
 */
class TestPrepareLayout implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The layout tempstore repository.
   *
   * @var \Drupal\layout_builder\LayoutTempstoreRepositoryInterface
   */
  protected $layoutTempstoreRepository;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new TestPrepareLayout.
   *
   * @param \Drupal\layout_builder\LayoutTempstoreRepositoryInterface $layout_tempstore_repository
   *   The tempstore repository.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(LayoutTempstoreRepositoryInterface $layout_tempstore_repository, MessengerInterface $messenger) {
    $this->layoutTempstoreRepository = $layout_tempstore_repository;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Act before core's layout builder subscriber.
    $events[LayoutBuilderEvents::PREPARE_LAYOUT][] = ['onBeforePrepareLayout', 20];
    // Act after core's layout builder subscriber.
    $events[LayoutBuilderEvents::PREPARE_LAYOUT][] = ['onAfterPrepareLayout', -10];
    return $events;
  }

  /**
   * Subscriber to test acting before the LB subscriber.
   *
   * @param \Drupal\layout_builder\Event\PrepareLayoutEvent $event
   *   The prepare layout event.
   */
  public function onBeforePrepareLayout(PrepareLayoutEvent $event) {
    $section_storage = $event->getSectionStorage();
    $context = $section_storage->getContextValues();

    if (!empty($context['entity'])) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $context['entity'];

      // Node 1 or 2: Append a block to the layout.
      if (in_array($entity->id(), ['1', '2'])) {
        $section = new Section('layout_onecol');
        $section->appendComponent(new SectionComponent('fake-uuid', 'content', [
          'id' => 'static_block',
          'label' => 'Test static block title',
          'label_display' => 'visible',
          'provider' => 'fake_provider',
        ]));
        $section_storage->appendSection($section);
      }

      // Node 2: Stop event propagation.
      if ($entity->id() === '2') {
        $event->stopPropagation();
      }
    }
  }

  /**
   * Subscriber to test acting after the LB subscriber.
   *
   * @param \Drupal\layout_builder\Event\PrepareLayoutEvent $event
   *   The prepare layout event.
   */
  public function onAfterPrepareLayout(PrepareLayoutEvent $event) {
    $section_storage = $event->getSectionStorage();
    $context = $section_storage->getContextValues();

    if (!empty($context['entity'])) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $context['entity'];

      // Node 1, 2, or 3: Append a block to the layout.
      if (in_array($entity->id(), ['1', '2', '3'])) {
        $section = new Section('layout_onecol');
        $section->appendComponent(new SectionComponent('fake-uuid', 'content', [
          'id' => 'static_block_two',
          'label' => 'Test second static block title',
          'label_display' => 'visible',
          'provider' => 'fake_provider',
        ]));
        $section_storage->appendSection($section);
      }
    }
  }

}
