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

  fetch("/wp-json/smartchat/v1/ask", {
    method: "POST",
    headers: {
      "Content-Type": "application/json"
    },
    body: JSON.stringify({ message: userMsg })
  })
    .then(res => res.json())
    .then(data => {
      addMessage("Bot", data.reply || "No recibí respuesta de la IA.");
    })
    .catch(err => {
      console.error("Error al conectar con IA:", err);
      addMessage("Bot", "Hubo un error al conectar con el servidor.");
    });
}

  sendBtn.addEventListener("click", sendMessage);
  input.addEventListener("keypress", (e) => {
    if (e.key === "Enter") sendMessage();
  });
});