// Métodos compartilhados para os campos de nota.
// Regra atual: nota inteira de 0 a 100 (sem vírgula/decimal).
// Use espalhando em um x-data: { note: $wire.entangle('prop').live, ...window.moneyMask }
// O limite máximo pode ser ajustado via a chave `limit`.
window.moneyMask = {
    mensagem: '',
    showMessage: false,
    limit: 100,

    init() {
        // Nem todo x-data que usa a máscara declara "note" (ex.: o campo de
        // correção usa wire:model + listener de captura). Sem a propriedade o
        // $watch lançaria ReferenceError e quebraria a inicialização Alpine.
        if (! ('note' in this)) {
            return;
        }

        this.$watch('note', (value) => {
            const clamped = this.clampToLimit(value);
            if (clamped !== value) {
                this.note = clamped;
            }
        });
    },

    /**
     * Retorna o valor sem reformatar; apenas substitui por `${limit}`
     * quando o valor numérico excede o limite (valores externos/forjados).
     */
    clampToLimit(value) {
        if (value === null || value === undefined || value === '') {
            return value;
        }

        const normalized = String(value).trim();
        if (normalized === '') {
            return value;
        }

        const number = parseFloat(normalized.replace(',', '.'));

        if (Number.isNaN(number) || number <= this.limit) {
            return value;
        }

        return `${this.limit}`;
    },

    mask(event) {
        const target = event.target;
        // Aceita apenas dígitos: a nota é inteira (sem vírgula).
        let value = target.value.replace(/\D/g, '');

        // Remove zeros à esquerda, mantendo um "0" quando há dígitos.
        value = value.replace(/^0+(?=\d)/, '');

        if (value === '') {
            // Campo vazio permanece vazio: não inventa "0", para que a regra
            // "informe a nota antes de criar" continue válida no servidor.
            this.setNote('', target);
            this.mensagem = '';
            return;
        }

        const numeroInteiro = parseInt(value, 10);

        if (numeroInteiro > this.limit) {
            this.setNote(`${this.limit}`, target);
            this.showWarningMessage();
            return;
        }

        this.setNote(`${numeroInteiro}`, target);
        this.mensagem = '';
    },

    /**
     * Atualiza o estado (note) e o próprio input, garantindo que o valor
     * mascarado seja o lido por wire:model/x-model no mesmo evento.
     */
    setNote(value, target = null) {
        this.note = value;

        if (target) {
            target.value = value;
        }
    },

    showWarningMessage() {
        this.mensagem = `Valor maximo permitido: ${this.limit}`;
        this.showMessage = true;
        setTimeout(() => {
            this.showMessage = false;
            this.mensagem = '';
        }, 4000);
    },

    handleBackspaceInput() {
        let value = String(this.note ?? '').replace(/\D/g, '');

        if (!value || value.length <= 1) {
            this.note = '';
            return;
        }

        // Remove o último dígito da direita.
        value = value.slice(0, -1);
        value = value.replace(/^0+(?=\d)/, '') || '0';

        const numeroInteiro = parseInt(value, 10);

        if (numeroInteiro > this.limit) {
            this.note = `${this.limit}`;
            return;
        }

        this.note = `${numeroInteiro}`;
    }
};
