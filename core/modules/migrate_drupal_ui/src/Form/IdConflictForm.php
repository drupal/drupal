<?php

namespace Drupal\migrate_drupal_ui\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Audit\IdAuditor;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * Migrate Upgrade Id Conflict form.
 *
 * @internal
 */
class IdConflictForm extends MigrateUpgradeFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'migrate_drupal_ui_idconflict_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get all the data needed for this form.
    $migrations = $this->store->get('migrations');

    // If data is missing or this is the wrong step, start over.
    if (!$migrations || ($this->store->get('step') != 'idconflict')) {
      return $this->restartUpgradeForm();
    }

    $migration_ids = array_keys($migrations);
    // Check if there are conflicts. If none, just skip this form!
    $migrations = $this->migrationPluginManager->createInstances($migration_ids);

    $translated_content_conflicts = $content_conflicts = [];

    $results = (new IdAuditor())->auditMultiple($migrations);

    /** @var \Drupal\migrate\Audit\AuditResult $result */
    foreach ($results as $result) {
      $destination = $result->getMigration()->getDestinationPlugin();
      if ($destination instanceof EntityContentBase && $destination->isTranslationDestination()) {
        // Translations are not yet supported by the audit system. For now, we
        // only warn the user to be cautious when migrating translated content.
        // I18n support should be added in https://www.drupal.org/node/2905759.
        $translated_content_conflicts[] = $result;
      }
      elseif (!$result->passed()) {
        $content_conflicts[] = $result;
      }
    }

    if ($content_conflicts || $translated_content_conflicts) {
      $this->messenger()->addWarning($this->t('WARNING: Content may be overwritten on your new site.'));

      $form = parent::buildForm($form, $form_state);
      $form['#title'] = $this->t('Upgrade analysis report');

      if ($content_conflicts) {
        $form = $this->conflictsForm($form, $content_conflicts);
      }
      if ($translated_content_conflicts) {
        $form = $this->i18nWarningForm($form, $translated_content_conflicts);
      }
      return $form;
    }
    else {
      $this->store->set('step', 'review');
      return $this->redirect('migrate_drupal_ui.upgrade_review');
    }
  }

  /**
   * Build the markup for conflict warnings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return array
   *   The form structure.
   */
  protected function conflictsForm(array &$form, array $conflicts) {
    $form['conflicts'] = [
      '#title' => $this->t('There is conflicting content of these types:'),
      '#theme' => 'item_list',
      '#items' => $this->formatConflicts($conflicts),
    ];

    $form['warning'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('It looks like you have content on your new site which <strong>may be overwritten</strong> if you continue to run this upgrade. The upgrade should be performed on a clean Drupal 8 installation. For more information see the <a target="_blank" href=":id-conflicts-handbook">upgrade handbook</a>.', [':id-conflicts-handbook' => 'https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#id_conflicts']) . '</p>',
    ];

    return $form;
  }

  /**
   * Formats a set of failing audit results as strings.
   *
   * Each string is the label of the destination plugin of the migration that
   * failed the audit, keyed by the destination plugin ID in order to prevent
   * duplication.
   *
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return string[]
   *   The formatted audit results.
   */
  protected function formatConflicts(array $conflicts) {
    $items = [];

    foreach ($conflicts as $conflict) {
      $definition = $conflict->getMigration()->getDestinationPlugin()->getPluginDefinition();
      $id = $definition['id'];
      $items[$id] = $definition['label'];
    }
    sort($items, SORT_STRING);

    return $items;
  }

  /**
   * Build the markup for i18n warnings.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\migrate\Audit\AuditResult[] $conflicts
   *   The failing audit results.
   *
   * @return array
   *   The form structure.
   */
  protected function i18nWarningForm(array &$form, array $conflicts) {
    $form['i18n'] = [
      '#title' => $this->t('There is translated content of these types:'),
      '#theme' => 'item_list',
      '#items' => $this->formatConflicts($conflicts),
    ];

    $form['i18n_warning'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('It looks like you are migrating translated content from your old site. Possible ID conflicts for translations are not automatically detected in the current version of Drupal. Refer to the <a target="_blank" href=":id-conflicts-handbook">upgrade handbook</a> for instructions on how to avoid ID conflicts with translated content.', [':id-conflicts-handbook' => 'https://www.drupal.org/docs/8/upgrade/known-issues-when-upgrading-from-drupal-6-or-7-to-drupal-8#id_conflicts']) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->store->set('step', 'review');
    $form_state->setRedirect('migrate_drupal_ui.upgrade_review');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('I acknowledge I may lose data. Continue anyway.');
  }

}
