import { configDriver,  AddButtonActions } from "../utils";

export async function ContinueUploadBlock(modules, dataId) {
  const steps = [
    {
      element: `#accordion-module-${dataId}`,
      popover: {
        description: `Vamos abrir as nessa unica aula`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 3000));
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#open-accordion-block-${dataId}`, // Seleciona o elemento com base no ID
      popover: {
        description: `Vou clicar e abrir para listar os blocos`,
        side: "bottom",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) => setTimeout(resolve, 3000));
        const btnOpenAccordion = document.querySelector(`#open-accordion-block-${dataId}`);
        await btnOpenAccordion.click();
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#block-open-new-block-${dataId}`,
      popover: {
        description: `Vou clicar aqui para abrir a opção de adicionar um bloco`,
        side: "right",
        align: "center",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        console.log("Terceiro processo executando here");
        await new Promise((resolve) => setTimeout(resolve, 3500));
        await el.click();
        await updateOnedDriverObj.moveNext();
      },
    },
    {
      element: `#browser-file-${dataId}`, // Seleciona o elemento com base no ID
      popover: {
        description: `Clique no icone de video para adicionar um novo bloco`,
        side: "top",
        align: "start",
        showButtons: [""],
      },
      onHighlighted: async (el) => {
        //await new Promise((resolve) => setTimeout(resolve, 3000));
        const videoElement = document.querySelector(`#temp-video-preview-${dataId}`);
        if (!videoElement) {
          console.error("Elemento de vídeo não encontrado");
          return;
        }
        // Verifica se já tem src definido
        if (videoElement.src && videoElement.src !== "") {
          console.log("Vídeo já selecionado - avançando");
          await updateOnedDriverObj.moveNext();
          return;
        }
        // Cria um observador para detectar quando o src for alterado
        const observer = new MutationObserver(async (mutations) => {
          for (const mutation of mutations) {
            if (
              mutation.type ===
              "attributes" &&
              mutation.attributeName ===
              "src"
            ) {
              if (
                videoElement.src &&
                videoElement.src !==
                ""
              ) {
                console.log(
                  "Video selecionado detectado - avançando",
                );
                observer.disconnect(); // Para de observar
                await updateOnedDriverObj.moveNext();
              }
            }
          }
        },
        );

        // Configura o observador
        observer.observe(videoElement, {
          attributes: true, // Observa mudanças em atributos
          attributeFilter: ["src"], // Apenas para o atributo src
        });

        // Timeout de segurança (opcional)
        setTimeout(() => {
          observer.disconnect();
          console.log(
            "Timeout - observador removido",
          );
        }, 30000); // 30 segundos
      },
    },
    {
      element: `#temp-video-preview-${dataId}`, // Seleciona o elemento com base no ID
      popover: {
        description: `Verifique se está ok, clique em 'Próximo' para continuar o upload`,
        side: "top",
        align: "start",
        onPopoverRender: (
          popover,
          { config, state },
        ) => {
          const actions =
            AddButtonActions(
              popover,
              "Voltar ao início",
            );
          actions.addEventListener(
            "click",
            async () => {
              //Livewire.dispatch('to-close')
              await updateOnedDriverObj.destroy();
              store.openMai = true;
            },
          );
        },
      },
      onNextClick: async () => {
        console.log("click para p roi");
      },

    },
    {
      element: `#temp-preview-actions-${dataId}`, // Seleciona o elemento com base no ID
      popover: {
        description: `Clique em 'Enviar' para enviar o video`,
        side: "top",
        align: "start",
        onPopoverRender: (
          popover,
          { config, state },
        ) => {
          const actions =
            AddButtonActions(
              popover,
              "Voltar ao inicio",
            );
          actions.addEventListener(
            "click",
            async () => {
              //Livewire.dispatch('to-close')
              await updateOnedDriverObj.destroy();
              store.openMai = true;
            },
          );
        },
      },
      onHighlighted: async (el) => {
        //await new Promise((resolve) => setTimeout(resolve, 3000));
        const btnSendVideoBlock =
          el.querySelector(
            `#lesson-send-video-block-content-${dataId}`,
          );
        if (!btnSendVideoBlock) {
          console.log("fudeo");
          return;
        }
        btnSendVideoBlock.addEventListener(
          "click",
          async () => {
            await updateOnedDriverObj.moveNext();
          },
        );
        //console.log('Bateu para terminar')
      },
    },
    {
      element: `#progress-bar-container-${dataId}`, // Seleciona o elemento com base no ID
      popover: {
        description: "Aguarde enquanto o video e enviado",
        side: "top",
        align: "start",
      },
      onHighlighted: async (el) => {
        await new Promise((resolve) =>
          setTimeout(resolve, 1000),
        );
        //console.log('Bateu para terminar')
        resolve(true);
        await updateOnedDriverObj.destroy();
        console.log("travamos here");
      },
    },
  ];

  // Cria um novo tour com os passos atualizados
  const updateOnedDriverObj = drivertour({
    ...configDriver,
    steps: [...steps],
  });

  await updateOnedDriverObj.drive();
  return;
}