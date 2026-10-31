window.notificationMenu = () => ({
    open: false,
    unreadCount: 0,
    items: [],
    loading: false,
    async init() {
        await this.refresh();
        const userId = document.querySelector('meta[name="user-id"]')?.content;
        if (window.Echo && userId) {
            window.Echo.private(`user.${userId}`).notification((notification) => {
                this.unreadCount += 1;
                this.items = [{
                    id: notification.id,
                    title: notification.title || 'Notification',
                    message: notification.message || '',
                    url: notification.url || null,
                    type: notification.type || 'procurement',
                    created_at: notification.created_at,
                    read_at: null,
                }, ...this.items].slice(0, 15);
            });
        }
    },
    async refresh() {
        this.loading = true;
        try {
            const response = await fetch('/notifications', { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) return;
            const data = await response.json();
            this.unreadCount = data.unread_count || 0;
            this.items = data.notifications || [];
        } catch (error) {
            console.warn('Could not load notifications.', error);
        } finally {
            this.loading = false;
        }
    },
    async markRead(id) {
        const item = this.items.find((notification) => notification.id === id);
        if (!item || item.read_at) return;
        const response = await fetch(`/notifications/${encodeURIComponent(id)}/read`, {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (!response.ok) return;
        item.read_at = new Date().toISOString();
        this.unreadCount = Math.max(0, this.unreadCount - 1);
    },
    async openNotification(item) {
        await this.markRead(item.id);
        if (item.url) window.location.href = item.url;
    },
    async markAllRead() {
        const response = await fetch('/notifications/read-all', {
            method: 'PATCH',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            },
        });
        if (!response.ok) return;
        this.items.forEach((item) => { item.read_at ||= new Date().toISOString(); });
        this.unreadCount = 0;
    },
});
