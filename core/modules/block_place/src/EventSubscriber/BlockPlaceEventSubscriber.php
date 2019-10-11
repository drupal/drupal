<?php

namespace Drupal\block_place\EventSubscriber;

@trigger_error('The ' . __NAMESPACE__ . '\BlockPlaceEventSubscriber is
  deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Using Layout
  Builder (available in Drupal 8.7.0) module is recommended. See
  https://www.drupal.org/node/3081957. Alternatively you may use the
  contrib module Place Blocks.', E_USER_DEPRECATED);

use Drupal\Core\Render\PageDisplayVariantSelectionEvent;
use Drupal\Core\Render\RenderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Session\AccountInterface;

/**
 * @see \Drupal\block_place\Plugin\DisplayVariant\PlaceBlockPageVariant
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
 * Using Layout Builder (available in Drupal 8.7.0) module is recommended.
 * Alternatively you may use the contrib module Place Blocks.
 *
 * @see https://www.drupal.org/node/3081957
 */
class BlockPlaceEventSubscriber implements EventSubscriberInterface {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * Constructs a \Drupal\block_place\EventSubscriber\BlockPlaceEventSubscriber object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack used to retrieve the current request.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   */
  public function __construct(RequestStack $request_stack, AccountInterface $account) {

    @trigger_error('The ' . __NAMESPACE__ . '\BlockPlaceEventSubscriber is
      deprecated in drupal:8.8.0 and is removed from drupal:9.0.0. Using
      Layout Builder (available in Drupal 8.7.0) module is recommended.
      See https://www.drupal.org/node/3081957. Alternatively you may use the
      contrib module Place Blocks.', E_USER_DEPRECATED);

    $this->requestStack = $request_stack;
    $this->account = $account;
  }

  /**
   * Selects the block place override of the block page display variant.
   *
   * @param \Drupal\Core\Render\PageDisplayVariantSelectionEvent $event
   *   The event to process.
   */
  public function onBlockPageDisplayVariantSelected(PageDisplayVariantSelectionEvent $event) {
    if ($event->getPluginId() === 'block_page') {
      if ($this->requestStack->getCurrentRequest()->query->has('block-place') && $this->account->hasPermission('administer blocks')) {
        $event->setPluginId('block_place_page');
      }
      $event->addCacheContexts(['user.permissions', 'url.query_args']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a very low priority, so that it runs last.
    $events[RenderEvents::SELECT_PAGE_DISPLAY_VARIANT][] = ['onBlockPageDisplayVariantSelected', -1000];
    return $events;
  }

}
