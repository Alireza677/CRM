import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const notifAudio = new Audio('/sounds/notification.mp3');
notifAudio.preload = 'auto';
let audioUnlocked = false;
let lastPlayAt = 0;
let notificationAssetSettings = window.__notificationAssetSettings || {};
let notificationMuteAll = !!window.__notificationMuteAll;
let notificationDefaultSound = window.__notificationDefaultSound || '/sounds/notification.mp3';

function unlockAudioOnce() {
    if (audioUnlocked) return;
    try {
        notifAudio.muted = true;
        const playPromise = notifAudio.play();
        if (playPromise && typeof playPromise.then === 'function') {
            playPromise.then(() => {
                notifAudio.pause();
                notifAudio.currentTime = 0;
                notifAudio.muted = false;
                audioUnlocked = true;
            }).catch((err) => {
                console.warn('Notification audio unlock failed', err);
            });
        } else {
            notifAudio.pause();
            notifAudio.currentTime = 0;
            notifAudio.muted = false;
            audioUnlocked = true;
        }
    } catch (err) {
        console.warn('Notification audio unlock failed', err);
    }
}

['click', 'keydown', 'touchstart'].forEach((evt) => {
    window.addEventListener(evt, unlockAudioOnce, { once: true });
});

function getMetaContent(name) {
    return document.querySelector(`meta[name="${name}"]`)?.getAttribute('content') || '';
}

function getNotificationSetting(notification) {
    if (!notification) return null;
    const moduleKey = notification.module || '';
    const eventKey = notification.event || '';
    if (!moduleKey || !eventKey) return null;
    return notificationAssetSettings[`${moduleKey}.${eventKey}`] || null;
}

function getNotificationIconUrl(notification) {
    const setting = getNotificationSetting(notification);
    return setting?.icon_url || null;
}

function getNotificationSound(notification) {
    const setting = getNotificationSetting(notification);
    if (setting && setting.sound_enabled === false) {
        return { enabled: false, url: null };
    }
    return {
        enabled: true,
        url: setting?.sound_url || notificationDefaultSound,
    };
}

function initNotificationStream() {
    const userId = getMetaContent('user-id');
    if (!userId) return;

    const streamUrl = getMetaContent('notifications-stream-url');
    const unreadCountUrl = getMetaContent('notifications-unread-count-url');
    const feedUrl = getMetaContent('notifications-feed-url');
    const assetSettingsUrl = getMetaContent('notifications-asset-settings-url');

    const toastContainer = document.getElementById('notification-toast-container');
    const badgeEl = document.getElementById('notification-unread-badge');
    const listContainer = document.getElementById('notification-list');
    const emptyState = document.getElementById('notification-empty-state');
    const notificationsTableBody = document.getElementById('notifications-table-body');
    const notificationsTableEmpty = document.getElementById('notifications-table-empty');

    if (!streamUrl || !toastContainer) {
        return;
    }

    const SEEN_KEY = 'crm_seen_notification_ids';
    let seenIds;
    try {
        const raw = window.localStorage.getItem(SEEN_KEY);
        const arr = raw ? JSON.parse(raw) : [];
        seenIds = new Set(Array.isArray(arr) ? arr : []);
    } catch (e) {
        seenIds = new Set();
    }

    function persistSeenIds() {
        try {
            window.localStorage.setItem(SEEN_KEY, JSON.stringify(Array.from(seenIds)));
        } catch (e) {
            // ignore storage errors
        }
    }

    function playNotificationSound(notification) {
        if (notificationMuteAll) return;
        if (document.visibilityState && document.visibilityState !== 'visible') return;
        const now = Date.now();
        if (now - lastPlayAt < 800) return;
        lastPlayAt = now;

        const sound = getNotificationSound(notification);
        if (!sound.enabled) return;

        try {
            if (!audioUnlocked) {
                console.warn('Notification audio not unlocked yet');
            }
            const targetUrl = sound.url || notificationDefaultSound;
            if (notifAudio.src !== targetUrl) {
                notifAudio.src = targetUrl;
            }
            if (!notifAudio.paused) {
                notifAudio.pause();
            }
            notifAudio.currentTime = 0;
            const playPromise = notifAudio.play();
            if (playPromise && typeof playPromise.then === 'function') {
                playPromise.catch((err) => {
                    console.warn('Notification audio play failed', err);
                });
            }
        } catch (err) {
            console.warn('Notification audio play failed', err);
        }
    }

    function setUnreadCount(count) {
        if (!badgeEl) return;
        const value = Number(count) || 0;
        badgeEl.dataset.count = String(value);
        badgeEl.textContent = String(value);
        badgeEl.classList.toggle('hidden', value === 0);
    }

    function getUnreadCount() {
        if (!badgeEl) return 0;
        return Number(badgeEl.dataset.count || badgeEl.textContent || 0) || 0;
    }

    function formatTime(createdAt) {
        if (!createdAt) return '';
        try {
            const d = new Date(createdAt);
            if (!isNaN(d.getTime())) {
                return d.toLocaleString('fa-IR');
            }
        } catch (e) {
            // ignore
        }
        return '';
    }

    function buildDropdownItem(notification) {
        const wrapper = document.createElement('div');
        wrapper.className = 'px-4 py-2 text-sm text-gray-800 hover:bg-gray-50 border-b';

        const link = document.createElement('a');
        link.className = 'block';
        link.href = notification.url || '#';

        const title = document.createElement('div');
        title.className = 'font-medium';
        title.textContent = notification.title || notification.message || 'اعلان جدیدی دارید';
        link.appendChild(title);

        if (notification.body) {
            const body = document.createElement('div');
            body.className = 'text-xs text-gray-600 mt-0.5 overflow-hidden text-ellipsis whitespace-nowrap';
            body.textContent = notification.body;
            link.appendChild(body);
        }

        const time = document.createElement('div');
        time.className = 'text-xs text-gray-500 mt-1';
        time.textContent = formatTime(notification.created_at) || '';

        wrapper.appendChild(link);
        if (time.textContent) {
            wrapper.appendChild(time);
        }

        return wrapper;
    }

    function prependDropdown(notification) {
        if (!listContainer) return;
        if (emptyState && emptyState.parentNode) {
            emptyState.parentNode.removeChild(emptyState);
        }
        const item = buildDropdownItem(notification);
        listContainer.insertBefore(item, listContainer.firstChild);

        const items = listContainer.querySelectorAll('div.border-b');
        if (items.length > 10) {
            items[items.length - 1].remove();
        }
    }

    function prependTable(notification) {
        if (!notificationsTableBody) return;
        if (notificationsTableEmpty && notificationsTableEmpty.parentNode) {
            notificationsTableEmpty.parentNode.removeChild(notificationsTableEmpty);
        }

        const row = document.createElement('tr');
        row.className = 'bg-yellow-50 text-gray-800 font-semibold border-b';

        row.innerHTML = `
            <td class="px-3 py-2 text-center">
                <input type="checkbox" form="bulkForm" name="selected[]" value="${notification.id}" class="rounded">
            </td>
            <td class="px-3 py-2 text-right">
                <a href="${notification.url || '#'}" class="hover:underline">${notification.title || notification.message || 'اعلان جدیدی دارید'}</a>
            </td>
            <td class="px-3 py-2 text-right">-</td>
            <td class="px-3 py-2 text-right">${formatTime(notification.created_at) || ''}</td>
            <td class="px-3 py-2 text-center">
                <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800">خوانده‌نشده</span>
            </td>
        `;

        notificationsTableBody.insertBefore(row, notificationsTableBody.firstChild);
    }

    function showToast(notification) {
        if (!notification || !notification.id || !toastContainer) return;

        const wrapper = document.createElement('div');
        wrapper.className =
            'pointer-events-auto max-w-sm w-80 bg-white border border-blue-200 shadow-lg ' +
            'rounded-xl p-3 flex gap-3 items-start animate-fade-in-down';

        const iconWrapper = document.createElement('div');
        const iconUrl = getNotificationIconUrl(notification);
        if (iconUrl) {
            const img = document.createElement('img');
            img.src = iconUrl;
            img.alt = '';
            img.className = 'h-9 w-9 rounded-full object-cover border border-blue-100 bg-white';
            iconWrapper.appendChild(img);
        } else {
            iconWrapper.innerHTML = `
                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-100">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 1010 10A10.011 10.011 0 0012 2z" />
                    </svg>
                </span>
            `;
        }

        const content = document.createElement('div');
        content.className = 'flex-1';

        const headerRow = document.createElement('div');
        headerRow.className = 'flex items-start justify-between gap-2';

        const titleEl = document.createElement('div');
        titleEl.className = 'text-sm font-semibold text-gray-900 leading-snug';

        const messageText = notification.title || notification.message || 'اعلان جدیدی دارید';

        if (notification.url) {
            const link = document.createElement('a');
            link.href = notification.url;
            link.textContent = messageText;
            link.className = 'hover:text-blue-600 hover:underline';
            titleEl.appendChild(link);
        } else {
            titleEl.textContent = messageText;
        }

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'text-gray-400 hover:text-gray-600 text-lg leading-none flex-shrink-0';
        closeBtn.innerHTML = '&times;';
        closeBtn.addEventListener('click', () => {
            if (wrapper.parentNode) {
                wrapper.parentNode.removeChild(wrapper);
            }
        });

        headerRow.appendChild(titleEl);
        headerRow.appendChild(closeBtn);

        const metaRow = document.createElement('div');
        metaRow.className = 'mt-1 flex items-center justify-between text-[11px] text-gray-400';

        const timeEl = document.createElement('span');
        timeEl.textContent = formatTime(notification.created_at) || '';
        if (timeEl.textContent) {
            metaRow.appendChild(timeEl);
        }

        if (notification.url) {
            const openBtn = document.createElement('button');
            openBtn.type = 'button';
            openBtn.textContent = 'مشاهده';
            openBtn.className = 'text-blue-600 hover:text-blue-700 font-semibold';
            openBtn.addEventListener('click', () => {
                window.location.href = notification.url;
            });
            metaRow.appendChild(openBtn);
        }

        content.appendChild(headerRow);
        if (metaRow.childNodes.length) {
            content.appendChild(metaRow);
        }

        wrapper.appendChild(iconWrapper);
        wrapper.appendChild(content);

        toastContainer.appendChild(wrapper);

        setTimeout(() => {
            if (wrapper.parentNode) {
                wrapper.parentNode.removeChild(wrapper);
            }
        }, 7000);
    }

    function handleIncoming(notification) {
        if (!notification || !notification.id) return;
        if (seenIds.has(notification.id)) return;
        seenIds.add(notification.id);
        persistSeenIds();
        showToast(notification);
        playNotificationSound(notification);
        prependDropdown(notification);
        prependTable(notification);
        if (!notification.is_read) {
            setUnreadCount(getUnreadCount() + 1);
        }
    }

    function handleNotifications(list) {
        if (!Array.isArray(list) || !list.length) {
            return;
        }

        const ordered = list.slice().reverse();

        let hasNew = false;

        ordered.forEach(item => {
            if (!item || !item.id) return;
            if (seenIds.has(item.id)) {
                return;
            }
            seenIds.add(item.id);
            hasNew = true;
            showToast(item);
        });

        if (hasNew) {
            persistSeenIds();
            const last = ordered[0] || null;
            playNotificationSound(last);
        }
    }

    function fetchLatest() {
        if (!feedUrl) return;
        fetch(feedUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Request failed: ' + response.status);
                }
                const contentType = response.headers.get('Content-Type') || '';
                if (contentType.indexOf('application/json') === -1) {
                    throw new Error('Unexpected content type');
                }
                return response.json();
            })
            .then(payload => {
                if (!payload || typeof payload !== 'object') return;
                handleNotifications(payload.data || []);
            })
            .catch(() => {
                // ignore
            });
    }

    function refreshAssetSettings() {
        if (!assetSettingsUrl) return;
        fetch(assetSettingsUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            credentials: 'same-origin',
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Request failed: ' + response.status);
                }
                return response.json();
            })
            .then(payload => {
                if (!payload || typeof payload !== 'object') return;
                notificationAssetSettings = payload.settings || notificationAssetSettings;
                notificationMuteAll = !!payload.mute_all;
                notificationDefaultSound = payload.default_sound_url || notificationDefaultSound;
            })
            .catch(() => {
                // ignore refresh errors
            });
    }

    let fallbackTimer = null;
    function startFallbackPolling() {
        if (fallbackTimer || !unreadCountUrl) return;
        const poll = () => {
            fetch(unreadCountUrl, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Request failed: ' + response.status);
                    }
                    return response.json();
                })
                .then(payload => {
                    setUnreadCount(payload?.count || 0);
                })
                .catch(() => {
                    // ignore polling errors
                });
        };
        poll();
        fallbackTimer = setInterval(poll, 20000);
    }

    let source = null;
    let retries = 0;
    function connectSse() {
        if (!window.EventSource) {
            startFallbackPolling();
            return;
        }

        const targetUrl = streamUrl;
        if (!targetUrl) {
            startFallbackPolling();
            return;
        }

        if (source) {
            source.close();
        }

        source = new EventSource(`${targetUrl}?since=${encodeURIComponent(new Date().toISOString())}`, { withCredentials: true });

        source.addEventListener('notification', event => {
            if (!event?.data) return;
            try {
                const payload = JSON.parse(event.data);
                handleIncoming(payload);
                retries = 0;
            } catch (e) {
                // ignore parse errors
            }
        });

        source.addEventListener('ping', () => {
            retries = 0;
        });

        source.onerror = () => {
            source.close();
            retries += 1;
            const delay = Math.min(30000, 1000 * (2 ** Math.min(retries, 5)));
            if (retries >= 3) {
                startFallbackPolling();
            }
            setTimeout(connectSse, delay);
        };
    }

    window.addEventListener('beforeunload', () => {
        if (source) {
            source.close();
        }
    });

    fetchLatest();
    refreshAssetSettings();
    connectSse();
}

document.addEventListener('DOMContentLoaded', initNotificationStream);
