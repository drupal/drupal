<?php

namespace Drupal\layout_builder_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\layout_builder\Event\SectionBuildRenderArrayEvent;
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
    // @see
    return [
      SectionBuildRenderArrayEvent::class => [
        ['beforeBuildRegionRenderArray', 200],
        ['afterBuildRegionRenderArray', 0],
      ],
    ];
  }

  /**
   * Reacts before layout_builder is building the regions.
   *
   * @param \Drupal\layout_builder\Event\SectionBuildRenderArrayEvent $event
   *   The event.
   */
  public function beforeBuildRegionRenderArray(SectionBuildRenderArrayEvent $event): void {
    if ($this->isSubscriberEnabled()) {
      $regions = $event->getRegions();
      $regions['main']['before'] = [
        '#markup' => '3rd party: before',
      ];
      $event->setRegions($regions);
    }
  }

  /**
   * Reacts before layout_builder is building the regions.
   *
   * @param \Drupal\layout_builder\Event\SectionBuildRenderArrayEvent $event
   *   The event.
   */
  public function afterBuildRegionRenderArray(SectionBuildRenderArrayEvent $event): void {
    if ($this->isSubscriberEnabled()) {
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
   * Checks if the subscriber is enabled.
   *
   * @return bool
   *   If the subscriber is enabled.
   */
  protected function isSubscriberEnabled(): bool {
    return $this->state->get('layout_test.subscriber.enabled', FALSE);
  }

}
