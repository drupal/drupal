<?php

declare(strict_types=1);

namespace Drupal\mailer_event_subscriber_test\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * A message event test subscriber.
 */
class MessageEventTestSubscriber implements EventSubscriberInterface {

  /**
   * Constructs a new message event test subscriber.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    protected StateInterface $state,
  ) {
  }

  /**
   * Sets a custom from header.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The message event.
   */
  public function setCustomFrom(MessageEvent $event): void {
    $customFrom = $this->state->get('mailer_event_subscriber_test.set_custom_from');
    $message = $event->getMessage();
    if (!empty($customFrom) && $message instanceof Email) {
      $message->from(Address::create($customFrom));
    }
  }

  /**
   * Sets a custom message sender header.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The message event.
   */
  public function setCustomMessageSender(MessageEvent $event): void {
    $customFrom = $this->state->get('mailer_event_subscriber_test.set_custom_message_sender');
    $message = $event->getMessage();
    if (!empty($customFrom) && $message instanceof Email) {
      $message->sender(Address::create($customFrom));
    }
  }

  /**
   * Sets a custom envelope sender.
   *
   * @param \Symfony\Component\Mailer\Event\MessageEvent $event
   *   The message event.
   */
  public function setCustomEnvelopeSender(MessageEvent $event): void {
    $customEnvelopeSender = $this->state->get('mailer_event_subscriber_test.set_custom_envelope_sender');
    if (!empty($customEnvelopeSender)) {
      $envelope = $event->getEnvelope();
      $envelope->setSender(Address::create($customEnvelopeSender));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MessageEvent::class => [
        ['setCustomFrom', 0],
        ['setCustomMessageSender', 0],
        ['setCustomEnvelopeSender', 0],
      ],
    ];
  }

}
