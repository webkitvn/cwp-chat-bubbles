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

    // Global variables
    let currentEditingItem = null;
    let mediaUploader = null;
    const i18n = (typeof wpAjax !== 'undefined' && wpAjax.i18n) ? wpAjax.i18n : {};

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(i18n, key) ? i18n[key] : fallback;
    }

    /**
     * Initialize admin functionality
     */
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

    /**
     * Initialize tab functionality
     */
    function initTabs() {
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            const targetTab = $(this).attr('href');
            
            // Update tab states
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            // Show/hide content
            $('.tab-content').hide();
            $(targetTab).show();
        });
    }

    /**
     * Initialize modal functionality
     */
    function initModal() {
        // Open modal for adding new item
        $('#add-new-item').on('click', function() {
            currentEditingItem = null;
            resetModalForm();
            $('#modal-title').text(t('addContactButton', 'Add contact button'));
            $('#cwp-item-modal').addClass('active');
        });

        // Close modal
        $('.cwp-modal-close, #cancel-item').on('click', function() {
            $('#cwp-item-modal').removeClass('active');
        });

        // Close modal when clicking outside
        $('#cwp-item-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).removeClass('active');
            }
        });

        // Platform selection change
        $('#platform').on('change', function() {
            updateContactFieldForPlatform($(this).val());
        });
    }

    /**
     * Initialize sortable functionality
     */
    function initSortable() {
        if ($.fn.sortable) {
            $('#sortable-items').sortable({
                handle: '.cwp-item-drag',
                placeholder: 'cwp-item-placeholder',
                update: function(event, ui) {
                    const orderedIds = [];
                    $('#sortable-items .cwp-item').each(function() {
                        orderedIds.push($(this).data('item-id'));
                    });
                    
                    saveItemOrder(orderedIds);
                }
            });
        }
    }

    /**
     * Initialize form handling with real-time validation
     */
    function initFormHandling() {
        $('#save-item').on('click', function() {
            saveItem();
        });

        // Form validation
        $('#cwp-item-form').on('submit', function(e) {
            e.preventDefault();
            saveItem();
        });

        // Real-time validation for platform selection
        $(document).on('change', '#platform', function() {
            const platform = $(this).val();
            
            // Clear previous errors when platform changes
            clearFieldError('#platform');
            clearFieldError('#contact-value');
            
            updateContactFieldForPlatform(platform);
            
            // Validate platform selection
            validateField('#platform');
            
            // Revalidate contact value if it has content
            const contactValue = $('#contact-value').val().trim();
            if (contactValue) {
                validateContactValue();
            }
        });

        // Real-time validation for label field
        $(document).on('input blur', '#label', function() {
            validateField('#label');
        });

        // Real-time validation for contact value
        $(document).on('input blur', '#contact-value', function() {
            validateField('#contact-value');
        });
    }

    /**
     * Validate individual field with real-time feedback
     */
    function validateField(fieldSelector) {
        const $field = $(fieldSelector);
        const value = $field.val().trim();
        
        // Clear previous error for this field
        clearFieldError(fieldSelector);
        
        switch (fieldSelector) {
            case '#platform':
                if (!value) {
                    showFieldError(fieldSelector, t('selectPlatform', 'Please choose a platform.'));
                    return false;
                }
                break;
                
            case '#label':
                if (!value) {
                    showFieldError(fieldSelector, t('labelRequired', 'Enter a button label.'));
                    return false;
                } else if (value.length < 2) {
                    showFieldError(fieldSelector, t('labelMin', 'Use at least 2 characters for the button label.'));
                    return false;
                } else if (value.length > 255) {
                    showFieldError(fieldSelector, t('labelMax', 'Use 255 characters or fewer for the button label.'));
                    return false;
                }
                break;
                
            case '#contact-value':
                if (!value) {
                    showFieldError(fieldSelector, t('contactRequired', 'Enter contact details.'));
                    return false;
                } else {
                    return validateContactValue();
                }
                break;
        }
        
        return true;
    }

    /**
     * Clear error for a specific field
     */
    function clearFieldError(fieldSelector) {
        const $field = $(fieldSelector);
        $field.removeClass('error');
        $field.siblings('.field-error').remove();
        $field.next('.field-error').remove();
    }

    /**
     * Initialize QR code upload functionality
     */
    function initQRCodeUpload() {
        $('#upload-qr-code').on('click', function(e) {
            e.preventDefault();
            
            // Hide item modal so media uploader appears on top
            $('#cwp-item-modal').removeClass('active');
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: t('uploadQrCode', 'Upload QR code'),
                button: {
                    text: t('uploadQrCode', 'Upload QR code')
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            mediaUploader.on('select', function() {
                const attachment = mediaUploader.state().get('selection').first().toJSON();
                setQRCodePreview(attachment.id, attachment.url);
                // Show item modal again after selection
                $('#cwp-item-modal').addClass('active');
            });
            
            mediaUploader.on('close', function() {
                // Show item modal again when media uploader is closed
                $('#cwp-item-modal').addClass('active');
            });
            
            mediaUploader.open();
        });

        $('#remove-qr-code').on('click', function() {
            removeQRCodePreview();
        });
    }

    /**
     * Initialize main icon upload functionality
     */
    function initMainIconUpload() {
        let mainIconUploader = null;
        const $preview = $('#main-icon-preview');
        const initialBgColor = $preview.data('bg-color');

        if (initialBgColor) {
            $preview.css('background-color', initialBgColor);
        }

        $('#upload-main-icon').on('click', function(e) {
            e.preventDefault();
            
            if (mainIconUploader) {
                mainIconUploader.open();
                return;
            }
            
            mainIconUploader = wp.media({
                title: t('uploadIcon', 'Upload icon'),
                button: {
                    text: t('uploadIcon', 'Upload icon')
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

        $('#remove-main-icon').on('click', function() {
            removeMainIconPreview();
        });

        // Update preview background color when main button color changes
        $('input[name="cwp_chat_bubbles_options[main_button_color]"]').on('input change', function() {
            const newColor = $(this).val();
            const $preview = $('#main-icon-preview');
            // Only update if there's an image in the preview
            if ($preview.find('img').length) {
                $preview.css('background-color', newColor);
            }
        });
    }

    /**
     * Set main icon preview
     */
    function setMainIconPreview(attachmentId, imageUrl) {
        $('#custom-main-icon').val(attachmentId);
        
        // Get the main button color from the color input
        const mainButtonColor = $('input[name="cwp_chat_bubbles_options[main_button_color]"]').val() || '#52BA00';
        
        // Keep layout styles in CSS and only update dynamic values.
        const $preview = $('#main-icon-preview');
        $preview.css('background-color', mainButtonColor);
        $preview.html('<img src="' + imageUrl + '" alt="Custom main icon preview" class="cwp-main-icon-image">');
        
        $('#upload-main-icon').text(t('changeIcon', 'Change icon'));
        $('#remove-main-icon').show();
    }

    /**
     * Remove main icon preview
     */
    function removeMainIconPreview() {
        $('#custom-main-icon').val(0);
        
        // Reset preview state.
        const $preview = $('#main-icon-preview');
        $preview.empty();
        $preview.css('background-color', '');
        
        $('#upload-main-icon').text(t('uploadIcon', 'Upload icon'));
        $('#remove-main-icon').hide();
    }

    /**
     * Initialize QR code previews for existing items on page load
     */
    function initQRCodePreviews() {
        // Find all items with QR codes and ensure they have proper indicators
        $('.cwp-item[data-qr-code-id]').each(function() {
            const $item = $(this);
            const qrCodeId = $item.data('qr-code-id');
            
            if (qrCodeId && qrCodeId > 0) {
                // Add QR code indicator if not already present
                const $info = $item.find('.cwp-item-content');
                if (!$info.find('.dashicons-format-image').length) {
                    $info.append('<br><span class="dashicons dashicons-format-image cwp-qr-indicator" title="Has QR code"></span>');
                }
            }
        });
    }

    /**
     * Bind additional events
     */
    function bindEvents() {
        // Edit item
        $(document).on('click', '.edit-item', function() {
            const itemId = $(this).data('item-id');
            editItem(itemId);
        });

        // Delete item
        $(document).on('click', '.delete-item', function() {
            const itemId = $(this).data('item-id');
            const label = $(`.cwp-item[data-item-id="${itemId}"]`).data('label') || '';
            const message = label
                ? `${t('confirmRemoveItem', 'Remove this contact button?')}\n${label}\n\n${t('confirmRemoveItemDetail', 'This action cannot be undone.')}`
                : `${t('confirmRemoveItem', 'Remove this contact button?')}\n\n${t('confirmRemoveItemDetail', 'This action cannot be undone.')}`;
            if (confirm(message)) {
                deleteItem(itemId);
            }
        });
    }

    /**
     * Update contact field based on platform selection
     */
    function updateContactFieldForPlatform(platform) {
        // Check if platform is valid and platformConfigs exists
        if (!platform || !window.platformConfigs || !window.platformConfigs[platform]) {
            // Reset to default state when no platform or invalid platform
            const $contactField = $('#contact-value');
            const $contactLabel = $('#contact-label');
            const $contactDescription = $('#contact-description');
            
            $contactLabel.text(t('contactLabelDefault', 'Contact details'));
            $contactField.attr('placeholder', t('contactPlaceholderDefault', 'Enter contact details'));
            $contactDescription.text(t('contactDescriptionDefault', 'Choose a platform to see what format to use.'));
            $contactField.attr('pattern', '');
            return;
        }

        const config = window.platformConfigs[platform];
        const $contactField = $('#contact-value');
        const $contactLabel = $('#contact-label');
        const $contactDescription = $('#contact-description');

        // Update field label and placeholder
        $contactLabel.text(config.label + ' ' + config.contact_field.charAt(0).toUpperCase() + config.contact_field.slice(1));
        $contactField.attr('placeholder', config.placeholder);

        // Update description
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

        // Update validation pattern
        $contactField.attr('pattern', config.pattern ? config.pattern.slice(1, -1) : ''); // Remove regex delimiters
    }

    /**
     * Validate contact value based on platform with detailed error messages
     */
    function validateContactValue() {
        const platform = $('#platform').val();
        const contactValue = $('#contact-value').val().trim();
        
        if (!platform || !contactValue) return true;

        const config = window.platformConfigs[platform];
        if (!config || !config.pattern) return true;

        const regex = new RegExp(config.pattern.slice(1, -1)); // Remove regex delimiters
        const isValid = regex.test(contactValue);

        if (!isValid) {
            let errorMessage = t('genericFormatError', 'The contact details format is not valid for this platform.');
            
            // Provide specific error messages based on platform
            switch (platform) {
                case 'phone':
                    errorMessage = t('phoneFormatError', 'Enter a valid phone number, for example +1234567890 or 0123456789.');
                    break;
                case 'zalo':
                    errorMessage = t('zaloFormatError', 'Enter a valid Zalo phone number with 9 to 11 digits.');
                    break;
                case 'whatsapp':
                    errorMessage = t('whatsappFormatError', 'Enter a valid WhatsApp number with country code.');
                    break;
                case 'viber':
                    errorMessage = t('viberFormatError', 'Enter a valid Viber phone number, for example +1234567890.');
                    break;
                case 'telegram':
                    errorMessage = t('telegramFormatError', 'Enter a Telegram username with 5 to 32 characters (letters, numbers, underscore).');
                    break;
                case 'messenger':
                    errorMessage = t('messengerFormatError', 'Enter a valid Facebook username (letters, numbers, dots).');
                    break;
                case 'line':
                    errorMessage = t('lineFormatError', 'Enter a valid Line ID (letters, numbers, dots, dashes, underscore).');
                    break;
                case 'kakaotalk':
                    errorMessage = t('kakaotalkFormatError', 'Enter a valid KakaoTalk ID (letters, numbers, underscore, dash).');
                    break;
            }
            
            showFieldError('#contact-value', errorMessage);
        }

        return isValid;
    }

    /**
     * Set QR code preview
     */
    function setQRCodePreview(attachmentId, imageUrl) {
        $('#qr-code-id').val(attachmentId);
        $('#qr-preview').html('<img src="' + imageUrl + '" class="cwp-qr-image" alt="QR code preview">');
        $('#upload-qr-code').text(t('changeQrCode', 'Change QR code'));
        $('#remove-qr-code').show();
    }

    /**
     * Remove QR code preview
     */
    function removeQRCodePreview() {
        $('#qr-code-id').val(0);
        $('#qr-preview').empty();
        $('#upload-qr-code').text(t('uploadQrCode', 'Upload QR code'));
        $('#remove-qr-code').hide();
    }

    /**
     * Reset modal form to initial state
     */
    function resetModalForm() {
        $('#cwp-item-form')[0].reset();
        $('#item-id').val('');
        $('#platform').val('');
        
        // Clear all validation errors and messages
        clearAllErrors();
        
        // Remove old error messages (backwards compatibility)
        $('.error-message').remove();
        
        // Reset QR code state
        removeQRCodePreview();
        
        // Reset contact field to default state (this now handles empty platform properly)
        updateContactFieldForPlatform('');
        
        // Reset current editing item
        currentEditingItem = null;
    }

    /**
     * Edit item
     */
    function editItem(itemId) {
        // Get item data from the DOM (in a real implementation, you might fetch from server)
        const $item = $(`.cwp-item[data-item-id="${itemId}"]`);
        if (!$item.length) return;

        // Extract item data from DOM
        const itemData = extractItemDataFromDOM($item);
        
        // Populate form
        populateModalForm(itemData);
        
        currentEditingItem = itemId;
        $('#modal-title').text(t('editContactButton', 'Edit contact button'));
        $('#cwp-item-modal').addClass('active');
    }

    /**
     * Extract item data from DOM element
     */
    function extractItemDataFromDOM($item) {
        // Use data attributes instead of parsing text content
        return {
            id: $item.data('item-id'),
            platform: $item.data('platform'),
            label: $item.data('label'),
            contact_value: $item.data('contact-value'),
            enabled: $item.data('enabled') == 1,
            qr_code_id: $item.data('qr-code-id') || 0
        };
    }

    /**
     * Populate modal form with item data
     */
    function populateModalForm(itemData) {
        $('#item-id').val(itemData.id);
        $('#platform').val(itemData.platform).trigger('change'); // Trigger change to update contact field
        $('#label').val(itemData.label);
        $('#contact-value').val(itemData.contact_value);
        $('#enabled').prop('checked', itemData.enabled);
        
        // Handle QR code if exists
        if (itemData.qr_code_id && itemData.qr_code_id > 0) {
            // Make AJAX call to get QR code image URL
            $.ajax({
                url: wpAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cwp_chat_bubbles_get_attachment_url',
                    nonce: wpAjax.nonce,
                    attachment_id: itemData.qr_code_id
                },
                success: function(response) {
                    if (response.success && response.data && response.data.url) {
                        setQRCodePreview(itemData.qr_code_id, response.data.url);
                    } else {
                        // Fallback: Set QR code ID without preview
                        $('#qr-code-id').val(itemData.qr_code_id);
                        $('#upload-qr-code').text(t('changeQrCode', 'Change QR code'));
                        $('#remove-qr-code').show();
                        showNotice('warning', 'QR code was found, but preview is unavailable.');
                    }
                },
                error: function(xhr, status, error) {
                    // Fallback: Set QR code ID without preview
                    $('#qr-code-id').val(itemData.qr_code_id);
                    $('#upload-qr-code').text(t('changeQrCode', 'Change QR code'));
                    $('#remove-qr-code').show();
                    showNotice('warning', 'QR code was found, but preview could not load.');
                }
            });
        } else {
            removeQRCodePreview();
        }
    }

    /**
     * Save item via AJAX
     */
    function saveItem() {
        if (!validateForm()) return;

        const formData = new FormData();
        formData.append('action', 'cwp_chat_bubbles_save_item');
        formData.append('nonce', wpAjax.nonce);
        formData.append('item_id', $('#item-id').val());
        formData.append('platform', $('#platform').val());
        formData.append('label', $('#label').val());
        formData.append('contact_value', $('#contact-value').val());
        formData.append('qr_code_id', $('#qr-code-id').val());
        formData.append('enabled', $('#enabled').is(':checked') ? 1 : 0);

        $.ajax({
            url: wpAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#save-item').prop('disabled', true).text(t('saving', 'Saving…'));
            },
            success: function(response) {
                if (response.success) {
                    $('#cwp-items-container').html(response.data.items_html);
                    $('#cwp-item-modal').removeClass('active');
                    showNotice('success', response.data.message);
                    initSortable(); // Reinitialize sortable after content update
                } else {
                    showNotice('error', response.data || t('saveFailed', 'We could not save this contact button. Please try again.'));
                }
            },
            error: function() {
                showNotice('error', t('networkError', 'We could not connect. Please check your connection and try again.'));
            },
            complete: function() {
                $('#save-item').prop('disabled', false).text(t('saveContactButton', 'Save contact button'));
            }
        });
    }

    /**
     * Delete item via AJAX
     */
    function deleteItem(itemId) {
        $.ajax({
            url: wpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cwp_chat_bubbles_delete_item',
                nonce: wpAjax.nonce,
                item_id: itemId
            },
            success: function(response) {
                if (response.success) {
                    $('#cwp-items-container').html(response.data.items_html);
                    showNotice('success', response.data.message);
                    initSortable(); // Reinitialize sortable after content update
                } else {
                    showNotice('error', response.data || t('deleteFailed', 'We could not remove this contact button. Please try again.'));
                }
            },
            error: function() {
                showNotice('error', t('networkError', 'We could not connect. Please check your connection and try again.'));
            }
        });
    }

    /**
     * Save item order via AJAX
     */
    function saveItemOrder(orderedIds) {
        $.ajax({
            url: wpAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cwp_chat_bubbles_reorder_items',
                nonce: wpAjax.nonce,
                ordered_ids: orderedIds
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data);
                } else {
                    showNotice('error', response.data || t('reorderFailed', 'We could not update the order. Please try again.'));
                }
            },
            error: function() {
                showNotice('error', t('networkError', 'We could not connect. Please check your connection and try again.'));
            }
        });
    }

    /**
     * Validate form with enhanced UX feedback
     */
    function validateForm() {
        let isValid = true;
        clearAllErrors();

        // Validate platform selection
        const platform = $('#platform').val();
        if (!platform || platform.trim() === '') {
            showFieldError('#platform', t('selectPlatform', 'Please choose a platform.'));
            isValid = false;
        }

        // Validate label
        const label = $('#label').val().trim();
        if (!label) {
            showFieldError('#label', t('labelRequired', 'Enter a button label.'));
            isValid = false;
        } else if (label.length < 2) {
            showFieldError('#label', t('labelMin', 'Use at least 2 characters for the button label.'));
            isValid = false;
        } else if (label.length > 255) {
            showFieldError('#label', t('labelMax', 'Use 255 characters or fewer for the button label.'));
            isValid = false;
        }

        // Validate contact value
        const contactValue = $('#contact-value').val().trim();
        if (!contactValue) {
            showFieldError('#contact-value', t('contactRequired', 'Enter contact details.'));
            isValid = false;
        } else if (!validateContactValue()) {
            // validateContactValue() already shows its own error message
            isValid = false;
        }

        // Show general error message if validation fails
        if (!isValid) {
            showFormError(t('fixErrors', 'Please fix the highlighted fields and try again.'));
        }

        return isValid;
    }

    /**
     * Show error message for a specific field
     */
    function showFieldError(fieldSelector, message) {
        const $field = $(fieldSelector);
        const $row = $field.closest('tr');
        
        // Add error class to field
        $field.addClass('error');
        
        // Remove existing error message
        $row.find('.field-error').remove();
        
        // Add new error message (use text() to prevent XSS)
        const $errorDiv = $('<div>')
            .addClass('field-error')
            .text(message);
        $field.after($errorDiv);
    }

    /**
     * Show general form error message
     */
    function showFormError(message) {
        const $modalBody = $('.cwp-modal-body');
        
        // Remove existing form error
        $modalBody.find('.form-error').remove();
        
        // Add new form error at the top (use text() to prevent XSS)
        const $errorDiv = $('<div>')
            .addClass('form-error')
            .text(message);
        $modalBody.prepend($errorDiv);
    }

    /**
     * Clear all error messages and styling
     */
    function clearAllErrors() {
        $('.cwp-modal-body .field-error').remove();
        $('.cwp-modal-body .form-error').remove();
        $('.cwp-modal-body .error').removeClass('error');
    }

    /**
     * Show admin notice
     */
    function showNotice(type, message) {
        let noticeClass = 'notice-info';
        if (type === 'success') {
            noticeClass = 'notice-success';
        } else if (type === 'warning') {
            noticeClass = 'notice-warning';
        } else if (type === 'error') {
            noticeClass = 'notice-error';
        }
        
        // Build notice element safely (use text() to prevent XSS)
        const $notice = $('<div>')
            .addClass('notice')
            .addClass(noticeClass)
            .addClass('is-dismissible')
            .append($('<p>').text(message))
            .append(
                $('<button>')
                    .attr('type', 'button')
                    .addClass('notice-dismiss')
                    .append($('<span>').addClass('screen-reader-text').text(t('dismissNotice', 'Dismiss this notice.')))
            );

        const $target = $('.wrap .wp-header-end').first();
        if ($target.length) {
            $target.after($notice);
        } else {
            $('.wrap h1').first().after($notice);
        }
    }

    $(document).on('click', '.notice.is-dismissible .notice-dismiss', function() {
        $(this).closest('.notice').fadeOut(function() {
            $(this).remove();
        });
    });

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery); 
