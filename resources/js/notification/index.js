
    Alpine.data('notifications', (user) => ({
         user: user,

        open: false,
        notifications: [],
        init() {
            console.log('inicialiando aqui as notificações', this.user);
            Echo.private(`chat-teacher-student.${this.user.id}`)
                .notification((notification) => {
                    console.log('Notificação', notification)
                    // if (!route().current('chat.user', { user: notification.user.id })) {
                    //     this.notifications.push(notification)
                    // }
                })
        }
    }))