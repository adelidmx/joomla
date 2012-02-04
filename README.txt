=== Módulo de integração PagSeguro + VirtueMart ===

Contributors: 
    ldmotta(visie.com.br - Implementação do múdulo),

Module version: 1.0.4
Tags: pagseguro, virtuemart
Tested up to: VirtueMart v_1.1.9
Requires at least: 1.0.4
Stable tag: 1.1.9

Módulo de integração do VirtueMart com o Pagseguro

== Description ==

Permite que o VirtueMart utilize o geteway de pagamento PagSeguro, de forma fácil e intuitiva, contém todas as
ferramentas necessárias a esta integração.


Algumas notas sobre as seções acima:

*   "Contributors" Lista de contribuidores para construção do módulo separados por vírgula
*   "Tags" É uma lista separada por vírgulas de tags que se aplicam ao plugin
*   "Requires at least" É a menor versão do plugin que irá trabalhar em
*   "Tested up to" É a versão mais alta do e-commerce utilizado com sucesso para testar o plugin *. Note-se que ele pode trabalhar em
versões superiores ... Este é apenas um mais alto que foi verificado.

== Installation ==

Descompacte os arquivos do pacote, copie todo o seu conteúdo ("administrator", "README.txt") e cole na raiz 
da sua instalação do Joomla, no mesmo nível da pasta "administrator" (os arquivos já estão colocados em suas 
respectivas pastas), não havendo necessidade de colocar os aquivos em pastas separadas. 


Vá no menu principal do VirtueMart e acesse o menu "Store > Add Payment Method"

Coloque no formulário as seguintes informações.

|| Campo              | Valor                        ||
| Active              | deixe marcado                 |
| Payment Method Name | PagSeguro                     |
| Code                | PGS                           |
| Payment class name  | ps_pagseguro                  |
| Payment method type | HTML-Form bases (e.g.) PayPal |

Os outros campos são opcionais. Na aba de configurações ("Configuration"), no campo "Payment Extra Info" copie e cole 

o código que se encontra na raiz do projeto chamado "pagseguro_payment_method_code".

Clique no botão "Save". Você será enviado para a tela de listagem de métodos. Clique no método PagSeguro para editar seus dados.

Na aba de configuração defina seu código TOKEN do PagSeguro, seu e-mail no PagSeguro e o Tipo de Frete desejado.

Clique no Botão "Save" para gravar sua configurações. Pronto! O seu VirtueMart está com o módulo PagSeguro instalado e seus clientes poderão comprar através dele.

== Perguntas Frequentes ==

= Eu posso instalar o meu módulo sem ter conhecimentos de php ou qualquer linguagem de programação? =

Pode, você só precisa ter conhecimentos em transferência de dados via FTP ou SFTP, ter os dados de acesso
ao servidor onde está hospedado a sua aplicação, e ter um gerenciador de arquivos FTP como o FileZilla
(http://filezilla-project.org/). Entretanto, recomendamos enfaticamente que procure um técnico da área.

= O módulo não funcionou na minha loja, o que fazer? =

Se já verificou a versão da sua loja virtual e ela e a versão testada com o módulo, e ainda assim não funciona,
entre em contato com o suporte técnico do PagSeguro 

== Changelog ==

= 1.0.4 =
* Implementação da nova lib pagseguro, já com as classes renomeadas.
