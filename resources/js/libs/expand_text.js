document.addEventListener('alpine:init', () => {
    Alpine.data('expandeText', (text) => ({
        showFull: false,
        textContent: text,
        max:150,

         truncatedText() {
            if (this.textContent.length <= this.max || this.showFull) {
                return this.textContent;
            }
            return this.textContent.substring(0, this.max) + '...';
        },

        shouldShowToggle() {
            return this.textContent.length > this.max;
        },

        toggleText() {
            this.showFull = !this.showFull;

        }
    }));
})
