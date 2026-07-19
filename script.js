const testButton = document.querySelector("#testButton");
const message = document.querySelector("#message");

testButton.addEventListener("click", () => {
  message.textContent = "Tudo funcionando! HTML, CSS e JavaScript carregaram corretamente.";
  testButton.textContent = "Teste concluído ✓";
});