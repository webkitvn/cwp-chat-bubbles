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

    /**
     * Initialize admin functionality
     */
    function init() {
        initTabs();
        initModal();
        initSortable();
        initFormHandling();
        initQRCodeUpload();
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
            $('#modal-title').text('Add New Item');
            $('#cwp-item-modal').show();
        });

        // Close modal
        $('.cwp-modal-close, #cancel-item').on('click', function() {
            $('#cwp-item-modal').hide();
        });

        // Close modal when clicking outside
        $('#cwp-item-modal').on('click', function(e) {
            if (e.target === this) {
                $(this).hide();
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
                    showFieldError(fieldSelector, 'Please select a platform type');
                    return false;
                }
                break;
                
            case '#label':
                if (!value) {
                    showFieldError(fieldSelector, 'Display label is required');
                    return false;
                } else if (value.length < 2) {
                    showFieldError(fieldSelector, 'Display label must be at least 2 characters long');
                    return false;
                } else if (value.length > 255) {
                    showFieldError(fieldSelector, 'Display label must be less than 255 characters');
                    return false;
                }
                break;
                
            case '#contact-value':
                if (!value) {
                    showFieldError(fieldSelector, 'Contact information is required');
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
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Select QR Code Image',
                button: {
                    text: 'Use This Image'
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

        $('#remove-qr-code').on('click', function() {
            removeQRCodePreview();
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
            if (confirm('Are you sure you want to delete this item?')) {
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
            let errorMessage = 'Invalid format for this platform.';
            
            // Provide specific error messages based on platform
            switch (platform) {
                case 'phone':
                    errorMessage = 'Please enter a valid phone number (e.g., +1234567890 or 0123456789)';
                    break;
                case 'zalo':
                    errorMessage = 'Please enter a valid Zalo phone number (9-11 digits, e.g., 0123456789)';
                    break;
                case 'whatsapp':
                    errorMessage = 'Please enter a valid WhatsApp number with country code (e.g., 1234567890)';
                    break;
                case 'viber':
                    errorMessage = 'Please enter a valid Viber phone number (e.g., +1234567890)';
                    break;
                case 'telegram':
                    errorMessage = 'Please enter a valid Telegram username (5-32 characters, letters, numbers, underscore only)';
                    break;
                case 'messenger':
                    errorMessage = 'Please enter a valid Facebook username (letters, numbers, dots only)';
                    break;
                case 'line':
                    errorMessage = 'Please enter a valid Line ID (letters, numbers, dots, dashes, underscore only)';
                    break;
                case 'kakaotalk':
                    errorMessage = 'Please enter a valid KakaoTalk ID (letters, numbers, underscore, dash only)';
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
        $('#qr-preview').html(`<img src="${imageUrl}" style="max-width: 150px; height: auto; border: 1px solid #ddd;">`);
        $('#upload-qr-code').text('Change QR Code');
        $('#remove-qr-code').show();
    }

    /**
     * Remove QR code preview
     */
    function removeQRCodePreview() {
        $('#qr-code-id').val(0);
        $('#qr-preview').empty();
        $('#upload-qr-code').text('Upload QR Code');
        $('#remove-qr-code').hide();
    }

    /**
     * Reset modal form
     */
    function resetModalForm() {
        $('#cwp-item-form')[0].reset();
        $('#item-id').val('');
        $('#platform').val('');
        
        // Clear all validation errors and messages
        clearAllErrors();
        
        // Remove old error messages (backwards compatibility)
        $('.error-message').remove();
        
        removeQRCodePreview();
        // Reset contact field to default state (this now handles empty platform properly)
        updateContactFieldForPlatform('');
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
        $('#modal-title').text('Edit Item');
        $('#cwp-item-modal').show();
    }

    /**
     * Extract item data from DOM element
     */
    function extractItemDataFromDOM($item) {
        // This is a simplified version - in practice you might store data attributes
        // or fetch from server
        const $info = $item.find('.cwp-item-info');
        const label = $info.find('strong').text();
        const details = $info.find('small').text();
        const enabled = $item.find('.dashicons-yes-alt').length > 0;
        
        // Parse platform and contact from details text
        const parts = details.split(': ');
        const platform = parts[0].toLowerCase().replace(/\s+/g, '');
        const contactValue = parts[1] || '';
        
        return {
            id: $item.data('item-id'),
            platform: platform,
            label: label,
            contact_value: contactValue,
            enabled: enabled,
            qr_code_id: 0 // Would need to be stored in data attribute
        };
    }

    /**
     * Populate modal form with item data
     */
    function populateModalForm(itemData) {
        $('#item-id').val(itemData.id);
        $('#platform').val(itemData.platform);
        $('#label').val(itemData.label);
        $('#contact-value').val(itemData.contact_value);
        $('#enabled').prop('checked', itemData.enabled);
        
        updateContactFieldForPlatform(itemData.platform);
        
        if (itemData.qr_code_id) {
            setQRCodePreview(itemData.qr_code_id, ''); // Would need actual URL
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
                $('#save-item').prop('disabled', true).text('Saving...');
            },
            success: function(response) {
                if (response.success) {
                    $('#cwp-items-container').html(response.data.items_html);
                    $('#cwp-item-modal').hide();
                    showNotice('success', response.data.message);
                    initSortable(); // Reinitialize sortable after content update
                } else {
                    showNotice('error', response.data || 'Failed to save item');
                }
            },
            error: function() {
                showNotice('error', 'Network error occurred');
            },
            complete: function() {
                $('#save-item').prop('disabled', false).text('Save Item');
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
                    showNotice('error', response.data || 'Failed to delete item');
                }
            },
            error: function() {
                showNotice('error', 'Network error occurred');
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
                    showNotice('error', response.data || 'Failed to reorder items');
                }
            },
            error: function() {
                showNotice('error', 'Network error occurred');
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
            showFieldError('#platform', 'Please select a platform type');
            isValid = false;
        }

        // Validate label
        const label = $('#label').val().trim();
        if (!label) {
            showFieldError('#label', 'Display label is required');
            isValid = false;
        } else if (label.length < 2) {
            showFieldError('#label', 'Display label must be at least 2 characters long');
            isValid = false;
        } else if (label.length > 255) {
            showFieldError('#label', 'Display label must be less than 255 characters');
            isValid = false;
        }

        // Validate contact value
        const contactValue = $('#contact-value').val().trim();
        if (!contactValue) {
            showFieldError('#contact-value', 'Contact information is required');
            isValid = false;
        } else if (!validateContactValue()) {
            // validateContactValue() already shows its own error message
            isValid = false;
        }

        // Show general error message if validation fails
        if (!isValid) {
            showFormError('Please correct the highlighted errors before saving.');
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
        
        // Add new error message
        $field.after(`<div class="field-error" style="color: #d63638; font-size: 12px; margin-top: 5px;">${message}</div>`);
    }

    /**
     * Show general form error message
     */
    function showFormError(message) {
        const $modalBody = $('.cwp-modal-body');
        
        // Remove existing form error
        $modalBody.find('.form-error').remove();
        
        // Add new form error at the top
        $modalBody.prepend(`<div class="form-error" style="background: #fbeaea; border: 1px solid #d63638; border-radius: 4px; padding: 10px; margin-bottom: 15px; color: #d63638; font-weight: 500;">${message}</div>`);
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
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $(`<div class="notice ${noticeClass} is-dismissible"><p>${message}</p></div>`);
        
        $('.wrap h1').after($notice);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }

    // Initialize when DOM is ready
    $(document).ready(init);

})(jQuery); 