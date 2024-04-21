  // Função para mostrar o texto letra por letra
  function showText(el, text, interval) {
    var index = 0;
    var timer = setInterval(function() {
      if (index < text.length) {
        el.innerHTML += text[index];
        index++;
      } else {
        clearInterval(timer);
      }
    }, interval);
  }
    
  // Espera 2 segundos após o carregamento da página
  setTimeout(function() {
    var mensagem = document.getElementById('card-text');
    var text = "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat.";
    var interval = 30;
  
    // Inicia a digitação da mensagem
    showText(mensagem, text, interval);
  
    
  }, 8000); // 2 segundos antes de começar a animação
 
