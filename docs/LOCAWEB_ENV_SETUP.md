LOCAWEB - Guia rápido para configurar credenciais EFI (seguro)

Objetivo: guardar com segurança as variáveis necessárias para a integração EFI, e o certificado PEM, em hospedagem Locaweb (compartilhada).

Opção recomendada: conferir no painel da Locaweb por "Variáveis de Ambiente" ou "App Settings" e inserir as chaves lá. Se não houver suporte, usar `.htaccess.env` fora do repo e `Include` (se suportado) ou SetEnv direto no `.htaccess` (menos ideal).

Passo-a-passo (painel Locaweb - modo recomendado):
1. Acesse o Painel Locaweb > Sites / Aplicações > selecione o site do PetFinder.
2. Procure por "Variáveis de Ambiente" / "Environment Variables" / "Configurações de aplicação".
3. Adicione as entradas:
   - EFI_CLIENT_ID = <valor>
   - EFI_CLIENT_SECRET = <valor>
   - EFI_PIX_KEY = <valor>
   - EFI_WEBHOOK_TOKEN = <valor>
   - EFI_CERTIFICATE_PATH = /home/usuario/secrets/producao-573055-petfinder.pem
4. Salve e reinicie a aplicação (se houver botão de restart).

Se painel NÃO suportar env vars (alternativa):
1. Faça upload do certificado PEM para uma pasta fora do webroot (ex: /home/usuario/secrets/). Use FTP/SFTP/Panel File Manager.
2. Peça ao suporte ou use SSH para alterar permissões:
   - chmod 600 /home/usuario/secrets/producao-573055-petfinder.pem
3. Crie localmente (no servidor) o arquivo `.htaccess.env` (já criado no projeto como template) com as linhas SetEnv preenchidas com os valores reais.
4. Se o Apache permitir, inclua no `.htaccess` da aplicação:
   - Include .htaccess.env
   Caso o Include não seja permitido, copie as linhas SetEnv diretamente no `.htaccess` (atenção: é menos ideal, mas funcional).

Testes e validação:
- Via SSH (se disponível): rodar `php test_oauth_authorization.php` — deve retornar HTTP 200 e token válido.
- Verificar logs: `includes/petfinder_error_log` e `logs/production_subscription_*.log` (não conter segredos).
- Testar webhook: use um endpoint público ou checar se o painel EFI aceita definir webhook e que o token de webhook esteja correto.

Texto pronto para suporte Locaweb (copiar/colar):
"Olá, preciso definir variáveis de ambiente para meu site (PetFinder). Por favor, poderia criar as seguintes environment variables para a aplicação em: petfinder.pageup.net.br ?
EFI_CLIENT_ID = <VALOR>
EFI_CLIENT_SECRET = <VALOR>
EFI_PIX_KEY = <VALOR>
EFI_WEBHOOK_TOKEN = <VALOR>
Além disso, gostaria que o arquivo /home/usuario/secrets/producao-573055-petfinder.pem fosse movido para uma pasta fora do webroot e que as permissões sejam definidas como 600 (somente leitura para o dono). Obrigado." 

Recomendações finais:
- Nunca commit o `.htaccess.env` ou qualquer arquivo com chaves ao repositório.
- Depois de configurar, **revogue as chaves antigas** e gere novas no painel EFI se elas foram expostas.
- Opcional: habilitar monitor de webhooks para alertar quando eventos falham. Podemos implementar isso aqui se desejar.
