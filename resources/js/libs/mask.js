document.addEventListener("alpine:init", () => {
    Alpine.data("maskInput", () => ({
            note: $wire.entangle('valueOfActivity'),
            mask(event){
              let x = event.target.value.replace(/\D/g, '');
              if(x.length > 3){
                console.log("Oque vem aqui",x);
                return;
              }
              let test =  parseFloat(x).toFixed(2);
              console.log("Verificando aqui dentreo dela", event.target.value, x, test);
            }
    }));


    // para editar
    Alpine.data("maskInputEdit", () => ({
        rawInput: '',
        formattedValue: '',
        showLimitMessage: false,
        limitValue: 100,

        init() {
            // Inicializa com o valor do Livewire se existir
            if (this.$el.value) {
                const initialValue = this.$el.value.toString();
                this.rawInput = initialValue.replace(/\D/g, '');
                this.formattedValue = this.formatNote(this.rawInput);
                this.$refs.valueInput.value = this.formattedValue;
            } else {
                this.formattedValue = this.formatNote(this.rawInput);
            }
        },

        formatNote(input) {
            const cleaned = input.replace(/\D/g, '');
            if (!cleaned) return '0,0';

            const parsed = parseInt(cleaned, 10);
            if (parsed > 100) return '10,0';

            const valueStr = String(parsed);
            if (valueStr.length === 1) {
                return `0,${valueStr}`;
            }
            const integerPart = valueStr.slice(0, -1);
            const decimalPart = valueStr.slice(-1);
            return `${integerPart},${decimalPart}`;
        },

        handleKeydown(event) {
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End'];
            if (allowedKeys.includes(event.key)) {
                return;
            }

            if (isNaN(event.key)) {
                event.preventDefault();
                return;
            }

            const nextRaw = this.rawInput + event.key;
            const parsedValue = parseInt(nextRaw, 10) || 0;

            if (parsedValue > this.limitValue) {
                event.preventDefault();
                this.showLimitMessage = true;
            } else {
                this.showLimitMessage = false;
            }
        },

        handleInput(event) {
            const raw = event.target.value.replace(/\D/g, '');

            if (raw.length < this.rawInput.length) {
                this.rawInput = raw;
            } else if (event.inputType === 'deleteContentBackward' && raw === '') {
                this.rawInput = '';
            } else {
                this.rawInput = raw;
            }

            this.formattedValue = this.formatNote(this.rawInput);

            // Atualiza o Livewire
            this.$wire.set('editingQuestionValue', this.formattedValue.replace(',', '.'));
        },

        resetOnFocus() {
            if (this.formattedValue === '0,0' || this.formattedValue === '0') {
                this.rawInput = '';
                this.formattedValue = '';
                this.$nextTick(() => this.$refs.valueInput.value = '');
            }
        },

        handleBackspace() {
            if (this.rawInput.length > 0) {
                this.rawInput = this.rawInput.slice(0, -1);
            }
            this.formattedValue = this.formatNote(this.rawInput);
            const parsedValue = parseInt(this.rawInput, 10) || 0;
            this.showLimitMessage = (parsedValue > this.limitValue);

            // Atualiza o Livewire
            this.$wire.set('editingQuestionValue', this.formattedValue.replace(',', '.'));
        }
    }));
});
