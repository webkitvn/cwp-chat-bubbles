/**
 * CWP Chat Bubbles Admin JavaScript
 * Modern ES6 implementation with vanilla JavaScript (no jQuery)
 * 
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Admin module using ES6 class and arrow functions
class CWPChatBubblesAdmin {
    constructor() {
        this.init();
    }

    // Initialize admin functionality
    init = () => {
        document.addEventListener('DOMContentLoaded', () => {
            this.initTabs();
            this.initModal();
            this.initSortable();
            this.initFormHandlers();
            this.initMediaUploaders();
            this.initColorPicker();
            this.initItemActions();
        });
    }

    // Tab navigation handler
    initTabs = () => {
        const tabLinks = document.querySelectorAll('.nav-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabLinks.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const targetTab = tab.getAttribute('href');

                // Remove active class from all tabs
                tabLinks.forEach(t => t.classList.remove('nav-tab-active'));
                tab.classList.add('nav-tab-active');

                // Hide all tab contents
                tabContents.forEach(content => content.style.display = 'none');
                
                // Show target tab
                const targetContent = document.querySelector(targetTab);
                if (targetContent) {
                    targetContent.style.display = 'block';
                }
            });
        });
    }

    // Modal handlers
    initModal = () => {
        const addNewBtn = document.getElementById('add-new-item');
        const modal = document.getElementById('cwp-item-modal');
        const closeButtons = document.querySelectorAll('.cwp-modal-close, #cancel-item');

        // Open modal
        if (addNewBtn) {
            addNewBtn.addEventListener('click', () => {
                this.resetForm();
                document.getElementById('modal-title').textContent = 'Add New Item';
                modal.style.display = 'block';
            });
        }

        // Close modal
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        });

        // Close on background click
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    }

    // Sortable functionality (vanilla JS implementation)
    initSortable = () => {
        const sortableContainer = document.getElementById('sortable-items');
        if (!sortableContainer) return;

        let draggedElement = null;
        let placeholder = null;

        // Add drag attributes to all items
        const items = sortableContainer.querySelectorAll('.cwp-item');
        items.forEach(item => {
            item.setAttribute('draggable', true);
            this.addDragListeners(item);
        });

        // Add drag listeners to an item
        this.addDragListeners = (item) => {
            item.addEventListener('dragstart', (e) => {
                draggedElement = item;
                item.classList.add('cwp-dragging');
                
                // Create placeholder
                placeholder = document.createElement('div');
                placeholder.className = 'cwp-item-placeholder';
                placeholder.style.height = item.offsetHeight + 'px';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('cwp-dragging');
                if (placeholder && placeholder.parentNode) {
                    placeholder.parentNode.removeChild(placeholder);
                }
                draggedElement = null;
                placeholder = null;

                // Update order
                this.updateItemsOrder();
            });

            item.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (draggedElement === item) return;

                const container = item.parentNode;
                const afterElement = this.getDragAfterElement(container, e.clientY);
                
                if (afterElement == null) {
                    container.appendChild(placeholder);
                } else {
                    container.insertBefore(placeholder, afterElement);
                }
            });
        };

        // Get element after drag position
        this.getDragAfterElement = (container, y) => {
            const draggableElements = [...container.querySelectorAll('.cwp-item:not(.cwp-dragging)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        };
    }

    // Update items order after drag
    updateItemsOrder = () => {
        const items = document.querySelectorAll('#sortable-items .cwp-item');
        const orderedIds = Array.from(items).map(item => 
            parseInt(item.getAttribute('data-item-id'))
        );

        if (orderedIds.length === 0) return;

        // Send AJAX request to update order
        this.sendAjaxRequest('reorder_items', {
            ordered_ids: orderedIds
        }).then(response => {
            if (!response.success) {
                console.error('Failed to update item order');
            }
        });
    }

    // Form handlers
    initFormHandlers = () => {
        const saveBtn = document.getElementById('save-item');
        const form = document.getElementById('cwp-item-form');
        const platformSelect = document.getElementById('platform');

        // Save item
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.saveItem();
            });
        }

        // Form submission
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.saveItem();
            });
        }

        // Platform change
        if (platformSelect) {
            platformSelect.addEventListener('change', () => {
                this.updateContactFieldForPlatform(platformSelect.value);
            });
        }

        // Real-time validation
        const labelField = document.getElementById('label');
        const contactField = document.getElementById('contact-value');

        if (labelField) {
            labelField.addEventListener('input', () => this.validateField('label'));
            labelField.addEventListener('blur', () => this.validateField('label'));
        }

        if (contactField) {
            contactField.addEventListener('input', () => this.validateField('contact-value'));
            contactField.addEventListener('blur', () => this.validateField('contact-value'));
        }
    }

    // Media uploaders
    initMediaUploaders = () => {
        this.initQRCodeUploader();
        this.initMainIconUploader();
    }

    // QR Code uploader
    initQRCodeUploader = () => {
        const uploadBtn = document.getElementById('upload-qr-code');
        const removeBtn = document.getElementById('remove-qr-code');

        if (uploadBtn) {
            uploadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const mediaUploader = wp.media({
                    title: 'Select QR Code Image',
                    button: { text: 'Use this image' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    this.setQRCodePreview(attachment.id, attachment.url);
                });

                mediaUploader.open();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.clearQRCodePreview();
            });
        }
    }

    // Main icon uploader
    initMainIconUploader = () => {
        const uploadBtn = document.getElementById('upload-main-icon');
        const removeBtn = document.getElementById('remove-main-icon');

        if (uploadBtn) {
            uploadBtn.addEventListener('click', (e) => {
                e.preventDefault();
                
                const mediaUploader = wp.media({
                    title: 'Select Custom Main Icon',
                    button: { text: 'Use this icon' },
                    multiple: false,
                    library: { type: 'image' }
                });

                mediaUploader.on('select', () => {
                    const attachment = mediaUploader.state().get('selection').first().toJSON();
                    this.setMainIconPreview(attachment.id, attachment.url);
                });

                mediaUploader.open();
            });
        }

        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.clearMainIconPreview();
            });
        }
    }

    // Color picker handler
    initColorPicker = () => {
        const colorInput = document.querySelector('input[name="cwp_chat_bubbles_options[main_button_color]"]');
        
        if (colorInput) {
            colorInput.addEventListener('input', (e) => {
                this.updateMainIconPreviewColor(e.target.value);
            });
            
            colorInput.addEventListener('change', (e) => {
                this.updateMainIconPreviewColor(e.target.value);
            });
        }
    }

    // Item actions (edit/delete)
    initItemActions = () => {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('edit-item')) {
                const itemId = parseInt(e.target.getAttribute('data-item-id'));
                this.editItem(itemId);
            }
            
            if (e.target.classList.contains('delete-item')) {
                const itemId = parseInt(e.target.getAttribute('data-item-id'));
                this.deleteItem(itemId);
            }
        });
    }

    // Platform contact field updater
    updateContactFieldForPlatform = (platform) => {
        const contactField = document.getElementById('contact-value');
        const contactLabel = document.getElementById('contact-label');
        const contactDescription = document.getElementById('contact-description');

        if (!contactField || !window.platformConfigs) return;

        const config = window.platformConfigs[platform];
        if (!config) {
            // Reset to default
            contactLabel.textContent = 'Contact Info';
            contactField.placeholder = '';
            contactDescription.textContent = '';
            return;
        }

        contactLabel.textContent = config.contact_label;
        contactField.placeholder = config.placeholder;
        contactDescription.textContent = config.description;
    }

    // Field validation
    validateField = (fieldId) => {
        const field = document.getElementById(fieldId);
        if (!field) return true;

        this.removeFieldError(fieldId);

        if (fieldId === 'label') {
            return this.validateLabel();
        }
        
        if (fieldId === 'contact-value') {
            return this.validateContactValue();
        }

        return true;
    }

    // Validate label field
    validateLabel = () => {
        const labelField = document.getElementById('label');
        const label = labelField.value.trim();

        if (label.length < 2) {
            this.showFieldError('label', 'Label must be at least 2 characters long');
            return false;
        }

        if (label.length > 50) {
            this.showFieldError('label', 'Label must be less than 50 characters');
            return false;
        }

        return true;
    }

    // Validate contact value
    validateContactValue = () => {
        const platform = document.getElementById('platform').value;
        const contactValue = document.getElementById('contact-value').value.trim();

        if (!platform || !contactValue) return false;

        const config = window.platformConfigs[platform];
        if (!config) return false;

        // Platform-specific validation
        if (platform === 'phone' && !/^[+]?[0-9\-()\\s]+$/.test(contactValue)) {
            this.showFieldError('contact-value', 'Please enter a valid phone number');
            return false;
        }

        if (platform === 'whatsapp' && !/^[+]?[0-9]+$/.test(contactValue.replace(/\s/g, ''))) {
            this.showFieldError('contact-value', 'WhatsApp number should contain only digits and +');
            return false;
        }

        if ((platform === 'zalo' || platform === 'telegram') && !/^[0-9]+$/.test(contactValue)) {
            this.showFieldError('contact-value', 'Please enter only numbers');
            return false;
        }

        return true;
    }

    // Show field error
    showFieldError = (fieldId, message) => {
        const field = document.getElementById(fieldId);
        if (!field) return;

        this.removeFieldError(fieldId);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.style.color = '#dc3232';
        errorDiv.style.fontSize = '12px';
        errorDiv.style.marginTop = '4px';
        errorDiv.textContent = message;

        field.parentNode.appendChild(errorDiv);
        field.style.borderColor = '#dc3232';
    }

    // Remove field error
    removeFieldError = (fieldId) => {
        const field = document.getElementById(fieldId);
        if (!field) return;

        const errorMsg = field.parentNode.querySelector('.error-message');
        if (errorMsg) {
            errorMsg.remove();
        }
        field.style.borderColor = '';
    }

    // QR Code preview handlers
    setQRCodePreview = (attachmentId, imageUrl) => {
        document.getElementById('qr-code-id').value = attachmentId;
        document.getElementById('qr-preview').innerHTML = 
            `<img src="${imageUrl}" style="max-width: 150px; height: auto; border: 1px solid #ddd;">`;
        document.getElementById('upload-qr-code').textContent = 'Change QR Code';
        document.getElementById('remove-qr-code').style.display = 'inline-block';
    }

    clearQRCodePreview = () => {
        document.getElementById('qr-code-id').value = '0';
        document.getElementById('qr-preview').innerHTML = '';
        document.getElementById('upload-qr-code').textContent = 'Upload QR Code';
        document.getElementById('remove-qr-code').style.display = 'none';
    }

    // Main icon preview handlers
    setMainIconPreview = (attachmentId, imageUrl) => {
        document.getElementById('custom-main-icon').value = attachmentId;
        
        const colorInput = document.querySelector('input[name="cwp_chat_bubbles_options[main_button_color]"]');
        const mainButtonColor = colorInput ? colorInput.value : '#52BA00';
        
        const preview = document.getElementById('main-icon-preview');
        preview.innerHTML = `<img src="${imageUrl}" alt="Custom main icon preview" style="width:80%; height:auto;">`;
        preview.style.backgroundColor = mainButtonColor;
        preview.style.display = 'block';

        document.getElementById('upload-main-icon').textContent = 'Change Custom Icon';
        document.getElementById('remove-main-icon').style.display = 'inline-block';
    }

    clearMainIconPreview = () => {
        document.getElementById('custom-main-icon').value = '0';
        
        const preview = document.getElementById('main-icon-preview');
        preview.innerHTML = '';
        preview.style.display = 'none';

        document.getElementById('upload-main-icon').textContent = 'Upload Custom Icon';
        document.getElementById('remove-main-icon').style.display = 'none';
    }

    updateMainIconPreviewColor = (newColor) => {
        const preview = document.getElementById('main-icon-preview');
        if (preview) {
            preview.style.backgroundColor = newColor;
        }
    }

    // Edit item
    editItem = (itemId) => {
        const item = document.querySelector(`.cwp-item[data-item-id="${itemId}"]`);
        if (!item) return;

        const itemData = {
            id: item.getAttribute('data-item-id'),
            platform: item.getAttribute('data-platform'),
            label: item.getAttribute('data-label'),
            contact_value: item.getAttribute('data-contact-value'),
            enabled: item.getAttribute('data-enabled') === '1',
            qr_code_id: item.getAttribute('data-qr-code-id') || '0'
        };

        // Populate form
        document.getElementById('modal-title').textContent = 'Edit Item';
        document.getElementById('item-id').value = itemData.id;
        document.getElementById('platform').value = itemData.platform;
        document.getElementById('label').value = itemData.label;
        document.getElementById('contact-value').value = itemData.contact_value;
        document.getElementById('enabled').checked = itemData.enabled;

        // Update contact field for platform
        this.updateContactFieldForPlatform(itemData.platform);

        // Handle QR code preview
        if (itemData.qr_code_id && itemData.qr_code_id !== '0') {
            this.sendAjaxRequest('chat_bubbles_get_attachment_url', {
                attachment_id: itemData.qr_code_id
            }).then(response => {
                if (response.success && response.data.url) {
                    this.setQRCodePreview(itemData.qr_code_id, response.data.url);
                } else {
                    this.clearQRCodePreview();
                }
            });
        } else {
            this.clearQRCodePreview();
        }

        // Show modal
        document.getElementById('cwp-item-modal').style.display = 'block';
    }

    // Delete item
    deleteItem = (itemId) => {
        if (!confirm('Are you sure you want to delete this item?')) return;

        this.sendAjaxRequest('delete_item', {
            item_id: itemId
        }).then(response => {
            if (response.success) {
                this.refreshItemsList(response.data.items_html);
            } else {
                alert('Failed to delete item: ' + (response.data || 'Unknown error'));
            }
        });
    }

    // Save item
    saveItem = () => {
        if (!this.validateForm()) return;

        const formData = new FormData();
        formData.append('action', 'cwp_save_item');
        formData.append('nonce', cwpChatBubblesAjax.nonce);
        formData.append('item_id', document.getElementById('item-id').value);
        formData.append('platform', document.getElementById('platform').value);
        formData.append('label', document.getElementById('label').value);
        formData.append('contact_value', document.getElementById('contact-value').value);
        formData.append('qr_code_id', document.getElementById('qr-code-id').value);
        formData.append('enabled', document.getElementById('enabled').checked ? 1 : 0);

        const saveBtn = document.getElementById('save-item');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        fetch(cwpChatBubblesAjax.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.refreshItemsList(data.data.items_html);
                document.getElementById('cwp-item-modal').style.display = 'none';
            } else {
                alert('Failed to save item: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Save error:', error);
            alert('Failed to save item');
        })
        .finally(() => {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Item';
        });
    }

    // Validate entire form
    validateForm = () => {
        let isValid = true;

        // Clear all errors first
        document.querySelectorAll('.error-message').forEach(error => error.remove());

        const platform = document.getElementById('platform').value;
        if (!platform) {
            this.showFieldError('platform', 'Please select a platform');
            isValid = false;
        }

        if (!this.validateLabel()) isValid = false;
        if (!this.validateContactValue()) isValid = false;

        return isValid;
    }

    // Reset form
    resetForm = () => {
        document.getElementById('cwp-item-form').reset();
        document.getElementById('item-id').value = '';
        document.getElementById('platform').value = '';
        this.clearQRCodePreview();
        this.updateContactFieldForPlatform('');
        
        // Clear all errors
        document.querySelectorAll('.error-message').forEach(error => error.remove());
    }

    // Refresh items list
    refreshItemsList = (itemsHtml) => {
        const container = document.getElementById('cwp-items-container');
        if (container && itemsHtml) {
            container.innerHTML = itemsHtml;
            // Re-init sortable for new items
            this.initSortable();
        }
    }

    // Generic AJAX helper using modern fetch API
    sendAjaxRequest = async (action, data = {}) => {
        const formData = new FormData();
        formData.append('action', `cwp_${action}`);
        formData.append('nonce', cwpChatBubblesAjax.nonce);
        
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });

        try {
            const response = await fetch(cwpChatBubblesAjax.ajax_url, {
                method: 'POST',
                body: formData
            });
            return await response.json();
        } catch (error) {
            console.error('AJAX request failed:', error);
            return { success: false, data: 'Network error' };
        }
    }
}

// Initialize admin when DOM is ready
const cwpAdmin = new CWPChatBubblesAdmin();

// Export for potential external usage
export default CWPChatBubblesAdmin; 