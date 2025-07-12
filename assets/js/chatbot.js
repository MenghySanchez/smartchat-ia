document.addEventListener("DOMContentLoaded", () => { // Esperar a que el DOM esté completamente cargado antes de ejecutar el script.
  const bubble = document.getElementById("smartchat-bubble"); // Obtener el botón del chatbot.
  const window = document.getElementById("smartchat-window"); // Obtener la ventana del chatbot.

  bubble.addEventListener("click", () => { // Añadir un evento de clic al botón del chatbot para alternar la visibilidad de la ventana del chatbot.
    window.style.display = window.style.display === "none" ? "block" : "none"; // Si la ventana del chatbot está oculta, mostrarla; si está visible, ocultarla.
  });
});