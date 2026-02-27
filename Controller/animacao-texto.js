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
    var text = "Esta aplicação é um sistema simples de envio de mensagens onde o usuário envia o seu nome e endereço e-mail para outro endereço e-mail real predefinido. Para isso, desenvolvi o Back-end em PHP e o Front-end em JavaScript.";
    var interval = 30;
  
    // Inicia a digitação da mensagem
    showText(mensagem, text, interval);
  
    
  }, 8000); // 2 segundos antes de começar a animação
 
