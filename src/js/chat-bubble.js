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
const frontendI18n = (window.cwpChatBubbles && window.cwpChatBubbles.i18n) ? window.cwpChatBubbles.i18n : {};

const t = (key, fallback) => (
    Object.prototype.hasOwnProperty.call(frontendI18n, key) ? frontendI18n[key] : fallback
);

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

const normalizeTrackingValue = (value, fallback = '') => {
    if (value === undefined || value === null) {
        return fallback;
    }
    return String(value).trim();
};

const pushTrackingEvent = (chatBubbles, eventName, params = {}) => {
    const dataset = chatBubbles ? chatBubbles.dataset : {};
    const payload = {
        event: eventName,
        component: 'cwp_chat_bubbles',
        layout: normalizeTrackingValue(dataset.layout, 'toggle'),
        mode: normalizeTrackingValue(dataset.lazy, '0') === '1' ? 'lazy' : 'static',
        position: normalizeTrackingValue(dataset.position, 'unknown'),
        ...params
    };

    if (Array.isArray(window.dataLayer)) {
        window.dataLayer.push(payload);
    }

    document.dispatchEvent(new CustomEvent('cwpChatBubbles:track', {
        detail: payload
    }));
};

const getItemTrackingPayload = (item) => {
    if (!item) {
        return {};
    }

    return {
        platform: normalizeTrackingValue(item.dataset.platform, 'unknown'),
        item_id: normalizeTrackingValue(item.dataset.itemId, ''),
        item_label: normalizeTrackingValue(item.dataset.itemLabel, ''),
        has_qr: normalizeTrackingValue(item.dataset.hasQr, '0') === '1'
    };
};

const getModalTrackingPayload = (modal) => {
    if (!modal) {
        return {};
    }

    return {
        platform: normalizeTrackingValue(modal.dataset.platform, 'unknown'),
        item_id: normalizeTrackingValue(modal.dataset.itemId, ''),
        item_label: normalizeTrackingValue(modal.dataset.itemLabel, '')
    };
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
    const defaultLayout = (payload.settings && payload.settings.default_layout) ? payload.settings.default_layout : 'toggle';
    const cancelIcon = (chatBubbles.querySelector('.chat-icon-close') || {}).src || '';
    chatBubbles.dataset.layout = defaultLayout;

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
        itemLink.dataset.platform = item.platform || '';
        itemLink.dataset.itemId = String(item.id || '');
        itemLink.dataset.itemLabel = item.label || '';
        itemLink.dataset.hasQr = item.has_qr ? '1' : '0';
        itemLink.dataset.track = 'chat-item';

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
            modal.dataset.platform = item.platform || '';
            modal.dataset.itemId = String(item.id || '');
            modal.dataset.itemLabel = item.label || '';
            modal.dataset.track = 'chat-modal';

            const closeButton = document.createElement('button');
            closeButton.className = 'bubble-modal-close';
            closeButton.setAttribute('aria-label', t('closeModalAria', 'Close modal'));
            closeButton.dataset.track = 'chat-modal-close';
            if (cancelIcon) {
                const closeImage = document.createElement('img');
                closeImage.src = cancelIcon;
                closeImage.alt = t('closeIconAlt', 'Close');
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
            qrImage.alt = `${item.label || t('qrCodeAltFallback', 'QR')} QR Code`;
            qrImage.loading = 'lazy';
            qrWrapper.append(heading, qrImage);

            body.appendChild(qrWrapper);

            if (item.platform_url && item.platform_url !== '#') {
                const action = document.createElement('a');
                action.className = 'btn chat-modal-cta';
                action.href = item.platform_url;
                action.target = '_blank';
                action.rel = 'noopener noreferrer';
                action.dataset.platform = item.platform || '';
                action.dataset.itemId = String(item.id || '');
                action.dataset.itemLabel = item.label || '';
                action.dataset.track = 'chat-modal-cta';
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
                label.textContent = `${t('openLabelPrefix', 'Open')} ${item.label || ''}`.trim();

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
    const layoutMode = chatBubbles.dataset.layout || 'toggle';
    const isExpandedLayout = layoutMode === 'expanded';
    if (!isExpandedLayout && !chatToggle) {
        return;
    }

    if (chatBubbles.dataset.cwpInitialized === '1') {
        return;
    }
    chatBubbles.dataset.cwpInitialized = '1';

    const isLazy = chatBubbles.dataset.lazy === '1';
    const endpoint = chatBubbles.dataset.endpoint || '';
    const prefetchEnabled = chatBubbles.dataset.prefetch === '1';

    const state = {
        mode: isLazy ? 'lazy' : 'static',
        loadState: isLazy ? 'idle' : 'loaded',
        loadPromise: null,
        itemsLoaded: !isLazy,
        isExpandedLayout
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
        let hadOpenModal = false;
        chatBubbles.querySelectorAll('.bubble-modal').forEach((modal) => {
            if (modal.classList.contains('active')) {
                hadOpenModal = true;
            }
            modal.classList.remove('active');
            modal.setAttribute('aria-hidden', 'true');
            modal.tabIndex = -1;
        });
        return hadOpenModal;
    };

    const openChatModal = (targetId) => {
        chatBubbles.querySelectorAll('.bubble-modal').forEach((modal) => {
            if (modal.id === targetId) {
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');
                modal.tabIndex = 0;
            } else {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                modal.tabIndex = -1;
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
            const message = error && typeof error.message === 'string' ? error.message : '';
            const statusMatch = message.match(/^HTTP\s+(\d{3})$/);
            const statusCode = statusMatch ? parseInt(statusMatch[1], 10) : null;
            const shouldRetry = (statusCode === null) || statusCode === 429 || (statusCode >= 500 && statusCode < 600);

            if (attempt === 0 && shouldRetry) {
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

    if (chatToggle) {
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
            const isOpen = chatBubbles.classList.contains('active');
            pushTrackingEvent(chatBubbles, isOpen ? 'chat_bubble_open' : 'chat_bubble_close', {
                source: 'toggle_button'
            });
        });
    }

    chatBubbles.addEventListener('click', (event) => {
        const cta = event.target.closest('.chat-modal-cta');
        if (cta) {
            pushTrackingEvent(chatBubbles, 'chat_cta_click', {
                source: 'modal_cta',
                ...getItemTrackingPayload(cta)
            });
            return;
        }

        const closeButton = event.target.closest('.bubble-modal-close');
        if (closeButton) {
            event.preventDefault();
            const modal = closeButton.closest('.bubble-modal');
            closeChatModal();
            pushTrackingEvent(chatBubbles, 'chat_modal_close', {
                source: 'modal_close_button',
                ...getModalTrackingPayload(modal)
            });
            return;
        }

        const item = event.target.closest('.chat-item');
        if (!item) {
            return;
        }

        pushTrackingEvent(chatBubbles, 'chat_item_click', {
            source: 'chat_item',
            ...getItemTrackingPayload(item)
        });

        const target = item.getAttribute('data-bubble-modal');
        if (target) {
            event.preventDefault();
            openChatModal(target);
            const modal = chatBubbles.querySelector(`#${target}`);
            pushTrackingEvent(chatBubbles, 'chat_modal_open', {
                source: 'chat_item',
                ...getModalTrackingPayload(modal)
            });
        }
    });

    document.addEventListener('click', (event) => {
        if (!chatBubbles.contains(event.target)) {
            if (!state.isExpandedLayout) {
                const wasOpen = chatBubbles.classList.contains('active');
                closeChatBubble();
                if (wasOpen) {
                    pushTrackingEvent(chatBubbles, 'chat_bubble_close', {
                        source: 'outside_click'
                    });
                }
            }
            const hadOpenModal = closeChatModal();
            if (hadOpenModal) {
                pushTrackingEvent(chatBubbles, 'chat_modal_close', {
                    source: 'outside_click'
                });
            }
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (!state.isExpandedLayout) {
                const wasOpen = chatBubbles.classList.contains('active');
                closeChatBubble();
                if (wasOpen) {
                    pushTrackingEvent(chatBubbles, 'chat_bubble_close', {
                        source: 'escape_key'
                    });
                }
            }
            const hadOpenModal = closeChatModal();
            if (hadOpenModal) {
                pushTrackingEvent(chatBubbles, 'chat_modal_close', {
                    source: 'escape_key'
                });
            }
        }
    });

    if (state.isExpandedLayout) {
        if (state.mode === 'lazy' && !state.itemsLoaded) {
            void ensureItemsLoaded();
        }
        chatBubbles.classList.add('is-expanded-default');
    }

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
