<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate\Unit\TestMigrateExecutable.
 */

namespace Drupal\Tests\migrate\Unit;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests MigrateExecutable.
 */
class TestMigrateExecutable extends MigrateExecutable {

  /**
   * Sets the string translation service.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   */
  public function setStringTranslation(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;
  }

  /**
   * Allows access to set protected source property.
   *
   * @param \Drupal\migrate\Plugin\MigrateSourceInterface $source
   *   The value to set.
   */
  public function setSource($source) {
    $this->source = $source;
  }

  /**
   * Allows access to protected sourceIdValues property.
   *
   * @param array $source_id_values
   *   The value to set.
   */
  public function setSourceIdValues($source_id_values) {
    $this->sourceIdValues = $source_id_values;
  }

  /**
   * {@inheritdoc}
   */
  public function handleException(\Exception $exception, $save = TRUE) {
    $message = $exception->getMessage();
    if ($save) {
      $this->saveMessage($message);
    }
    $this->message->display($message);
  }

}
