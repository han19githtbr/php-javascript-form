// variável para armazenar o email para o qual a mensagem será enviada
var emailUser = ''
// Botão para salvar o email
let $salvarMail = document.querySelector('#salvar-email')
// Fazer referência ao formulário
let $enviarMail = document.querySelector('#form-mail')

// função para validar o formulário
function validarEmail(email) {
	let expressao = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,4})+[a-zA-Z]{1,}$/;

	if (!expressao.exec(email)) {
		return false
	} else {
		return true
	}
}

// função para salvar o email com um modal personalizado
function salvarEmail() {
    swal({
        title: "Importante",
        text: "Digite o teu email:",
        content: {
            element: "input",
            attributes: {
                type: "email",
                placeholder: "Endereço de email",
                style: "color: black;" // Estilo para definir a cor do texto como preto
            }
        },
        buttons: {
            cancel: {
                text: "Cancelar",
                value: null,
                visible: true,
                className: "btn-secondary",
                closeModal: true
            },
            confirm: {
                text: "Salvar",
                value: true,
                visible: true,
                className: "btn-primary",
                closeModal: true
            }
        }
    })
    .then((value) => {
        if (value === null) { // Verifica se o botão cancel foi clicado
            swal('Error', 'Clique no botão Salvar Email e digite o seu email', 'error');
        } else if (value === '') {
            swal(
                'Aviso',
                'Não digitou o email. Não se preocupe, pode fazer isso mais tarde',
                'warning'
            );
        } else {
            if (!validarEmail(value)) {
                swal('Error', 'Digite um email válido', 'error');
            } else {
                swal('Perfeito', 'Email Salvo', 'success');
                emailUser = value;
                console.log(emailUser);
            }
        }
    });
}


// chamar a função salvarEmail
salvarEmail()

// salvar o email
$salvarMail.addEventListener('click', () => {
	salvarEmail()
})

// Enviar a mensagem
$enviarMail.addEventListener('submit', (e) => {
	// Evitar a recarga quando a mensagem é enviada
	e.preventDefault()

	// Validar o email para o qual a mensagem é enviada
	if (!validarEmail(emailUser)) {
		swal(
			'Error',
			'Você colocou un email incorreto, digite um email correto',
			'error'
		)
	} else {
		// Capturar os dados do formulário
		let $nome = document.querySelector('#nome').value
		let $mensagem = document.querySelector('#mensagem').value
		let $correio = document.querySelector('#correio').value

		// Se os campos estiverem vazios no envio
		if (
			$nome === '' ||
			$mensagem === '' ||
			$correio === ''
		) {
			swal('Error', 'Preenche todos os campos, por favor!', 'error')
		} else {
			// Capturar os dados do formulário e anexar mais um dado
			let formData = new FormData($enviarMail)
			formData.append('email', emailUser)

			const url = "/Model/mail.php"
			
			// Utilizar fetch para enviar os dados para o Backend
			fetch(url, {
				method: 'POST',
				body: formData,
			})
				
				.then((response) => response.json())
				.then((data) => {
					console.log(data);
					console.log(typeof data);
					if (!data.error) {
						swal('Perfeito', data.mensagem, 'success')
						$nome = ''
						$mensagem = ''
						$correio = ''
					} else {
						swal('Sentimos muito', data.mensagem, 'warning')
					}
				})
		}
	}
})
