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
        const chatItems = chatBubbles.querySelectorAll('.chat-item');
        const chatModals = chatBubbles.querySelectorAll('.bubble-modal');

        const toggleChatBubble = () => {
            chatBubbles.classList.toggle('active');
        };

        const closeChatBubble = () => {
            chatBubbles.classList.remove('active');
        };

        const openChatModal = (targetId) => {
            chatModals.forEach((chatModal) => {
                if (chatModal.id === targetId) {
                    chatModal.classList.add('active');
                } else {
                    chatModal.classList.remove('active');
                }
            });
        };

        const closeChatModal = () => {
            chatModals.forEach((chatModal) => {
                chatModal.classList.remove('active');
            });
        };

        // Main toggle button click handler
        chatToggle.addEventListener('click', (e) => {
            e.preventDefault();
            closeChatModal();
            toggleChatBubble();
        });

        // Open Chat Modal when clicking on chat items with QR codes
        chatItems.forEach((chatItem) => {
            chatItem.addEventListener('click', (e) => {
                const target = chatItem.getAttribute('data-bubble-modal');
                if (target) {
                    e.preventDefault();
                    openChatModal(target);
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
                closeChatBubble();
                closeChatModal();
            }
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