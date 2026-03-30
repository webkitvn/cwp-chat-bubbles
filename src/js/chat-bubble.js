/**
 * CWP Chat Bubbles JavaScript
 *
 * Handles chat bubble interactions, engagement triggers, and modal functionality.
 *
 * @package CWP_Chat_Bubbles
 * @since 1.0.0
 */

const initChatBubbles = () => {
    const chatBubbles = document.getElementById('chat-bubbles');
    const runtimeConfig = typeof cwpChatBubbles !== 'undefined' ? cwpChatBubbles : {};
    const platformRegistry = runtimeConfig.platforms || {};
    const settings = runtimeConfig.settings || {};
    const behaviorSettings = settings.behavior || {
        default_state: 'closed',
        display_delay: 0,
        scroll_trigger_percent: 0,
        dismiss_for_session: false
    };
    const analyticsSettings = settings.analytics || {
        enabled: false,
        provider: 'none',
        event_prefix: 'cwp_chat_bubbles'
    };

    if (!chatBubbles) {
        return;
    }

    const chatToggle = chatBubbles.querySelector('.chat-btn-toggle');
    const chatPanel = chatBubbles.querySelector('.item-group');
    const chatItems = chatBubbles.querySelectorAll('.chat-item');
    const chatModals = chatBubbles.querySelectorAll('.bubble-modal');
    const modalActionLinks = chatBubbles.querySelectorAll('.bubble-modal [data-bubble-item-id]');
    let activeModalTrigger = null;
    let autoOpened = false;
    let delaySatisfied = Number(behaviorSettings.display_delay || 0) <= 0;
    let scrollSatisfied = Number(behaviorSettings.scroll_trigger_percent || 0) <= 0;

    if (!chatToggle || !chatPanel) {
        return;
    }

    const dismissForSession = Boolean(behaviorSettings.dismiss_for_session);
    const dismissSessionKey = 'cwp_chat_bubbles_dismissed';

    const emitCustomAnalyticsEvent = (name, payload) => {
        if (typeof window.CustomEvent !== 'function') {
            return;
        }

        window.dispatchEvent(
            new CustomEvent('cwp-chat-bubbles:event', {
                detail: {
                    name,
                    payload
                }
            })
        );
        window.dispatchEvent(
            new CustomEvent(`cwp-chat-bubbles:${name}`, {
                detail: payload
            })
        );
    };

    const emitExternalAnalyticsEvent = (name, payload) => {
        if (!analyticsSettings.enabled) {
            return;
        }

        const provider = analyticsSettings.provider || 'none';
        if (!['ga4', 'gtm'].includes(provider)) {
            return;
        }

        const eventPrefix = analyticsSettings.event_prefix || 'cwp_chat_bubbles';
        const eventName = `${eventPrefix}_${name}`;
        const eventPayload = {
            event_category: 'chat_bubbles',
            component: 'cwp_chat_bubbles',
            trigger: payload.trigger || '',
            position: payload.position || '',
            item_id: payload.itemId || null,
            item_platform: payload.itemPlatform || '',
            item_label: payload.itemLabel || '',
            target_type: payload.targetType || '',
            interaction_mode: payload.interactionMode || '',
            has_qr: payload.hasQr || false
        };

        if ('ga4' === provider && typeof window.gtag === 'function') {
            window.gtag('event', eventName, eventPayload);
        }

        if ('gtm' === provider && window.dataLayer && typeof window.dataLayer.push === 'function') {
            window.dataLayer.push({
                event: eventName,
                ...eventPayload
            });
        }
    };

    const emitAnalyticsEvent = (name, payload = {}) => {
        const eventPayload = {
            name,
            component: 'cwp_chat_bubbles',
            position: chatBubbles.dataset.position || '',
            ...payload
        };

        emitCustomAnalyticsEvent(name, eventPayload);
        emitExternalAnalyticsEvent(name, eventPayload);
    };

    const getChatItemPayload = (chatItem, extraPayload = {}) => {
        const itemId = chatItem.dataset.bubbleItemId || '';
        const platformData = itemId ? platformRegistry[itemId] || {} : {};
        const behavior = platformData.behavior || {};

        return {
            itemId: itemId ? Number(itemId) : null,
            itemPlatform: platformData.platform || '',
            itemLabel: platformData.label || '',
            targetType: chatItem.dataset.bubbleTargetType || (chatItem.hasAttribute('data-bubble-modal') ? 'modal' : 'link'),
            interactionMode: chatItem.dataset.bubbleInteractionMode || behavior.interaction_mode || '',
            hasQr: Boolean(platformData.has_qr),
            ...extraPayload
        };
    };

    const readSessionDismissedState = () => {
        if (!dismissForSession || typeof window.sessionStorage === 'undefined') {
            return false;
        }

        try {
            return '1' === window.sessionStorage.getItem(dismissSessionKey);
        } catch (error) {
            return false;
        }
    };

    const persistSessionDismissedState = () => {
        if (!dismissForSession || typeof window.sessionStorage === 'undefined') {
            return;
        }

        try {
            window.sessionStorage.setItem(dismissSessionKey, '1');
        } catch (error) {
            // Ignore storage failures and keep the current page state functional.
        }
    };

    const setWidgetVisibility = (isVisible) => {
        chatBubbles.hidden = !isVisible;
        chatBubbles.dataset.runtimeVisible = isVisible ? 'true' : 'false';
    };

    const setBubbleState = (isOpen) => {
        chatBubbles.classList.toggle('active', isOpen);
        chatToggle.setAttribute('aria-expanded', String(isOpen));
        chatToggle.setAttribute(
            'aria-label',
            isOpen ? chatToggle.dataset.labelClose : chatToggle.dataset.labelOpen
        );
        chatPanel.setAttribute('aria-hidden', String(!isOpen));
    };

    const getActiveModal = () => chatBubbles.querySelector('.bubble-modal.active');

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

    const closeChatBubble = () => {
        setBubbleState(false);
    };

    const dismissWidgetForSession = (trigger) => {
        persistSessionDismissedState();
        closeChatModal();
        closeChatBubble();
        setWidgetVisibility(false);
        emitAnalyticsEvent('dismiss', {
            trigger
        });
    };

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

    const maybeAutoOpenBubble = (trigger) => {
        if (autoOpened || behaviorSettings.default_state !== 'open') {
            return;
        }

        autoOpened = true;
        setBubbleState(true);
        emitAnalyticsEvent('open', {
            trigger
        });
    };

    const applyEngagementState = (trigger) => {
        if (readSessionDismissedState()) {
            setWidgetVisibility(false);
            closeChatBubble();
            return;
        }

        if (!delaySatisfied || !scrollSatisfied) {
            setWidgetVisibility(false);
            return;
        }

        setWidgetVisibility(true);
        maybeAutoOpenBubble(trigger);
    };

    const getScrollProgress = () => {
        const scrollTop = window.scrollY || window.pageYOffset || 0;
        const documentHeight = document.documentElement.scrollHeight - window.innerHeight;

        if (documentHeight <= 0) {
            return 100;
        }

        return Math.min(100, Math.max(0, (scrollTop / documentHeight) * 100));
    };

    const maybeSatisfyScrollTrigger = () => {
        const scrollTriggerPercent = Number(behaviorSettings.scroll_trigger_percent || 0);

        if (scrollTriggerPercent <= 0 || scrollSatisfied) {
            return;
        }

        if (getScrollProgress() >= scrollTriggerPercent) {
            scrollSatisfied = true;
            applyEngagementState('scroll_trigger');
            window.removeEventListener('scroll', maybeSatisfyScrollTrigger);
        }
    };

    setBubbleState(false);
    closeChatModal();
    applyEngagementState('initial');

    if (!delaySatisfied) {
        window.setTimeout(() => {
            delaySatisfied = true;
            applyEngagementState('display_delay');
        }, Number(behaviorSettings.display_delay || 0) * 1000);
    }

    if (!scrollSatisfied) {
        window.addEventListener('scroll', maybeSatisfyScrollTrigger, {
            passive: true
        });
        maybeSatisfyScrollTrigger();
    }

    chatToggle.addEventListener('click', (event) => {
        event.preventDefault();

        if (chatBubbles.classList.contains('active')) {
            if (dismissForSession) {
                dismissWidgetForSession('toggle');
                return;
            }

            closeChatModal();
            closeChatBubble();
            return;
        }

        setWidgetVisibility(true);
        setBubbleState(true);
        emitAnalyticsEvent('open', {
            trigger: 'toggle'
        });
    });

    chatItems.forEach((chatItem) => {
        chatItem.addEventListener('click', (event) => {
            const target = chatItem.getAttribute('data-bubble-modal');
            emitAnalyticsEvent('item_click', getChatItemPayload(chatItem));

            if (target) {
                event.preventDefault();
                openChatModal(target, chatItem);
            }
        });
    });

    modalActionLinks.forEach((modalActionLink) => {
        modalActionLink.addEventListener('click', () => {
            emitAnalyticsEvent(
                'item_click',
                getChatItemPayload(modalActionLink, {
                    trigger: 'modal_cta'
                })
            );
        });
    });

    chatModals.forEach((chatModal) => {
        const chatModalClose = chatModal.querySelector('.bubble-modal-close');
        if (chatModalClose) {
            chatModalClose.addEventListener('click', (event) => {
                event.preventDefault();
                closeChatModal();
            });
        }

        chatModal.addEventListener('click', (event) => {
            if (event.target === chatModal) {
                closeChatModal();
            }
        });
    });

    document.addEventListener('click', (event) => {
        if (chatBubbles.hidden || !chatBubbles.contains(event.target)) {
            if (!chatBubbles.classList.contains('active')) {
                return;
            }

            if (dismissForSession) {
                dismissWidgetForSession('outside_click');
                return;
            }

            closeChatBubble();
            closeChatModal();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (getActiveModal()) {
                closeChatModal();
                return;
            }

            if (!chatBubbles.classList.contains('active')) {
                return;
            }

            if (dismissForSession) {
                dismissWidgetForSession('escape');
                return;
            }

            closeChatBubble();
        }

        trapModalFocus(event);
    });
};

const init = () => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatBubbles);
    } else {
        initChatBubbles();
    }
};

init();

window.CWPChatBubbles = {
    init: initChatBubbles
};
