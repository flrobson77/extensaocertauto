<<<<<<< HEAD
# Guia de Instalação - EXTENSAO_SUAP

## Pré-requisitos

- Servidor web (Apache/Nginx)
- PHP 8.3 ou superior
- Acesso SSH ao servidor (recomendado)
- Git instalado
- Permissões para criar diretórios e arquivos

## Passo 1: Clonar o Repositório

### Via SSH
```bash
cd /var/www/html  # ou seu diretório web
git clone git@github.com:seu-usuario/extensao-suap-certificados.git
cd extensao-suap-certificados
```

### Via HTTPS
```bash
cd /var/www/html
git clone https://github.com/seu-usuario/extensao-suap-certificados.git
cd extensao-suap-certificados
```

## Passo 2: Configurar Permissões

```bash
# Define permissões básicas
chmod 755 -R .

# Permissões de escrita para logs e protocolos
chmod 777 logs/
chmod 777 protocolos/

# Permissões de leitura para certificados
chmod 755 data/
chmod 755 data/certificados/
```

## Passo 3: Configurar o Sistema

### 3.1 Editar config.php

```bash
nano config/config.php
```

Ajuste as seguintes configurações:

```php
// Remetente do e-mail
define('MAIL_FROM', 'seu-email@instituicao.edu.br');
define('MAIL_FROM_NAME', 'Nome da Instituição');

// Assunto do e-mail
define('MAIL_SUBJECT', 'Certificado de Participação');

// Timezone
define('TIMEZONE', 'America/Sao_Paulo');

// Modo de teste (true = não envia e-mails reais)
define('TEST_MODE', false);
```

### 3.2 Preparar arquivo CSV

```bash
# Copie o exemplo
cp data/estudantes.csv.exemplo data/estudantes.csv

# Edite com seus dados
nano data/estudantes.csv
```

Formato do CSV:
```csv
prontuario,email
GU0000001,estudante1@email.com
GU0000002,estudante2@email.com
```

### 3.3 Adicionar Certificados

Copie os arquivos PDF para o diretório `data/certificados/`:

```bash
# Exemplo usando scp
scp certificados/*.pdf usuario@servidor:/caminho/data/certificados/

# Ou se já estiver no servidor
cp /origem/*.pdf data/certificados/
```

**Importante:** O nome do arquivo deve ser igual ao prontuário:
- `GU0000001.pdf`
- `GU0000002.pdf`
- `GU000000X.pdf`

## Passo 4: Verificar Configuração do PHP

### 4.1 Verificar função mail()

```bash
php -r "echo function_exists('mail') ? 'OK' : 'ERRO';"
```

### 4.2 Verificar extensões necessárias

```bash
php -m | grep -E 'json|fileinfo'
```

### 4.3 Configurar php.ini (se necessário)

```bash
# Encontre o php.ini
php --ini

# Edite o arquivo
nano /etc/php/8.3/apache2/php.ini
```

Verifique/ajuste:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

Reinicie o Apache:
```bash
sudo systemctl restart apache2
```

## Passo 5: Configurar Apache

### 5.1 Habilitar mod_rewrite (se necessário)

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 5.2 Configurar VirtualHost (opcional)

```bash
sudo nano /etc/apache2/sites-available/certificados.conf
```

```apache
<VirtualHost *:80>
    ServerName certificados.sua-instituicao.edu.br
    DocumentRoot /var/www/html/extensao-suap-certificados
    
    <Directory /var/www/html/extensao-suap-certificados>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/certificados-error.log
    CustomLog ${APACHE_LOG_DIR}/certificados-access.log combined
</VirtualHost>
```

Ative o site:
```bash
sudo a2ensite certificados.conf
sudo systemctl reload apache2
```

## Passo 6: Testar o Sistema

### 6.1 Teste via linha de comando

```bash
# Testar leitura do CSV
php -r "require 'includes/functions.php'; var_dump(lerEstudantesCSV());"

# Testar busca de certificado
php -r "require 'includes/functions.php'; var_dump(buscarCertificado('GU0000001'));"
```

### 6.2 Teste no navegador

1. Acesse: `http://seu-servidor/extensao-suap-certificados/`
2. Verifique se os estudantes são carregados
3. Verifique as estatísticas

### 6.3 Modo de teste

Ative o modo de teste em `config/config.php`:
```php
define('TEST_MODE', true);
```

Execute um envio de teste. Isso simulará o envio sem enviar e-mails reais.

## Passo 7: Configurar E-mail (se necessário)

### Hospedagem Compartilhada
A maioria das hospedagens compartilhadas já tem a função `mail()` configurada.

### Servidor Próprio (VPS/Dedicado)

#### Instalar Postfix

```bash
sudo apt update
sudo apt install postfix mailutils
```

Escolha "Internet Site" durante a instalação.

#### Configurar Postfix

```bash
sudo nano /etc/postfix/main.cf
```

Ajuste:
```
myhostname = mail.sua-instituicao.edu.br
mydomain = sua-instituicao.edu.br
myorigin = $mydomain
```

Reinicie:
```bash
sudo systemctl restart postfix
```

#### Testar envio

```bash
echo "Teste" | mail -s "Assunto" seu-email@exemplo.com
```

## Passo 8: Colocar em Produção

1. Desative o modo de teste:
```php
define('TEST_MODE', false);
```

2. Verifique os logs:
```bash
tail -f logs/envios.log
```

3. Faça um envio de teste com 1-2 estudantes

4. Verifique se os e-mails foram recebidos

5. Verifique os protocolos gerados:
```bash
ls -lh protocolos/
```

## Passo 9: Manutenção

### Fazer backup

```bash
# Backup dos dados
tar -czf backup-$(date +%Y%m%d).tar.gz data/ logs/ protocolos/
```

### Limpar logs antigos

```bash
# Logs com mais de 30 dias
find logs/ -name "*.log" -mtime +30 -delete
```

### Atualizar via Git

```bash
git pull origin main
```

**Atenção:** Não versione os arquivos sensíveis (CSV, PDFs, logs)

## Troubleshooting

### E-mails não estão sendo enviados

1. Verifique os logs: `tail -f logs/envios.log`
2. Teste a função mail():
```bash
php -r "mail('seu-email@exemplo.com', 'Teste', 'Corpo do teste');"
```
3. Verifique spam/lixo eletrônico
4. Configure SPF/DKIM no DNS

### Erro de permissão

```bash
chmod 777 logs/
chmod 777 protocolos/
```

### Certificado não encontrado

- Verifique o nome exato do arquivo
- Compare com o prontuário no CSV
- Verifique permissões de leitura

### PHP memory limit exceeded

Edite `php.ini`:
```ini
memory_limit = 512M
```

## Segurança

1. Sempre use HTTPS em produção
2. Restrinja acesso ao diretório pelo IP (opcional)
3. Mantenha backups regulares
4. Monitore os logs regularmente
5. Atualize o PHP periodicamente

## Suporte

=======
# Guia de Instalação - EXTENSAO_SUAP

## Pré-requisitos

- Servidor web (Apache/Nginx)
- PHP 8.3 ou superior
- Acesso SSH ao servidor (recomendado)
- Git instalado
- Permissões para criar diretórios e arquivos

## Passo 1: Clonar o Repositório

### Via SSH
```bash
cd /var/www/html  # ou seu diretório web
git clone git@github.com:seu-usuario/extensao-suap-certificados.git
cd extensao-suap-certificados
```

### Via HTTPS
```bash
cd /var/www/html
git clone https://github.com/seu-usuario/extensao-suap-certificados.git
cd extensao-suap-certificados
```

## Passo 2: Configurar Permissões

```bash
# Define permissões básicas
chmod 755 -R .

# Permissões de escrita para logs e protocolos
chmod 777 logs/
chmod 777 protocolos/

# Permissões de leitura para certificados
chmod 755 data/
chmod 755 data/certificados/
```

## Passo 3: Configurar o Sistema

### 3.1 Editar config.php

```bash
nano config/config.php
```

Ajuste as seguintes configurações:

```php
// Remetente do e-mail
define('MAIL_FROM', 'seu-email@instituicao.edu.br');
define('MAIL_FROM_NAME', 'Nome da Instituição');

// Assunto do e-mail
define('MAIL_SUBJECT', 'Certificado de Participação');

// Timezone
define('TIMEZONE', 'America/Sao_Paulo');

// Modo de teste (true = não envia e-mails reais)
define('TEST_MODE', false);
```

### 3.2 Preparar arquivo CSV

```bash
# Copie o exemplo
cp data/estudantes.csv.exemplo data/estudantes.csv

# Edite com seus dados
nano data/estudantes.csv
```

Formato do CSV:
```csv
prontuario,email
GU0000001,estudante1@email.com
GU0000002,estudante2@email.com
```

### 3.3 Adicionar Certificados

Copie os arquivos PDF para o diretório `data/certificados/`:

```bash
# Exemplo usando scp
scp certificados/*.pdf usuario@servidor:/caminho/data/certificados/

# Ou se já estiver no servidor
cp /origem/*.pdf data/certificados/
```

**Importante:** O nome do arquivo deve ser igual ao prontuário:
- `GU0000001.pdf`
- `GU0000002.pdf`
- `GU000000X.pdf`

## Passo 4: Verificar Configuração do PHP

### 4.1 Verificar função mail()

```bash
php -r "echo function_exists('mail') ? 'OK' : 'ERRO';"
```

### 4.2 Verificar extensões necessárias

```bash
php -m | grep -E 'json|fileinfo'
```

### 4.3 Configurar php.ini (se necessário)

```bash
# Encontre o php.ini
php --ini

# Edite o arquivo
nano /etc/php/8.3/apache2/php.ini
```

Verifique/ajuste:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
```

Reinicie o Apache:
```bash
sudo systemctl restart apache2
```

## Passo 5: Configurar Apache

### 5.1 Habilitar mod_rewrite (se necessário)

```bash
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### 5.2 Configurar VirtualHost (opcional)

```bash
sudo nano /etc/apache2/sites-available/certificados.conf
```

```apache
<VirtualHost *:80>
    ServerName certificados.sua-instituicao.edu.br
    DocumentRoot /var/www/html/extensao-suap-certificados
    
    <Directory /var/www/html/extensao-suap-certificados>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/certificados-error.log
    CustomLog ${APACHE_LOG_DIR}/certificados-access.log combined
</VirtualHost>
```

Ative o site:
```bash
sudo a2ensite certificados.conf
sudo systemctl reload apache2
```

## Passo 6: Testar o Sistema

### 6.1 Teste via linha de comando

```bash
# Testar leitura do CSV
php -r "require 'includes/functions.php'; var_dump(lerEstudantesCSV());"

# Testar busca de certificado
php -r "require 'includes/functions.php'; var_dump(buscarCertificado('GU0000001'));"
```

### 6.2 Teste no navegador

1. Acesse: `http://seu-servidor/extensao-suap-certificados/`
2. Verifique se os estudantes são carregados
3. Verifique as estatísticas

### 6.3 Modo de teste

Ative o modo de teste em `config/config.php`:
```php
define('TEST_MODE', true);
```

Execute um envio de teste. Isso simulará o envio sem enviar e-mails reais.

## Passo 7: Configurar E-mail (se necessário)

### Hospedagem Compartilhada
A maioria das hospedagens compartilhadas já tem a função `mail()` configurada.

### Servidor Próprio (VPS/Dedicado)

#### Instalar Postfix

```bash
sudo apt update
sudo apt install postfix mailutils
```

Escolha "Internet Site" durante a instalação.

#### Configurar Postfix

```bash
sudo nano /etc/postfix/main.cf
```

Ajuste:
```
myhostname = mail.sua-instituicao.edu.br
mydomain = sua-instituicao.edu.br
myorigin = $mydomain
```

Reinicie:
```bash
sudo systemctl restart postfix
```

#### Testar envio

```bash
echo "Teste" | mail -s "Assunto" seu-email@exemplo.com
```

## Passo 8: Colocar em Produção

1. Desative o modo de teste:
```php
define('TEST_MODE', false);
```

2. Verifique os logs:
```bash
tail -f logs/envios.log
```

3. Faça um envio de teste com 1-2 estudantes

4. Verifique se os e-mails foram recebidos

5. Verifique os protocolos gerados:
```bash
ls -lh protocolos/
```

## Passo 9: Manutenção

### Fazer backup

```bash
# Backup dos dados
tar -czf backup-$(date +%Y%m%d).tar.gz data/ logs/ protocolos/
```

### Limpar logs antigos

```bash
# Logs com mais de 30 dias
find logs/ -name "*.log" -mtime +30 -delete
```

### Atualizar via Git

```bash
git pull origin main
```

**Atenção:** Não versione os arquivos sensíveis (CSV, PDFs, logs)

## Troubleshooting

### E-mails não estão sendo enviados

1. Verifique os logs: `tail -f logs/envios.log`
2. Teste a função mail():
```bash
php -r "mail('seu-email@exemplo.com', 'Teste', 'Corpo do teste');"
```
3. Verifique spam/lixo eletrônico
4. Configure SPF/DKIM no DNS

### Erro de permissão

```bash
chmod 777 logs/
chmod 777 protocolos/
```

### Certificado não encontrado

- Verifique o nome exato do arquivo
- Compare com o prontuário no CSV
- Verifique permissões de leitura

### PHP memory limit exceeded

Edite `php.ini`:
```ini
memory_limit = 512M
```

## Segurança

1. Sempre use HTTPS em produção
2. Restrinja acesso ao diretório pelo IP (opcional)
3. Mantenha backups regulares
4. Monitore os logs regularmente
5. Atualize o PHP periodicamente

## Suporte

>>>>>>> 02be3d6e93f4b7aa59e02c72bb147de6dcd9d180
Para dúvidas, abra uma issue no GitHub ou consulte a documentação completa no README.md.