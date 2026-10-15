// Métodos compartilhados para os campos de nota (máscara monetária pt-BR).
// Use espalhando em um x-data: { note: $wire.entangle('prop').live, ...window.moneyMask }
// O limite máximo (parte inteira) pode ser ajustado via a chave `limit`.
window.moneyMask = {
    mensagem: '',
    showMessage: false,
    limit: 10,

    mask(event) {
        let value = event.target.value.replace(/\D/g, '');

        if (!value) {
            this.note = '0,00';
            this.mensagem = '';
            return;
        }

        while (value.length < 3) {
            value = '0' + value;
        }

        let inteiro = value.slice(0, -2);
        let decimal = value.slice(-2);
        let numeroInteiro = parseInt(inteiro);

        if (numeroInteiro > this.limit) {
            // Only keep first digit of integer part when above limit
            let limited = this.limit.toString();
            this.note = `${limited},${decimal.slice(0, 1)}`;
            this.showWarningMessage();
            return;
        }

        if (numeroInteiro === this.limit) {
            this.note = `${this.limit},0`;
            if (decimal !== '00') {
                this.showWarningMessage();
            } else {
                this.mensagem = '';
            }
            return;
        }

        this.note = `${numeroInteiro},${decimal}`;
        this.mensagem = '';
    },

    showWarningMessage() {
        this.mensagem = `Valor maximo permitido: ${this.limit},0`;
        this.showMessage = true;
        setTimeout(() => {
            this.showMessage = false;
            this.mensagem = '';
        }, 4000);
    },

    handleBackspaceInput() {
        let value = this.note.replace(/\D/g, '');

        if (!value || value === '000') {
            this.note = '0,00';
            return;
        }

        value = value.slice(0, -1);

        while (value.length < 3) {
            value = '0' + value;
        }

        let inteiro = value.slice(0, -2);
        let decimal = value.slice(-2);
        let numeroInteiro = parseInt(inteiro);

        if (numeroInteiro > this.limit) {
            let limited = this.limit.toString();
            this.note = `${limited},${decimal.slice(0, 1)}`;
            return;
        }

        if (numeroInteiro === this.limit) {
            this.note = `${this.limit},0`;
            return;
        }

        this.note = `${numeroInteiro},${decimal}`;
    }
};
