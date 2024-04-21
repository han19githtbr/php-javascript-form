<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Teste de Programação</title>

  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">

  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
  <button class="btn btn-success salvar-email" id="salvar-email">Salvar Email</button>


  <div class="card-body">
    <h1 class="card-title">Teste de Programação</h1>
    <p id="card-text"></p>
  </div>

    
  <div class="contato">
    <h2>Contato</h2>
    <form id="form-mail">
      <label for="nome">Nome</label>
      <input type="text" class="form-control" id="nome" placeholder="Digite o teu nome..." name="nome" required />
      <label for="email">E-mail</label>
      <input type="email" class="form-control" id="correio" placeholder="Digite o teu email..." name="correio" required />
      <label for="mensagem">Mensagem</label>
      <textarea name="mensagem" id="mensagem" class="form-control" placeholder="Digite a tua mensagem..." required></textarea>
      <button type="submit" data-button>Enviar Mensagem</button>
    </form>
  </div>
 

  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <script src="Controller/mail.js"></script>
  <script src="Controller/animacao-texto.js"></script>
</body>
</html>