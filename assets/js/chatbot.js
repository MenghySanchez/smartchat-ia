document.addEventListener("DOMContentLoaded", () => { // Esperar a que el DOM esté completamente cargado antes de ejecutar el script.
  const bubble = document.getElementById("smartchat-bubble"); // Obtener el botón del chatbot.
  const window = document.getElementById("smartchat-window"); // Obtener la ventana del chatbot.

  bubble.addEventListener("click", () => { // Añadir un evento de clic al botón del chatbot para alternar la visibilidad de la ventana del chatbot.
    window.style.display = window.style.display === "none" ? "block" : "none"; // Si la ventana del chatbot está oculta, mostrarla; si está visible, ocultarla.
  });
function addMessage(sender, text) {
    const msg = document.createElement("div");
    msg.innerHTML = `<strong>${sender}:</strong> ${text}`;
    messages.appendChild(msg);
    messages.scrollTop = messages.scrollHeight;
  }

  function sendMessage() {
    const userMsg = input.value.trim();
    if (userMsg === "") return;

    addMessage("Tú", userMsg);
    input.value = "";

    // Simulación de respuesta del bot
    setTimeout(() => {
      addMessage("Bot", "Gracias por tu mensaje. Pronto me conectaré con la IA 😊");
    }, 1000);
  }

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
});