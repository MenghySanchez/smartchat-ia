document.addEventListener("DOMContentLoaded", () => {
  const bubble = document.getElementById("smartchat-bubble");
  const windowEl = document.getElementById("smartchat-window");
  const messages = document.getElementById("smartchat-messages");
  const input = document.getElementById("smartchat-user-input");
  const sendBtn = document.getElementById("smartchat-send-btn");

  bubble.addEventListener("click", () => {
    windowEl.style.display = windowEl.style.display === "none" ? "flex" : "none";
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

    // Simulación de respuesta automática
    setTimeout(() => {
      addMessage("Bot", "Gracias por tu mensaje. Pronto me conectaré con la IA 😊");
    }, 1000);
  }

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
});