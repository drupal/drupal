((Drupal, once) => {
  Drupal.behaviors.ginMediaLibrary = {
    attach: function attach() {
      Drupal.ginMediaLibrary.init();
    },
  };

  Drupal.ginMediaLibrary = {
    init: function () {
      once('media-library-select-all', '.js-media-library-view[data-view-display-id="page"]').forEach(el => {
        if (el.querySelectorAll('.js-media-library-item').length) {
          const header = document.querySelector('.media-library-views-form');
          const selectAll = document.createElement('label');
          selectAll.className = 'media-library-select-all';
          selectAll.innerHTML = Drupal.theme('checkbox') + Drupal.t('Select all media');
          selectAll.children[0].addEventListener('click', e => {
            const currentTarget = e.currentTarget;
            const checkboxes = currentTarget
              .closest('.js-media-library-view')
              .querySelectorAll('.js-media-library-item .form-boolean');

            checkboxes.forEach(checkbox => {
              const stateChanged = checkbox.checked !== currentTarget.checked;

              if (stateChanged) {
                checkbox.checked = currentTarget.checked;
                checkbox.dispatchEvent(new Event('change'));
              }
            });

            const announcement = currentTarget.checked ? Drupal.t('All @count items selected', {
              '@count': checkboxes.length
            }) : Drupal.t('Zero items selected');
            Drupal.announce(announcement);

            this.bulkOperations();
          });
          header.prepend(selectAll);
        }

        this.itemSelect();
      });
    },

    itemSelect: () => {
      document.querySelectorAll('.media-library-view .js-click-to-select-trigger, .media-library-view .media-library-item .form-checkbox')
        .forEach(trigger => {
          trigger.addEventListener('click', () => {
            const selectAll = document.querySelector('.media-library-select-all .form-boolean');
            const checkboxes = document.querySelectorAll('.media-library-view .media-library-item .form-boolean');
            const checkboxesChecked = document.querySelectorAll('.media-library-view .media-library-item .form-boolean:checked');

            if (selectAll && selectAll.checked === true && checkboxes.length !== checkboxesChecked.length) {
              selectAll.checked = false;
              selectAll.dispatchEvent(new Event('change'));

            } else if (checkboxes.length === Array.from(checkboxes).filter(el => el.checked === true).length) {
              selectAll.checked = true;
              selectAll.dispatchEvent(new Event('change'));
            }

            Drupal.ginMediaLibrary.bulkOperations();
          });
        });
    },

    bulkOperations: () => {
      const bulkOperations = document.querySelector('.media-library-view [data-drupal-selector*="edit-header"]');
      const bulkOperationsStickyBar = document.querySelector('.media-library-views-form__bulk_form');

      if (bulkOperations && document.querySelectorAll('.media-library-view .form-checkbox:checked').length > 0) {
        bulkOperations.classList.add('is-sticky');
        bulkOperationsStickyBar?.setAttribute('data-drupal-sticky-vbo', true);
      } else {
        bulkOperations.classList.remove('is-sticky');
        bulkOperationsStickyBar?.setAttribute('data-drupal-sticky-vbo', false);
      }
    },

  };
})(Drupal, once);
