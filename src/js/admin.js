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

            const targetTab = $(this).attr('href');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').hide();
            $(targetTab).show();
        });
    }

    function initModal() {
        $('#add-new-item').on('click', function() {
            resetModalForm();
            $('#modal-title').text('Add New Item');
            $('#cwp-item-modal').addClass('active');
        });

        $('.cwp-modal-close, #cancel-item').on('click', function() {
            $('#cwp-item-modal').removeClass('active');
        });

        $('#cwp-item-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });

        $('#platform').on('change', function() {
            updateContactFieldForPlatform($(this).val());
        });
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
                title: 'Select QR Code Image',
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
                title: 'Select Custom Main Icon',
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
        $preview.html(`<img src="${imageUrl}" alt="Custom main icon preview" style="width: 80%; height: auto;">`);

        $('#upload-main-icon').text('Change Custom Icon');
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

        $('#upload-main-icon').text('Upload Custom Icon');
        $('#remove-main-icon').hide();
    }

    function initQRCodePreviews() {
        $('.cwp-item[data-qr-code-id]').each(function() {
            const $item = $(this);
            const qrCodeId = $item.data('qr-code-id');

            if (qrCodeId && qrCodeId > 0) {
                const $info = $item.find('.cwp-item-info');
                if (!$info.find('.dashicons-format-image').length) {
                    $info.append('<br><span class="dashicons dashicons-format-image" title="Has QR Code" style="color: #0073aa;"></span>');
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
            if (confirm('Are you sure you want to delete this item?')) {
                deleteItem(itemId);
            }
        });
    }

    function updateContactFieldForPlatform(platform) {
        if (!platform || !window.platformConfigs || !window.platformConfigs[platform]) {
            const $contactField = $('#contact-value');
            const $contactLabel = $('#contact-label');
            const $contactDescription = $('#contact-description');

            $contactLabel.text('Contact Value');
            $contactField.attr('placeholder', 'Enter contact information');
            $contactDescription.text('Select a platform to see specific instructions.');
            $contactField.attr('pattern', '');
            return;
        }

        const config = window.platformConfigs[platform];
        const $contactField = $('#contact-value');
        const $contactLabel = $('#contact-label');
        const $contactDescription = $('#contact-description');

        $contactLabel.text(config.label + ' ' + config.contact_field.charAt(0).toUpperCase() + config.contact_field.slice(1));
        $contactField.attr('placeholder', config.placeholder);

        let description = '';
        switch (config.contact_field) {
            case 'number':
                description = 'Enter the phone number or ID for this platform.';
                break;
            case 'username':
                description = 'Enter the username (without @ symbol).';
                break;
            case 'id':
                description = 'Enter the unique ID for this platform.';
                break;
        }
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
            let errorMessage = 'Invalid format for this platform.';

            switch (platform) {
                case 'phone':
                    errorMessage = 'Please enter a valid phone number (e.g., +1234567890 or 0123456789)';
                    break;
                case 'zalo':
                    errorMessage = 'Please enter a valid Zalo phone number (9-11 digits, e.g., 0123456789)';
                    break;
                case 'zalo_oa':
                    errorMessage = 'Please enter a valid Zalo OA ID or full OA URL.';
                    break;
                case 'whatsapp':
                    errorMessage = 'Please enter a valid WhatsApp number with country code (e.g., 1234567890)';
                    break;
                case 'viber':
                    errorMessage = 'Please enter a valid Viber phone number.';
                    break;
                case 'telegram':
                    errorMessage = 'Please enter a valid Telegram username starting with a letter.';
                    break;
                case 'messenger':
                    errorMessage = 'Please enter a valid Facebook Messenger username.';
                    break;
                case 'line':
                    errorMessage = 'Please enter a valid Line ID.';
                    break;
                case 'kakaotalk':
                    errorMessage = 'Please enter a valid KakaoTalk channel ID.';
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
            showFieldError('label', 'Label must be at least 2 characters long');
            return false;
        }

        if (label.length > 50) {
            showFieldError('label', 'Label must be 50 characters or fewer');
            return false;
        }

        removeFieldError('label');
        return true;
    }

    function showFieldError(fieldId, message) {
        const $field = $('#' + fieldId);
        removeFieldError(fieldId);

        const $error = $('<div class="error-message"></div>').text(message).css({
            color: '#d63638',
            'margin-top': '4px'
        });

        $field.after($error);
        $field.addClass('error');
    }

    function removeFieldError(fieldId) {
        const $field = $('#' + fieldId);
        $field.removeClass('error');
        $field.siblings('.error-message').remove();
    }

    function editItem(itemId) {
        const $item = $(`.cwp-item[data-item-id="${itemId}"]`);

        if (!$item.length) {
            return;
        }

        $('#modal-title').text('Edit Item');
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

        $('#cwp-item-modal').addClass('active');
    }

    function deleteItem(itemId) {
        sendAjaxRequest('cwp_chat_bubbles_delete_item', {
            item_id: itemId
        }).then((response) => {
            if (response.success) {
                refreshItemsList(response.data.items_html);
            } else {
                alert(response.data || 'Failed to delete item.');
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
                    $('#cwp-item-modal').removeClass('active');
                    resetModalForm();
                } else {
                    alert(response.data || 'Failed to save item.');
                }
            })
            .finally(() => {
                $saveButton.prop('disabled', false).text('Save Item');
            });
    }

    function validateForm() {
        let isValid = true;

        if (!$('#platform').val()) {
            showFieldError('platform', 'Please select a platform');
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
        $('.error-message').remove();
        $('#cwp-item-form .error').removeClass('error');
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
                alert(response.data || 'Failed to save item order.');
            }
        });
    }

    function setQRCodePreview(attachmentId, imageUrl) {
        $('#qr-code-id').val(attachmentId);
        $('#qr-preview').html(`<img src="${imageUrl}" alt="QR code preview" style="max-width: 150px; height: auto; border: 1px solid #ddd;">`);
        $('#upload-qr-code').text('Change QR Code');
        $('#remove-qr-code').show();
    }

    function clearQRCodePreview() {
        $('#qr-code-id').val(0);
        $('#qr-preview').empty();
        $('#upload-qr-code').text('Upload QR Code');
        $('#remove-qr-code').hide();
    }

    function updateMainIconPreviewColor(color) {
        $('#main-icon-preview').css('background-color', color);
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
            const message = xhr?.responseJSON?.data || xhr?.responseText || 'Request failed.';
            return {
                success: false,
                data: message
            };
        });
    }

    $(init);
})(jQuery);
