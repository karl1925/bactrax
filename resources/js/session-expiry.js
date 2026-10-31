function installSessionExpiryHandling() {
    const modal = document.getElementById('session-expired-modal');
    if (!modal) return;

    const loginLink = document.getElementById('session-expired-login');
    const loginUrl = modal.dataset.loginUrl;
    const timeoutMs = Number(modal.dataset.timeoutSeconds) * 1000;
    const activityKey = `bactrax:last-activity:${modal.dataset.sessionKey}`;
    let expired = false;
    let timerId;
    let lastActivity = null;
    let storageUnavailable = false;

    const reportStorageUnavailable = error => {
        if (storageUnavailable) return;
        storageUnavailable = true;
        console.error('Cross-tab session expiry tracking is unavailable; this tab will continue tracking activity locally.', error);
    };

    const showExpiredSession = () => {
        if (expired) return;
        expired = true;
        window.clearTimeout(timerId);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loginLink?.focus();
    };

    const isExpiredResponse = response =>
        response.status === 401
        || response.status === 419
        || (response.redirected && new URL(response.url, window.location.href).pathname === new URL(loginUrl, window.location.href).pathname);
    const readLastActivity = () => {
        try {
            const rawValue = window.localStorage.getItem(activityKey);
            if (rawValue === null) return null;
            const stored = Number(rawValue);
            return Number.isFinite(stored) && stored > 0 && stored <= Date.now() ? stored : null;
        } catch (error) {
            reportStorageUnavailable(error);
            return lastActivity ?? Date.now();
        }
    };
    const scheduleExpiryCheck = () => {
        window.clearTimeout(timerId);
        const remaining = timeoutMs - (Date.now() - (readLastActivity() ?? lastActivity ?? Date.now()));
        if (remaining <= 0) {
            showExpiredSession();
            return;
        }
        timerId = window.setTimeout(scheduleExpiryCheck, remaining);
    };
    const recordActivity = event => {
        if (expired) return;
        const previousActivity = readLastActivity() ?? lastActivity;
        if (previousActivity && Date.now() - previousActivity >= timeoutMs) {
            showExpiredSession();
            event?.preventDefault();
            event?.stopImmediatePropagation();
            return;
        }
        lastActivity = Date.now();
        try {
            window.localStorage.setItem(activityKey, String(lastActivity));
        } catch (error) {
            reportStorageUnavailable(error);
        }
        scheduleExpiryCheck();
    };

    const originalFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
        const response = await originalFetch(...args);
        if (isExpiredResponse(response)) showExpiredSession();
        return response;
    };

    window.axios?.interceptors.response.use(
        response => {
            if (response.status === 419) showExpiredSession();
            return response;
        },
        error => {
            if ([401, 419].includes(error.response?.status)) showExpiredSession();
            return Promise.reject(error);
        },
    );

    if (!Number.isFinite(timeoutMs) || timeoutMs <= 0) {
        console.error('The configured session lifetime is invalid; inactivity-based expiry detection is unavailable.');
        return;
    }

    const storedActivity = readLastActivity();
    if (storedActivity === null) recordActivity();
    else {
        lastActivity = storedActivity;
        scheduleExpiryCheck();
    }
    ['pointerdown', 'keydown', 'touchstart', 'input', 'scroll'].forEach(eventName => {
        document.addEventListener(eventName, recordActivity, { capture: true, passive: eventName !== 'keydown' && eventName !== 'pointerdown' });
    });
    window.addEventListener('storage', event => {
        if (event.key === activityKey && Number(event.newValue) > lastActivity) {
            lastActivity = Number(event.newValue);
            scheduleExpiryCheck();
        }
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) scheduleExpiryCheck();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installSessionExpiryHandling, { once: true });
} else {
    installSessionExpiryHandling();
}
