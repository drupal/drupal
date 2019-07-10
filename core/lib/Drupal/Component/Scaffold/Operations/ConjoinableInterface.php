<?php

namespace Drupal\Component\Scaffold\Operations;

/**
 * Marker interface indicating that operation is conjoinable.
 *
 * A conjoinable operation is one that runs in addition to any previous
 * operation defined at the same destination path. Operations that are
 * not conjoinable simply replace anything at the same destination path.
 */
interface ConjoinableInterface {
}
