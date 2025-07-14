document.addEventListener("DOMContentLoaded", function() {

    //Disable element function.
    function disableElement(element) {
        element.classList.add('disabled', 'loading');
        element.style.pointerEvents = 'none';
    }

    function enableElement(element) {
        element.classList.remove('disabled', 'loading');
        element.style.pointerEvents = 'auto';
    }

  document.addEventListener("click", function (event) {
        const buttonAddNew = event.target.closest('#js_backwpup_add_new_backup');
        if (!buttonAddNew) {
          return;
        }
        //To avoid multiple submission when a user try to create new backup.
        if (buttonAddNew.classList.contains('disabled')) {
            return;
        }

        disableElement(buttonAddNew);
        event.target.classList.add('bg-grey-200');
        requestWPApi(backwpupApi.addjob, {type: 'mixed'}, function(response) {
                if (response.success === true) {
                    document.getElementById("backwpup-backup-now").disabled = false;
                    loadBackupsListingAndPagination( getUrlParameter( 'page_num', 1 ) );
                    backwpupDisplaySettingsToast('success', response.message);

                    requestWPApi(
                        backwpupApi.getjobslist,
                        {},
                        function (response) {
                            const nextScheduledElement = document.querySelector('#backwup-next-scheduled-backups'),
                                dynamicContentElement = document.querySelector('#backwpup_dynamic_response_content');

                            nextScheduledElement.innerHTML  = response;
                            const dynamicContent = dynamicContentElement.innerHTML;
                            nextScheduledElement.insertAdjacentHTML('beforeend', dynamicContent);
                            enableElement(buttonAddNew);
                            buttonAddNew.classList.remove('bg-grey-200');
                        },
                        "GET",
                    );

                }
            },
            'POST',
            function (request, error) {
                backwpupDisplaySettingsToast('error', request.responseText);
            });
    });

    if (!window._jobButtonHandlerAttached) {
        //Dispatch after click event
        document.addEventListener("click", function (event) {
            const buttonWithJobId = event.target.closest('[data-job-id]');

            if (buttonWithJobId
                && !event.target.classList.contains('js-backwpup-mixed-data-settings')
            ) {
                window.currentPrimaryJobId = buttonWithJobId.getAttribute('data-job-id');
            }

            //Edit job process flow and logic
            //Edit job data type options
            let $data_types = event.target.closest('.js-backwpup-mixed-data-settings');
            if ($data_types) {
                event.preventDefault();
                event.stopImmediatePropagation();

                const checkbox = $data_types.querySelector('.js-backwpup-mixed-data-settings-checkbox'),
                    data_types = document.querySelectorAll('.js-backwpup-mixed-data-settings .js-backwpup-mixed-data-settings-checkbox'),
                    checked_count = Array.from(data_types).filter(cb => cb.checked).length,
                    configure_buttons = document.querySelectorAll('button[data-mixed-data-content]');

                if (checkbox.checked && checked_count === 1) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change'));

                const active_data_type = Array.from(data_types).filter(cb => cb.checked);
                const checked_data_type = active_data_type.map(cb => cb.value);

                let typeValue;

                if (checked_data_type.length === 1) {
                    typeValue = checked_data_type[0];
                } else {
                    typeValue = 'mixed';
                }

                $data = {
                    'type': typeValue
                };

                const endpoint = backwpupApi.updates_backup_type.replace('%d', currentPrimaryJobId);

                requestWPApi(endpoint, $data, function(response) {
                    if (response.status === 200) {
                        requestWPApi(
                            backwpupApi.getjobslist,
                            {},
                            function (response) {
                                const nextScheduledElement = document.querySelector('#backwup-next-scheduled-backups'),
                                    dynamicContentElement = document.querySelector('#backwpup_dynamic_response_content');

                                nextScheduledElement.innerHTML  = response;
                                const dynamicContent = dynamicContentElement.innerHTML;
                                nextScheduledElement.insertAdjacentHTML('beforeend', dynamicContent);
                            },
                            "GET",
                        );
                    }
                }, 'POST');
            }

            //Configure files related settings.
            let $main_setting_section = document.querySelector('.js-data-main-setting'),
                $file_setting_section = document.querySelector('.js-file-settings-section'),
                $database_setting_section = document.querySelector('.js-database-settings-section');

            const $sections = {
                files: $file_setting_section,
                database: $database_setting_section
            };

            const $toggle_type = event.target.closest('[data-toggle-setting-panel]')?.dataset.toggleSettingPanel;

            if ($toggle_type && $sections[$toggle_type]) {
                event.preventDefault();
                $main_setting_section.classList.toggle('hidden')
                $sections[$toggle_type].classList.toggle('hidden')
            }
        })

        window._jobButtonHandlerAttached = true;
    }
})