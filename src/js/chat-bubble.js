/**
 * CWP Chat Bubbles JavaScript
 * 
 * Handles chat bubble interactions and modal functionality
 * 
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

// Main chat bubbles functionality
const initChatBubbles = () => {
    // Check if chat bubble element exists
    const chatBubbles = document.getElementById('chat-bubbles');

    if (chatBubbles) {
        const chatToggle = chatBubbles.querySelector('.chat-btn-toggle');
        const chatPanel = chatBubbles.querySelector('.item-group');
        const chatItems = chatBubbles.querySelectorAll('.chat-item');
        const chatModals = chatBubbles.querySelectorAll('.bubble-modal');
        let activeModalTrigger = null;

        if (!chatToggle || !chatPanel) {
            return;
        }

        const setBubbleState = (isOpen) => {
            chatBubbles.classList.toggle('active', isOpen);
            chatToggle.setAttribute('aria-expanded', String(isOpen));
            chatToggle.setAttribute(
                'aria-label',
                isOpen ? chatToggle.dataset.labelClose : chatToggle.dataset.labelOpen
            );
            chatPanel.setAttribute('aria-hidden', String(!isOpen));
        };

        const toggleChatBubble = () => {
            setBubbleState(!chatBubbles.classList.contains('active'));
        };

        const closeChatBubble = () => {
            setBubbleState(false);
        };

        const getActiveModal = () => chatBubbles.querySelector('.bubble-modal.active');

        const openChatModal = (targetId, triggerElement) => {
            chatModals.forEach((chatModal) => {
                if (chatModal.id === targetId) {
                    chatModal.classList.add('active');
                    chatModal.setAttribute('aria-hidden', 'false');
                    activeModalTrigger = triggerElement || chatToggle;
                    const closeButton = chatModal.querySelector('.bubble-modal-close');
                    window.requestAnimationFrame(() => {
                        if (closeButton) {
                            closeButton.focus();
                        } else {
                            chatModal.focus();
                        }
                    });
                } else {
                    chatModal.classList.remove('active');
                    chatModal.setAttribute('aria-hidden', 'true');
                }
            });
        };

        const closeChatModal = () => {
            chatModals.forEach((chatModal) => {
                chatModal.classList.remove('active');
                chatModal.setAttribute('aria-hidden', 'true');
            });

            if (activeModalTrigger instanceof HTMLElement) {
                activeModalTrigger.focus();
            }

            activeModalTrigger = null;
        };

        const trapModalFocus = (event) => {
            if (event.key !== 'Tab') {
                return;
            }

            const activeModal = getActiveModal();
            if (!activeModal) {
                return;
            }

            const focusableElements = activeModal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            if (!focusableElements.length) {
                return;
            }

            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            if (event.shiftKey && document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            } else if (!event.shiftKey && document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        };

        setBubbleState(false);
        closeChatModal();

        // Main toggle button click handler
        chatToggle.addEventListener('click', (e) => {
            e.preventDefault();
            const isOpen = chatBubbles.classList.contains('active');
            if (isOpen) {
                closeChatModal();
            }
            toggleChatBubble();
        });

        // Open Chat Modal when clicking on chat items with QR codes
        chatItems.forEach((chatItem) => {
            chatItem.addEventListener('click', (e) => {
                const target = chatItem.getAttribute('data-bubble-modal');
                if (target) {
                    e.preventDefault();
                    openChatModal(target, chatItem);
                } else {
                    // Let the link open normally if no modal
                    return true;
                }
            });
        });

        // Close modal buttons
        chatModals.forEach((chatModal) => {
            const chatModalClose = chatModal.querySelector('.bubble-modal-close');
            if (chatModalClose) {
                chatModalClose.addEventListener('click', (e) => {
                    e.preventDefault();
                    closeChatModal();
                });
            }

            chatModal.addEventListener('click', (event) => {
                if (event.target === chatModal) {
                    closeChatModal();
                }
            });
        });

        // Close Chat Bubble and Modals when clicking outside
        document.addEventListener('click', (e) => {
            if (!chatBubbles.contains(e.target)) {
                closeChatBubble();
                closeChatModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (getActiveModal()) {
                    closeChatModal();
                    return;
                }

                closeChatBubble();
            }

            trapModalFocus(e);
        });
    }
};

// Initialize when DOM is ready
const init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatBubbles);
    } else {
        initChatBubbles();
    }
};

// Auto-initialize
init();

// Export for potential manual initialization
window.CWPChatBubbles = {
    init: initChatBubbles
};
