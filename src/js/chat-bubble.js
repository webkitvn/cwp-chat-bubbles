/**
 * CWP Chat Bubbles JavaScript
 *
 * Supports both static rendering and lazy auto-inject rendering.
 *
 * @package CWP_Chat_Bubbles
 * @since 1.1.1
 */

const LAZY_FETCH_TIMEOUT = 4000;
const PREFETCH_DELAY = 2500;
const RETRY_DELAY = 300;

const wait = (ms) => new Promise((resolve) => {
    window.setTimeout(resolve, ms);
});

const shouldSkipPrefetch = () => {
    const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    if (!connection) {
        return false;
    }

    if (connection.saveData) {
        return true;
    }

    return connection.effectiveType === '2g' || connection.effectiveType === 'slow-2g';
};

const renderLazyItems = (chatBubbles, payload) => {
    const itemGroup = chatBubbles.querySelector('.item-group');
    const modalContainer = chatBubbles.querySelector('.cwp-chat-modals');

    if (!itemGroup || !modalContainer) {
        return;
    }

    itemGroup.innerHTML = '';
    modalContainer.innerHTML = '';

    const items = Array.isArray(payload.items) ? payload.items : [];
    const showLabels = !!(payload.settings && payload.settings.show_labels);
    const cancelIcon = (chatBubbles.querySelector('.chat-icon-close') || {}).src || '';

    if (showLabels) {
        itemGroup.classList.remove('no-labels');
    } else {
        itemGroup.classList.add('no-labels');
    }

    const itemsFragment = document.createDocumentFragment();
    const modalsFragment = document.createDocumentFragment();

    items.forEach((item) => {
        const itemLink = document.createElement('a');
        itemLink.className = `chat-item chat-item-${item.platform}`;
        itemLink.href = item.platform_url || '#';
        itemLink.title = item.label || item.platform || '';

        if (item.has_qr) {
            itemLink.dataset.bubbleModal = `modal-${item.id}`;
            itemLink.dataset.noDirectLink = 'true';
        } else {
            itemLink.target = '_blank';
            itemLink.rel = 'noopener noreferrer';
        }

        const icon = document.createElement('img');
        icon.src = item.platform_icon || '';
        icon.alt = item.platform || '';
        icon.width = 24;
        icon.height = 24;
        icon.loading = 'lazy';
        itemLink.appendChild(icon);

        if (showLabels) {
            const text = document.createElement('span');
            text.className = 'chat-item-text';
            text.textContent = item.label || '';
            itemLink.appendChild(text);
        }

        itemsFragment.appendChild(itemLink);

        if (item.has_qr && item.qr_code_url) {
            const modal = document.createElement('div');
            modal.className = 'bubble-modal';
            modal.id = `modal-${item.id}`;
            modal.tabIndex = -1;
            modal.setAttribute('aria-hidden', 'true');

            const closeButton = document.createElement('button');
            closeButton.className = 'bubble-modal-close';
            closeButton.setAttribute('aria-label', 'Close modal');
            if (cancelIcon) {
                const closeImage = document.createElement('img');
                closeImage.src = cancelIcon;
                closeImage.alt = 'Close';
                closeButton.appendChild(closeImage);
            }

            const body = document.createElement('div');
            body.className = 'modal-body';

            const qrWrapper = document.createElement('div');
            qrWrapper.className = 'qrcode';
            const heading = document.createElement('h3');
            heading.textContent = item.label || '';
            const qrImage = document.createElement('img');
            qrImage.src = item.qr_code_url;
            qrImage.alt = `${item.label || 'QR'} QR Code`;
            qrImage.loading = 'lazy';
            qrWrapper.append(heading, qrImage);

            body.appendChild(qrWrapper);

            if (item.platform_url && item.platform_url !== '#') {
                const action = document.createElement('a');
                action.className = 'btn';
                action.href = item.platform_url;
                action.target = '_blank';
                action.rel = 'noopener noreferrer';
                if (item.platform_color) {
                    action.style.backgroundColor = item.platform_color;
                }

                const iconWrapper = document.createElement('div');
                iconWrapper.className = 'icon';
                const actionIcon = document.createElement('img');
                actionIcon.src = item.platform_icon || '';
                actionIcon.alt = item.label || '';
                actionIcon.width = 24;
                actionIcon.height = 24;
                actionIcon.loading = 'lazy';
                iconWrapper.appendChild(actionIcon);

                const label = document.createElement('span');
                label.textContent = `Open ${item.label || ''}`.trim();

                action.append(iconWrapper, label);
                body.appendChild(action);
            }

            modal.append(closeButton, body);
            modalsFragment.appendChild(modal);
        }
    });

    itemGroup.appendChild(itemsFragment);
    modalContainer.appendChild(modalsFragment);
};

const initializeChatBubbles = () => {
    const chatBubbles = document.getElementById('chat-bubbles');
    if (!chatBubbles) {
        return;
    }

    const chatToggle = chatBubbles.querySelector('.chat-btn-toggle');
    if (!chatToggle) {
        return;
    }

    const isLazy = chatBubbles.dataset.lazy === '1';
    const endpoint = chatBubbles.dataset.endpoint || '';
    const prefetchEnabled = chatBubbles.dataset.prefetch === '1';

    const state = {
        mode: isLazy ? 'lazy' : 'static',
        loadState: isLazy ? 'idle' : 'loaded',
        loadPromise: null,
        itemsLoaded: !isLazy
    };

    const setStatusUI = () => {
        if (state.loadState === 'loading') {
            chatBubbles.classList.add('is-loading');
            return;
        }

        chatBubbles.classList.remove('is-loading');
    };

    const closeChatBubble = () => {
        chatBubbles.classList.remove('active');
    };

    const closeChatModal = () => {
        chatBubbles.querySelectorAll('.bubble-modal.active').forEach((modal) => {
            modal.classList.remove('active');
        });
    };

    const openChatModal = (targetId) => {
        chatBubbles.querySelectorAll('.bubble-modal').forEach((modal) => {
            if (modal.id === targetId) {
                modal.classList.add('active');
            } else {
                modal.classList.remove('active');
            }
        });
    };

    const fetchItemsPayload = async (attempt = 0) => {
        if (!endpoint) {
            throw new Error('Missing lazy endpoint');
        }

        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => {
            controller.abort();
        }, LAZY_FETCH_TIMEOUT);

        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                },
                signal: controller.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            if (attempt === 0) {
                await wait(RETRY_DELAY);
                return fetchItemsPayload(1);
            }
            throw error;
        } finally {
            window.clearTimeout(timeoutId);
        }
    };

    const ensureItemsLoaded = async () => {
        if (state.itemsLoaded) {
            return 'loaded';
        }

        if (state.loadPromise) {
            return state.loadPromise;
        }

        state.loadState = 'loading';
        setStatusUI();

        state.loadPromise = (async () => {
            try {
                const payload = await fetchItemsPayload();
                const items = Array.isArray(payload.items) ? payload.items : [];

                if (items.length === 0) {
                    state.loadState = 'empty';
                    setStatusUI();
                    chatBubbles.style.display = 'none';
                    return 'empty';
                }

                renderLazyItems(chatBubbles, payload);
                state.itemsLoaded = true;
                state.loadState = 'loaded';
                setStatusUI();
                return 'loaded';
            } catch {
                state.loadState = 'error';
                setStatusUI();
                return 'error';
            } finally {
                state.loadPromise = null;
            }
        })();

        return state.loadPromise;
    };

    const triggerPrefetch = () => {
        if (state.mode !== 'lazy' || !prefetchEnabled || shouldSkipPrefetch()) {
            return;
        }

        if (typeof window.requestIdleCallback === 'function') {
            window.setTimeout(() => {
                window.requestIdleCallback(() => {
                    void ensureItemsLoaded();
                });
            }, PREFETCH_DELAY);
            return;
        }

        window.setTimeout(() => {
            void ensureItemsLoaded();
        }, PREFETCH_DELAY);
    };

    chatToggle.addEventListener('click', async (event) => {
        event.preventDefault();
        closeChatModal();

        if (state.mode === 'lazy' && !state.itemsLoaded) {
            const result = await ensureItemsLoaded();
            if (result === 'error' || result === 'empty') {
                return;
            }
        }

        chatBubbles.classList.toggle('active');
    });

    chatBubbles.addEventListener('click', (event) => {
        const closeButton = event.target.closest('.bubble-modal-close');
        if (closeButton) {
            event.preventDefault();
            closeChatModal();
            return;
        }

        const item = event.target.closest('.chat-item');
        if (!item) {
            return;
        }

        const target = item.getAttribute('data-bubble-modal');
        if (target) {
            event.preventDefault();
            openChatModal(target);
        }
    });

    document.addEventListener('click', (event) => {
        if (!chatBubbles.contains(event.target)) {
            closeChatBubble();
            closeChatModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closeChatBubble();
            closeChatModal();
        }
    });

    triggerPrefetch();
};

const init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeChatBubbles);
        return;
    }

    initializeChatBubbles();
};

init();

window.CWPChatBubbles = {
    init: initializeChatBubbles
};
