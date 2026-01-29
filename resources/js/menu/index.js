document.addEventListener("alpine:init", () => {
    Alpine.store('expandedSidebar', {
        expanded: false,

        toggle() {
            this.expanded = !this.expanded;
            console.log('clicando here')
        },

        // Classes condicionais para Tailwind
        sidebarClasses() {
            return this.expanded ? 'w-64' : 'w-20';
        },

        textClasses() {
            return this.expanded ? 'opacity-100' : 'opacity-0 absolute';
        },

        iconPosition() {
            return this.expanded ? 'justify-end' : 'justify-center';
        }
    });
});
