document.addEventListener('alpine:init', () => {
    Alpine.data('sidebar', () => ({
        currentPath: window.location.pathname,
        init() {
            // Atualiza currentPath após navegação pelo histórico do navegador
            window.addEventListener('popstate', () => {
                this.currentPath = window.location.pathname;
            });

            // Atualiza currentPath após navegação com Livewire
            document.addEventListener('livewire:navigated', () => {
                this.currentPath = window.location.pathname;
            });
        },
        /** Function to verify link actived */
        isActive(routePath) {
            // If links empty dont active
            if (routePath === '#') return false;

            // Remove barras finais para consistência
            const current = this.currentPath.replace(/\/$/, '');
            const route = routePath.replace(/\/$/, '');

            // Caso 1: Rota exata
            if (current === route) return true;

            // Caso 2: Subrotas (ex: /app/teacher/discipline/123)
            if (route.startsWith('/teacher/')) {
                const routeBase = route.split('/teacher/')[1].split('/')[0];
                const currentBase = current.split('/teacher/')[1]?.split('/')[0];
                if (routeBase && currentBase === routeBase) {
                    return true;
                }
            }else if (route.startsWith('/student/')) {
                const routeBase = route.split('/student/')[1].split('/')[0];
                const currentBase = current.split('/student/')[1]?.split('/')[0];
                if (routeBase && currentBase === routeBase) {
                    return true;
                }
            }
            return false;
        }
    }));
});
