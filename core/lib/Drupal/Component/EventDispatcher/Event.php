<?php

namespace Drupal\Component\EventDispatcher;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

/**
 * Provides a forward-compatibility layer for the Symfony 5 event class.
 *
 * Symfony 5 relies on the Symfony\Contracts\EventDispatcher\Event class.
 * In order to prepare for updates, code that wishes to extend Symfony's Event
 * class should extend this intermediary class, which will handle switching
 * from Symfony\Component to Symfony\Contracts without a further change.
 */
class Event extends SymfonyEvent {}
