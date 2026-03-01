((Drupal) => {
  Drupal.behaviors.formDescriptionToggle = {
    attach: (context) => {
      context
        .querySelectorAll('.help-icon__description-toggle')
        .forEach((elem, index) => {
          if (elem.dataset.formDescriptionToggleAttached) {
            return;
          }
          elem.dataset.formDescriptionToggleAttached = true;
          const a11yLabel = 'help-icon-label--' + Math.floor(Math.random() * 10000);
          elem.setAttribute('id', a11yLabel);
          elem.setAttribute('aria-expanded', 'false');
          elem.setAttribute('aria-controls', 'target');
          elem
            .closest('.help-icon__description-container')
            .querySelectorAll(
              '.gin-details__description, .fieldset__description, .form-item__description',
            )
            .forEach((description) => {
              description.setAttribute('aria-labelledby', a11yLabel);
            });

          elem.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();

            // Open details element on toggle.
            if (event.currentTarget.parentElement.tagName === 'SUMMARY'
              && event.currentTarget.parentElement.parentElement.open === false) {
              event.currentTarget.parentElement.parentElement.open = true;
            }

            event.currentTarget.focus(); // firefox button focus issue
            event.currentTarget
              .closest('.help-icon__description-container')
              .querySelectorAll(
                '.gin-details__description, .fieldset__description, .form-item__description',
              )
              .forEach((description, index) => {
                if (index > 1) {
                  return;
                }
                const setStatus = description.classList.contains('visually-hidden');
                event.currentTarget.setAttribute('aria-expanded', setStatus);
                description.classList.toggle('visually-hidden');
                description.setAttribute('aria-hidden', !setStatus);
              });
          });
        });
    }
  };
})(Drupal);
