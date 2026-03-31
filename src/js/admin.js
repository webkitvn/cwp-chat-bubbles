/**
 * CWP Chat Bubbles Admin JavaScript
 *
 * Handles dynamic admin interface functionality including:
 * - Tab switching
 * - Add/Edit/Delete items
 * - Drag and drop sorting
 * - QR code uploads
 * - Form validation
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

(function($) {
    'use strict';

    const ACTIVE_TAB_KEY = 'cwpChatBubblesActiveTab';
    let mediaUploader = null;

    function init() {
        initTabs();
        initModal();
        initSortable();
        initFormHandling();
        initQRCodeUpload();
        initMainIconUpload();
        initQRCodePreviews();
        bindEvents();
    }

    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            activateTab($(this).attr('href'));
        });

        if (window.location.hash && $(window.location.hash).hasClass('tab-content')) {
            activateTab(window.location.hash);
            return;
        }

        const storedTab = window.sessionStorage ? window.sessionStorage.getItem(ACTIVE_TAB_KEY) : '';
        if (storedTab && $(storedTab).hasClass('tab-content')) {
            activateTab(storedTab);
        }
    }

    function activateTab(targetTab) {
        if (!targetTab || !$(targetTab).length) {
            return;
        }

        $('.nav-tab').removeClass('nav-tab-active');
        $(`.nav-tab[href="${targetTab}"]`).addClass('nav-tab-active');

        $('.tab-content').hide();
        $(targetTab).show();

        if (window.sessionStorage) {
            window.sessionStorage.setItem(ACTIVE_TAB_KEY, targetTab);
        }
    }

    function initModal() {
        $('#add-new-item, #cwp-header-add-item').on('click', function(e) {
            e.preventDefault();
            activateTab('#chat-items');
            resetModalForm();
            $('#modal-title').text('Add contact method');
            openItemModal('Add contact method');
        });

        $('#cancel-item').on('click', function(e) {
            e.preventDefault();
            resetModalForm();
            closeItemModal();
        });

        $(document).on('click', '#TB_closeWindowButton', function() {
            resetModalForm();
        });

        $('#platform').on('change', function() {
            updateContactFieldForPlatform($(this).val());
        });
    }

    function openItemModal(title) {
        if (typeof window.tb_show === 'function') {
            window.tb_show(title, '#TB_inline?width=760&height=700&inlineId=cwp-item-modal-inline');
            $('#TB_window').addClass('cwp-item-modal');
        }
    }

    function closeItemModal() {
        if (typeof window.tb_remove === 'function') {
            $('#TB_window').removeClass('cwp-item-modal');
            window.tb_remove();
        }
    }

    function initSortable() {
        if ($.fn.sortable) {
            $('#sortable-items').sortable({
                handle: '.cwp-item-drag',
                placeholder: 'cwp-item-placeholder',
                update: function() {
                    const orderedIds = [];
                    $('#sortable-items .cwp-item').each(function() {
                        orderedIds.push($(this).data('item-id'));
                    });

                    saveItemOrder(orderedIds);
                }
            });
        }
    }

    function initFormHandling() {
        $('form[action="options.php"]').on('submit', function() {
            const activeTab = $('.nav-tab.nav-tab-active').attr('href');
            if (activeTab && window.sessionStorage) {
                window.sessionStorage.setItem(ACTIVE_TAB_KEY, activeTab);
            }
        });

        $('#save-item').on('click', function() {
            saveItem();
        });

        $('#cwp-item-form').on('submit', function(e) {
            e.preventDefault();
            saveItem();
        });

        $(document).on('change', '#platform', function() {
            updateContactFieldForPlatform($(this).val());
            validateContactValue();
        });

        $(document).on('input', '#label', function() {
            validateLabel();
        });

        $(document).on('input', '#contact-value', function() {
            validateContactValue();
        });
    }

    function initQRCodeUpload() {
        $('#upload-qr-code').on('click', function(e) {
            e.preventDefault();

            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            mediaUploader = wp.media({
                title: 'Choose a QR code image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                setQRCodePreview(attachment.id, attachment.url);
            });

            mediaUploader.open();
        });

        $('#remove-qr-code').on('click', function(e) {
            e.preventDefault();
            clearQRCodePreview();
        });
    }

    function initMainIconUpload() {
        $('#upload-main-icon').on('click', function(e) {
            e.preventDefault();

            const mainIconUploader = wp.media({
                title: 'Choose a main button icon',
                button: {
                    text: 'Use this icon'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            mainIconUploader.on('select', function() {
                const attachment = mainIconUploader.state().get('selection').first().toJSON();
                setMainIconPreview(attachment.id, attachment.url);
            });

            mainIconUploader.open();
        });

        $('#remove-main-icon').on('click', function(e) {
            e.preventDefault();
            removeMainIconPreview();
        });

        $(document).on('change', 'input[name="cwp_chat_bubbles_options[main_button_color]"]', function() {
            updateMainIconPreviewColor($(this).val());
        });
    }

    function setMainIconPreview(attachmentId, imageUrl) {
        $('#custom-main-icon').val(attachmentId);

        const mainButtonColor = $('input[name="cwp_chat_bubbles_options[main_button_color]"]').val() || '#52BA00';

        const $preview = $('#main-icon-preview');
        $preview.css({
            display: 'flex',
            'justify-content': 'center',
            'align-items': 'center',
            'border-radius': '50%',
            width: '64px',
            height: '64px',
            'background-color': mainButtonColor,
            'margin-top': '10px'
        });
        $preview.html(`<img src="${imageUrl}" alt="Main button icon preview" style="width: 80%; height: auto;">`);

        $('#upload-main-icon').text('Change icon');
        $('#remove-main-icon').show();
    }

    function removeMainIconPreview() {
        $('#custom-main-icon').val(0);

        const $preview = $('#main-icon-preview');
        $preview.empty();
        $preview.css({
            display: '',
            'justify-content': '',
            'align-items': '',
            'border-radius': '',
            width: '',
            height: '',
            'background-color': '',
            'margin-top': '10px'
        });

        $('#upload-main-icon').text('Upload icon');
        $('#remove-main-icon').hide();
    }

    function initQRCodePreviews() {
        $('.cwp-item[data-qr-code-id]').each(function() {
            const $item = $(this);
            const qrCodeId = $item.data('qr-code-id');

            if (qrCodeId && qrCodeId > 0) {
                const $info = $item.find('.cwp-item-info');
                if (!$info.find('.dashicons-format-image').length) {
                    $info.append('<br><span class="dashicons dashicons-format-image" title="QR code available"></span>');
                }
            }
        });
    }

    function bindEvents() {
        $(document).on('click', '.edit-item', function() {
            const itemId = $(this).data('item-id');
            editItem(itemId);
        });

        $(document).on('click', '.delete-item', function() {
            const itemId = $(this).data('item-id');
            if (confirm('Delete this contact method permanently? It will disappear from your chat bubble right away.')) {
                deleteItem(itemId);
            }
        });
    }

    function updateContactFieldForPlatform(platform) {
        if (!platform || !window.platformConfigs || !window.platformConfigs[platform]) {
            const $contactField = $('#contact-value');
            const $contactLabel = $('#contact-label');
            const $contactDescription = $('#contact-description');

            $contactLabel.text('Contact details');
            $contactField.attr('placeholder', 'Add a phone number, username, or link');
            $contactDescription.text('Choose a chat app to see what to enter here.');
            $contactField.attr('pattern', '');
            return;
        }

        const config = window.platformConfigs[platform];
        const $contactField = $('#contact-value');
        const $contactLabel = $('#contact-label');
        const $contactDescription = $('#contact-description');

        $contactField.attr('placeholder', config.placeholder);

        let fieldLabel = 'Contact details';
        let description = '';
        switch (config.contact_field) {
            case 'number':
                fieldLabel = 'Phone number';
                description = 'Add the number people should use to reach you.';
                break;
            case 'username':
                fieldLabel = 'Username';
                description = 'Add the username people should use. Leave out the @ symbol.';
                break;
            case 'id':
                fieldLabel = 'ID or link';
                description = 'Add the ID or link people should use.';
                break;
        }

        $contactLabel.text(fieldLabel);
        $contactDescription.text(description);

        $contactField.attr('pattern', config.pattern ? config.pattern.slice(1, -1) : '');
    }

    function validateContactValue() {
        const platform = $('#platform').val();
        const contactValue = $('#contact-value').val().trim();

        if (!platform || !contactValue) {
            return true;
        }

        const config = window.platformConfigs[platform];
        if (!config || !config.pattern) {
            return true;
        }

        const regex = new RegExp(config.pattern.slice(1, -1));
        const isValid = regex.test(contactValue);

        if (!isValid) {
            let errorMessage = 'That does not look right for this chat app.';

            switch (platform) {
                case 'phone':
                    errorMessage = 'Enter a valid phone number, like +1234567890 or 0123456789.';
                    break;
                case 'zalo':
                    errorMessage = 'Enter a valid Zalo phone number with 9 to 11 digits, like 0123456789.';
                    break;
                case 'zalo_oa':
                    errorMessage = 'Enter a valid Zalo OA ID or the full OA link.';
                    break;
                case 'whatsapp':
                    errorMessage = 'Enter a valid WhatsApp number with the country code, like 1234567890.';
                    break;
                case 'viber':
                    errorMessage = 'Enter a valid Viber phone number.';
                    break;
                case 'telegram':
                    errorMessage = 'Enter a valid Telegram username that starts with a letter.';
                    break;
                case 'messenger':
                    errorMessage = 'Enter a valid Facebook Messenger username.';
                    break;
                case 'line':
                    errorMessage = 'Enter a valid Line ID.';
                    break;
                case 'kakaotalk':
                    errorMessage = 'Enter a valid KakaoTalk channel ID.';
                    break;
            }

            showFieldError('contact-value', errorMessage);
            return false;
        }

        removeFieldError('contact-value');
        return true;
    }

    function validateLabel() {
        const label = $('#label').val().trim();

        if (label.length < 2) {
            showFieldError('label', 'Enter a name with at least 2 characters so people know what this option is for.');
            return false;
        }

        if (label.length > 50) {
            showFieldError('label', 'Keep the name under 50 characters.');
            return false;
        }

        removeFieldError('label');
        return true;
    }

    function showFieldError(fieldId, message) {
        const $field = $('#' + fieldId);
        removeFieldError(fieldId);

        const errorId = `${fieldId}-error`;
        const existingDescription = $field.attr('aria-describedby');
        if (existingDescription) {
            $field.attr('data-original-described-by', existingDescription);
            $field.attr('aria-describedby', `${existingDescription} ${errorId}`);
        } else {
            $field.attr('aria-describedby', errorId);
        }

        const $error = $('<div></div>', {
            id: errorId,
            class: 'notice notice-error inline cwp-inline-notice',
            role: 'alert',
            'aria-live': 'assertive',
        }).append($('<p></p>').text(message));

        $field.after($error);
        $field.addClass('error').attr('aria-invalid', 'true');
    }

    function removeFieldError(fieldId) {
        const $field = $('#' + fieldId);
        const originalDescription = $field.attr('data-original-described-by');

        $field.removeClass('error').removeAttr('aria-invalid');

        if (originalDescription) {
            $field.attr('aria-describedby', originalDescription);
            $field.removeAttr('data-original-described-by');
        } else {
            $field.removeAttr('aria-describedby');
        }

        $('#' + fieldId + '-error').remove();
    }

    function editItem(itemId) {
        const $item = $(`.cwp-item[data-item-id="${itemId}"]`);

        if (!$item.length) {
            return;
        }

        $('#modal-title').text('Edit contact method');
        $('#item-id').val(itemId);
        $('#platform').val($item.data('platform'));
        $('#label').val($item.data('label'));
        $('#contact-value').val($item.data('contact-value'));
        $('#interaction-mode').val($item.data('interaction-mode') || 'auto');
        $('#prefill-message').val($item.data('prefill-message') || '');
        $('#enabled').prop('checked', Number($item.data('enabled')) === 1);

        updateContactFieldForPlatform($item.data('platform'));

        const qrCodeId = Number($item.data('qr-code-id'));
        if (qrCodeId > 0) {
            sendAjaxRequest('cwp_chat_bubbles_get_attachment_url', {
                attachment_id: qrCodeId
            }).then((response) => {
                if (response.success && response.data.url) {
                    setQRCodePreview(qrCodeId, response.data.url);
                } else {
                    clearQRCodePreview();
                }
            });
        } else {
            clearQRCodePreview();
        }

        openItemModal('Edit contact method');
    }

    function deleteItem(itemId) {
        sendAjaxRequest('cwp_chat_bubbles_delete_item', {
            item_id: itemId
        }).then((response) => {
            if (response.success) {
                refreshItemsList(response.data.items_html);
            } else {
                alert(response.data || 'We could not delete this contact method. Try again.');
            }
        });
    }

    function saveItem() {
        if (!validateForm()) {
            return;
        }

        const payload = {
            item_id: $('#item-id').val(),
            platform: $('#platform').val(),
            label: $('#label').val(),
            contact_value: $('#contact-value').val(),
            qr_code_id: $('#qr-code-id').val(),
            interaction_mode: $('#interaction-mode').val(),
            prefill_message: $('#prefill-message').val(),
            enabled: $('#enabled').is(':checked') ? 1 : 0
        };

        const $saveButton = $('#save-item');
        $saveButton.prop('disabled', true).text('Saving...');

        sendAjaxRequest('cwp_chat_bubbles_save_item', payload)
            .then((response) => {
                if (response.success) {
                    refreshItemsList(response.data.items_html);
                    resetModalForm();
                    closeItemModal();
                } else {
                    alert(response.data || 'We could not save this contact method. Check your details and try again.');
                }
            })
            .finally(() => {
                $saveButton.prop('disabled', false).text('Save contact method');
            });
    }

    function validateForm() {
        let isValid = true;

        if (!$('#platform').val()) {
            showFieldError('platform', 'Choose a chat app.');
            isValid = false;
        } else {
            removeFieldError('platform');
        }

        if (!validateLabel()) {
            isValid = false;
        }

        if (!validateContactValue()) {
            isValid = false;
        }

        return isValid;
    }

    function resetModalForm() {
        $('#cwp-item-form')[0].reset();
        $('#item-id').val('');
        clearQRCodePreview();
        updateContactFieldForPlatform('');
        $('.cwp-inline-notice').remove();
        $('#cwp-item-form .error').removeClass('error');
        $('#cwp-item-form [aria-invalid="true"]').removeAttr('aria-invalid');
        $('#cwp-item-form [data-original-described-by]').each(function() {
            const $field = $(this);
            $field.attr('aria-describedby', $field.attr('data-original-described-by'));
            $field.removeAttr('data-original-described-by');
        });
    }

    function refreshItemsList(itemsHtml) {
        $('#cwp-items-container').html(itemsHtml);
        initSortable();
        initQRCodePreviews();
    }

    function saveItemOrder(orderedIds) {
        sendAjaxRequest('cwp_chat_bubbles_reorder_items', {
            ordered_ids: orderedIds
        }).then((response) => {
            if (!response.success) {
                alert(response.data || 'We could not save the new order. Try again.');
            }
        });
    }

    function setQRCodePreview(attachmentId, imageUrl) {
        $('#qr-code-id').val(attachmentId);
        $('#qr-preview').html(`<img src="${imageUrl}" alt="QR code preview" class="cwp-qr-preview-image">`);
        $('#upload-qr-code').text('Change QR code');
        $('#remove-qr-code').show();
    }

    function clearQRCodePreview() {
        $('#qr-code-id').val(0);
        $('#qr-preview').empty();
        $('#upload-qr-code').text('Upload QR code');
        $('#remove-qr-code').hide();
    }

    function updateMainIconPreviewColor(color) {
        $('#main-icon-preview').css('--cwp-preview-color', color);
    }

    function sendAjaxRequest(action, data = {}) {
        return $.ajax({
            url: wpAjax.ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action,
                nonce: wpAjax.nonce,
                ...data
            }
        }).catch((xhr) => {
            const message = xhr?.responseJSON?.data || xhr?.responseText || 'We could not complete that request. Try again.';
            return {
                success: false,
                data: message
            };
        });
    }

    $(init);
})(jQuery);
