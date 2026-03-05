(function (Drupal) {
  Drupal.theme.textEditSummaryButton = function (title) {
    // Add an extra class for testing.
    return `
      <span class="field-edit-link text-test-edit-link">
        (<button type="button" class="link link-edit-summary">
          <span class="visually-hidden">Custom override: </span>${title}
        </button>)
      </span>
    `;
  };
})(Drupal);
