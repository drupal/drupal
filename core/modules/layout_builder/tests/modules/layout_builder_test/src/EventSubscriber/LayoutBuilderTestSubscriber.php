<?php

namespace Drupal\layout_builder_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Event\SectionBuildRegionsRenderArrayEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LayoutBuilderTestSubscriber implements EventSubscriberInterface {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new event subscriber instance.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Add two subscribers, one to run before core subscriber and one after.
    // @see \Drupal\layout_builder\EventSubscriber\SectionRegionsRenderArraySubscriber
    return [
      SectionBuildRegionsRenderArrayEvent::class => [
        ['beforeBuildRegionsRenderArray', 200],
        ['afterBuildRegionsRenderArray', 0],
      ],
    ];
  }

  /**
   * Reacts before layout_builder is building the regions.
   *
   * @param \Drupal\layout_builder\Event\SectionBuildRegionsRenderArrayEvent $event
   *   The event object.
   */
  public function beforeBuildRegionsRenderArray(SectionBuildRegionsRenderArrayEvent $event): void {
    if ($this->isActivated()) {
      $regions = $event->getRegions();
      $regions['main']['before'] = [
        '#markup' => '3rd party: before',
      ];
      $event->setRegions($regions);
    }
  }

  /**
   * Reacts after layout_builder is building the regions.
   *
   * @param \Drupal\layout_builder\Event\SectionBuildRegionsRenderArrayEvent $event
   *   The event object.
   */
  public function afterBuildRegionsRenderArray(SectionBuildRegionsRenderArrayEvent $event): void {
    if ($this->isActivated()) {
      $regions = $event->getRegions();
      $regions['main']['after'] = [
        '#markup' => '3rd party: after',
      ];
      // Alter also content provided by the core's subscriber.
      $regions['main']['first-uuid']['content']['#markup'] = '3rd party: replaced';
      $event->setRegions($regions);
    }
  }

  /**
   * Checks if the subscriber is activated.
   *
   * @return bool
   *   If the subscriber is active.
   */
  protected function isActivated(): bool {
    return $this->state->get('layout_builder_test.subscriber.active', FALSE);
  }

}
