(function () {
    'use strict';

    var STORAGE_KEY = 'asgc_gate_state';
    var gate = document.querySelector('[data-asgc-gate]');

    if (!gate) {
        return;
    }

    var config = {
        gateId: gate.getAttribute('data-gate-id'),
        postId: gate.getAttribute('data-post-id'),
        formId: gate.getAttribute('data-form-id'),
        trigger: gate.getAttribute('data-trigger') || 'entrance',
        delay: parseInt(gate.getAttribute('data-delay') || '0', 10),
        threshold: parseInt(gate.getAttribute('data-threshold') || '0', 10)
    };

    var dialog = gate.querySelector('.asgc-gate__dialog');
    var closeButton = gate.querySelector('[data-asgc-close]');
    var hasDisplayed = false;
    var hasExitIntentTriggered = false;
    var lastMouseY = null;
    var lastMouseMoveAt = 0;
    var previousFocus = null;

    function readState() {
        try {
            return JSON.parse(window.localStorage.getItem(STORAGE_KEY)) || { submitted: {}, triggers: {} };
        } catch (error) {
            return { submitted: {}, triggers: {} };
        }
    }

    function writeState(state) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (error) {
            return;
        }
    }

    function getTriggerKey() {
        return config.gateId + ':post:' + config.postId;
    }

    function markSubmitted() {
        var state = readState();
        state.submitted = state.submitted || {};
        state.submitted[config.gateId] = true;
        writeState(state);
        closeGate();
    }

    function isSubmitted() {
        var state = readState();
        return !!(state.submitted && state.submitted[config.gateId]);
    }

    function recordTriggerAndShouldShow() {
        var state = readState();
        var triggerKey = getTriggerKey();

        state.triggers = state.triggers || {};
        state.triggers[triggerKey] = parseInt(state.triggers[triggerKey] || '0', 10) + 1;
        writeState(state);

        return state.triggers[triggerKey] > config.threshold;
    }

    function openGate() {
        if (hasDisplayed || isSubmitted()) {
            return;
        }

        hasDisplayed = true;
        previousFocus = document.activeElement;
        gate.classList.add('is-open');
        gate.setAttribute('aria-hidden', 'false');
        document.body.classList.add('asgc-gate-open');

        if (dialog) {
            dialog.focus();
        }
    }

    function closeGate() {
        gate.classList.remove('is-open');
        gate.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('asgc-gate-open');

        if (previousFocus && typeof previousFocus.focus === 'function') {
            previousFocus.focus();
        }
    }

    function maybeOpenGate() {
        if (hasDisplayed || isSubmitted()) {
            return;
        }

        if (!recordTriggerAndShouldShow()) {
            return;
        }

        window.setTimeout(openGate, Math.max(0, config.delay) * 1000);
    }

    function bindExitIntent() {
        var topBoundary = 32;

        function triggerExitIntent() {
            if (hasExitIntentTriggered) {
                return;
            }

            hasExitIntentTriggered = true;
            maybeOpenGate();
        }

        function isMousePointer(event) {
            return !event.pointerType || 'mouse' === event.pointerType;
        }

        document.addEventListener('mousemove', function (event) {
            var previousMouseY = lastMouseY;

            lastMouseY = event.clientY;
            lastMouseMoveAt = Date.now();

            if (null === previousMouseY) {
                return;
            }

            if (event.clientY <= topBoundary && previousMouseY > event.clientY) {
                triggerExitIntent();
            }
        }, { passive: true });

        document.addEventListener('mouseout', function (event) {
            if (event.relatedTarget || event.toElement) {
                return;
            }

            if (event.clientY <= topBoundary) {
                triggerExitIntent();
            }
        }, { passive: true });

        document.addEventListener('mouseleave', function (event) {
            if (event.clientY <= topBoundary) {
                triggerExitIntent();
            }
        }, { passive: true });

        if (window.PointerEvent) {
            document.addEventListener('pointerleave', function (event) {
                if (isMousePointer(event) && event.clientY <= topBoundary) {
                    triggerExitIntent();
                }
            }, { passive: true });
        }

        window.addEventListener('blur', function () {
            var pointerWasRecentlyNearTop = lastMouseY !== null && lastMouseY <= topBoundary && Date.now() - lastMouseMoveAt < 750;

            if (pointerWasRecentlyNearTop) {
                triggerExitIntent();
            }
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', closeGate);
    }

    document.addEventListener('keydown', function (event) {
        if ('Escape' === event.key && gate.classList.contains('is-open')) {
            closeGate();
        }
    });

    var existingConfirmationCallback = window.gform_confirmation_loaded;

    window.gform_confirmation_loaded = function (formId) {
        if (typeof existingConfirmationCallback === 'function') {
            existingConfirmationCallback(formId);
        }

        if (String(formId) === String(config.formId)) {
            markSubmitted();
        }
    };

    var observer = new MutationObserver(function () {
        if (gate.querySelector('.gform_confirmation_message')) {
            markSubmitted();
        }
    });

    observer.observe(gate, { childList: true, subtree: true });

    if (isSubmitted()) {
        return;
    }

    if ('exit' === config.trigger) {
        bindExitIntent();
    } else {
        window.setTimeout(maybeOpenGate, 0);
    }
})();
