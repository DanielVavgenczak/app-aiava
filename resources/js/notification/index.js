Alpine.data('notifications', (user) => ({
        user: user,
        open: false,
        notifications: [],
        init() {
            if (!window.Echo) {
                return;
            }

            console.log('inicialiando aqui as notificações', this.user);
            window.Echo.private(`chat-teacher-student.${this.user.id}`)
                .notification((notification) => {
                    console.log('Notificação', notification)
                    // if (!route().current('chat.user', { user: notification.user.id })) {
                    //     this.notifications.push(notification)
                    // }
                })
        }
    }))