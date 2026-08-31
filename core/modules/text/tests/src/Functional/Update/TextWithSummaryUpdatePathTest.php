<?php

declare(strict_types=1);

namespace Drupal\Tests\text\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the upgrade path that installs text_with_summary and updates.
 */
#[Group('Update')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class TextWithSummaryUpdatePathTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-11.3.0.bare.standard.php.gz',
      __DIR__ . '/../../../fixtures/update/add-text-with-summary-field.php',
    ];
  }

  /**
   * Tests text_with_summary install and dependency updates.
   */
  public function testRunUpdates(): void {
    $this->assertFalse(\Drupal::moduleHandler()->moduleExists('text_with_summary'));

    $storage = \Drupal::config('field.storage.node.body');
    $this->assertNotEmpty($storage->getRawData());
    $this->assertContains('text', $storage->get('dependencies.module'));
    $this->assertNotContains('text_with_summary', $storage->get('dependencies.module'));

    $field = \Drupal::config('field.field.node.article.body');
    $this->assertNotEmpty($field->getRawData());
    $this->assertContains('text', $field->get('dependencies.module'));
    $this->assertNotContains('text_with_summary', $field->get('dependencies.module'));

    $form_display = \Drupal::config('core.entity_form_display.node.article.default');
    $this->assertNotEmpty($form_display->getRawData());
    $this->assertContains('text', $form_display->get('dependencies.module'));
    $this->assertNotContains('text_with_summary', $form_display->get('dependencies.module'));

    $view_display = \Drupal::config('core.entity_view_display.node.article.teaser');
    $this->assertNotEmpty($view_display->getRawData());
    $this->assertContains('text', $view_display->get('dependencies.module'));
    $this->assertNotContains('text_with_summary', $view_display->get('dependencies.module'));

    $this->runUpdates();

    $this->assertTrue(\Drupal::moduleHandler()->moduleExists('text_with_summary'));

    $storage = \Drupal::config('field.storage.node.body');
    $this->assertContains('text_with_summary', $storage->get('dependencies.module'));

    $field = \Drupal::config('field.field.node.article.body');
    $this->assertContains('text_with_summary', $field->get('dependencies.module'));

    $form_display = \Drupal::config('core.entity_form_display.node.article.default');
    $this->assertContains('text_with_summary', $form_display->get('dependencies.module'));

    $view_display = \Drupal::config('core.entity_view_display.node.article.teaser');
    $this->assertContains('text_with_summary', $view_display->get('dependencies.module'));
  }

}
