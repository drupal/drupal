/**
 * @file
 * Claro's enhancement for autocomplete form element.
 */

// cspell:ignore is-autocompleting

(($, Drupal, once) => {
  Drupal.behaviors.claroAutoCompete = {
    attach(context) {
      once('claroAutoComplete', 'input.form-autocomplete', context).forEach(
        (value) => {
          const $input = $(value);
          const classRemove = ($autoCompleteElem) => {
            $autoCompleteElem.removeClass('is-autocompleting');
            $autoCompleteElem
              .siblings('[data-drupal-selector="autocomplete-message"]')
              .addClass('hidden');
          };

          $input.autocomplete({
            search(event) {
              const result = Drupal.autocomplete.options.search(event);
              if (result) {
                $(event.target).addClass('is-autocompleting');
                $(event.target)
                  .siblings('[data-drupal-selector="autocomplete-message"]')
                  .removeClass('hidden');
              }

              return result;
            },
            response(event) {
              classRemove($(event.target));
            },
          });
        },
      );
    },
  };
})(jQuery, Drupal, once);
