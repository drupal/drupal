/**
 * @file
 * Claro's enhancement for autocomplete form element.
 */

(($, Drupal) => {
  Drupal.behaviors.claroAutoCompete = {
    attach(context) {
      $(context)
        .find('input.form-autocomplete')
        .once('claroAutoComplete')
        .each((index, value) => {
          const $input = $(value);
          const timeout = 400;
          let classRemoveTimeout;
          const classRemove = ($autoCompleteElem) => {
            $autoCompleteElem.removeClass('is-autocompleting');
            $autoCompleteElem
              .siblings('[data-drupal-selector="autocomplete-message"]')
              .addClass('hidden');
          };

          $input.on(
            'input autocompletesearch autocompleteresponses',
            (event) => {
              if (event && event.type && event.type === 'autocompletesearch') {
                $(event.target).addClass('is-autocompleting');
                $(event.target)
                  .siblings('[data-drupal-selector="autocomplete-message"]')
                  .removeClass('hidden');
              }
              clearTimeout(classRemoveTimeout);
              classRemoveTimeout = setTimeout(
                classRemove,
                timeout,
                $(event.target),
              );
            },
          );
        });
    },
  };
})(jQuery, Drupal);
