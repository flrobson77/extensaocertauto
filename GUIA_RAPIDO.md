# Guia Rápido de Uso

## 📋 Preparação dos Dados

### 1. Preparar o arquivo CSV

```bash
# Copie o exemplo e edite
cp data/estudantes.csv.exemplo data/estudantes.csv
nano data/estudantes.csv
```

Formato:
```csv
prontuario,email
GU0000001,estudante1@email.com
GU0000002,estudante2@email.com
```

### 2. Adicionar os certificados PDF

Coloque os arquivos PDF em `data/certificados/` com o nome igual ao prontuário:
- `GU0000001.pdf`
- `GU0000002.pdf`
- etc.

## 🚀 Uso do Sistema

### 1. Acessar a interface

Abra no navegador:
```
http://seu-servidor/extensao-suap-certificados/
```

### 2. Verificar os dados

A interface mostrará:
- ✅ Total de estudantes carregados
- ✅ Quantos têm certificado disponível
- ✅ Quantos estão faltando certificado
- ✅ Percentual de progresso

### 3. Enviar os certificados

1. Clique no botão **"Enviar Certificados"**
2. Confirme a ação
3. Aguarde o processamento
4. Acompanhe o log de envios

### 4. Verificar resultados

**Logs de envio:**
```bash
cat logs/envios.log
```

**Protocolos gerados:**
```bash
ls -lh protocolos/
cat protocolos/PROTOCOLO-*.txt
```

## 🔧 Configurações Importantes

### Modo de Teste

Edite `config/config.php`:

```php
// true = simula envios (não envia e-mails reais)
// false = envia e-mails realmente
define('TEST_MODE', false);
```

**Recomendação:** Use `TEST_MODE = true` nos primeiros testes!

### Personalizar E-mails

Em `config/config.php`, altere:

```php
define('MAIL_FROM', 'extensao@sua-instituicao.edu.br');
define('MAIL_FROM_NAME', 'Coordenação de Extensão');
define('MAIL_SUBJECT', 'Certificado de Participação');
```

Para personalizar o corpo do e-mail, edite as constantes:
- `MAIL_BODY_HTML` - versão HTML
- `MAIL_BODY_TEXT` - versão texto plano

## 📊 Monitoramento

### Acompanhar logs em tempo real

```bash
tail -f logs/envios.log
```

### Verificar último protocolo

```bash
ls -lt protocolos/ | head -n 2
```

### Ver estatísticas

Acesse a interface web para ver:
- Total processado
- Sucessos
- Erros
- Progresso

## ❓ Solução de Problemas

### E-mails não chegam

1. Verifique a pasta de SPAM
2. Confirme as configurações em `config/config.php`
3. Teste a função mail():
```bash
php -r "mail('seu-email@exemplo.com', 'Teste', 'Corpo');"
```

### Certificado não encontrado

- Verifique se o nome do PDF é **exatamente** igual ao prontuário
- Exemplo: `GU0000001.pdf` para prontuário `GU0000001`
- Maiúsculas/minúsculas importam!

### Erro de permissão

```bash
chmod 777 logs/
chmod 777 protocolos/
```

## 📦 Backup

### Fazer backup dos dados

```bash
tar -czf backup-$(date +%Y%m%d-%H%M%S).tar.gz \
  data/estudantes.csv \
  data/certificados/ \
  logs/ \
  protocolos/
```

### Restaurar backup

```bash
tar -xzf backup-YYYYMMDD-HHMMSS.tar.gz
```

## 🔄 Atualização do Sistema

```bash
# Fazer backup primeiro!
git pull origin main

# Verificar se há mudanças nas configurações
git diff config/config.php
```

## 📝 Dicas

1. **Sempre teste com poucos e-mails primeiro** usando `TEST_MODE = true`
2. **Faça backup antes de envios grandes**
3. **Monitore os logs** durante o envio
4. **Guarde os protocolos** por tempo adequado
5. **Use HTTPS** em produção

## 🆘 Ajuda Rápida

**Ver estudantes no CSV:**
```bash
cat data/estudantes.csv
```

**Contar certificados:**
```bash
ls data/certificados/*.pdf | wc -l
```

**Ver logs de hoje:**
```bash
grep "$(date +%Y-%m-%d)" logs/envios.log
```

**Limpar logs antigos (>30 dias):**
```bash
find logs/ -name "*.log" -mtime +30 -delete
```

## 📞 Suporte

- GitHub Issues: [link do repositório]
- Documentação completa: README.md
- Instalação detalhada: INSTALACAO.md