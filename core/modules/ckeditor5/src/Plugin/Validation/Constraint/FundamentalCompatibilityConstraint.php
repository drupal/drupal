<?php

declare(strict_types = 1);

namespace Drupal\ckeditor5\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * The fundamental compatibility constraint.
 *
 * @internal
 */
#[Constraint(
  id: 'CKEditor5FundamentalCompatibility',
  label: new TranslatableMarkup('CKEditor 5 fundamental text format compatibility', [], ['context' => 'Validation'])
)]
class FundamentalCompatibilityConstraint extends SymfonyConstraint {

  public function __construct(
    mixed $options = NULL,
    public $noMarkupFiltersMessage = 'CKEditor 5 only works with HTML-based text formats. The "%filter_label" (%filter_plugin_id) filter implies this text format is not HTML anymore.',
    public $nonAllowedElementsMessage = 'CKEditor 5 needs at least the &lt;p&gt; and &lt;br&gt; tags to be allowed to be able to function. They are not allowed by the "%filter_label" (%filter_plugin_id) filter.',
    public $notSupportedElementsMessage = 'The current CKEditor 5 build requires the following elements and attributes: <br><code>@list</code><br>The following elements are not supported: <br><code>@diff</code>',
    public $missingElementsMessage = 'The current CKEditor 5 build requires the following elements and attributes: <br><code>@list</code><br>The following elements are missing: <br><code>@diff</code>',
    public $nonCreatableTagMessage = 'The %plugin plugin needs another plugin to create <code>@non_creatable_tag</code>, for it to be able to create the following attributes: <code>@attributes_on_tag</code>. Enable a plugin that supports creating this tag. If none exists, you can configure the Source Editing plugin to support it.',
    ?array $groups = NULL,
    mixed $payload = NULL,
  ) {
    parent::__construct($options, $groups, $payload);
  }

}
