# EXTENSAO_SUAP - Envio de Certificados Automático

**Versão:** 0.1  
**Módulo:** Envio automático de certificados por e-mail

## Descrição

Sistema web para envio automático de certificados em PDF para estudantes cadastrados em arquivo CSV. O sistema gera protocolo de envio para cada certificado enviado.

## Requisitos

- PHP 8.3+
- Servidor web (Apache/Nginx)
- Permissões de escrita nas pastas `logs/` e `protocolos/`
- Extensões PHP: `mail`, `json`, `fileinfo`

## Estrutura de Arquivos

```
extensao-suap-certificados/
├── config/config.php          # Configurações do sistema
├── data/
│   ├── estudantes.csv         # Lista de estudantes (prontuário, e-mail)
│   └── certificados/          # PDFs dos certificados
├── logs/                      # Logs de envio
├── protocolos/                # Protocolos gerados
├── assets/
│   ├── css/style.css         # Estilos da interface
│   └── js/app.js             # JavaScript da interface
├── includes/
│   ├── functions.php         # Funções auxiliares
│   └── email.php             # Funções de envio de e-mail
├── index.php                 # Interface principal
└── processar.php             # Processamento de envios
```

## Formato do Arquivo CSV

O arquivo `data/estudantes.csv` deve ter o seguinte formato:

```csv
prontuario,email
GU0000001,estudante1@email.com
GU0000002,estudante2@email.com
GU000000X,estudante3@email.com
```

**Importante:** 
- Primeira linha deve conter os cabeçalhos
- Prontuários podem terminar com X
- Separador: vírgula (,)

## Certificados

Os certificados devem estar em formato PDF na pasta `data/certificados/` com o nome igual ao prontuário:

```
GU0000001.pdf
GU0000002.pdf
GU000000X.pdf
```

## Instalação

### 1. Clone o repositório

```bash
git clone https://github.com/seu-usuario/extensao-suap-certificados.git
cd extensao-suap-certificados
```

### 2. Configure permissões

```bash
chmod 755 -R .
chmod 777 logs/
chmod 777 protocolos/
```

### 3. Configure o sistema

Edite o arquivo `config/config.php` e ajuste:

- **Remetente do e-mail:** `MAIL_FROM` e `MAIL_FROM_NAME`
- **Assunto do e-mail:** `MAIL_SUBJECT`
- **Timezone:** `TIMEZONE`

### 4. Prepare os dados

- Coloque o arquivo `estudantes.csv` em `data/`
- Coloque os certificados PDF em `data/certificados/`

### 5. Acesse o sistema

Abra no navegador: `http://seu-dominio.com/extensao-suap-certificados/`

## Uso

1. Acesse a interface web
2. O sistema carregará automaticamente os estudantes do CSV
3. Revise a lista de estudantes e certificados disponíveis
4. Clique em "Enviar Certificados"
5. Acompanhe o progresso do envio
6. Verifique os protocolos gerados em `protocolos/`

## Protocolo de Envio

Cada envio gera um protocolo único no formato:

```
PROTOCOLO-YYYYMMDD-HHMMSS-{HASH}
```

Exemplo: `PROTOCOLO-20260107-143025-a1b2c3d4`

O arquivo de protocolo contém:
- Data/hora do envio
- Prontuário do estudante
- E-mail de destino
- Status do envio
- Hash MD5 do certificado

## Logs

Os logs de envio são salvos em `logs/envios.log` com:
- Timestamp
- Prontuário
- E-mail
- Status (SUCESSO/ERRO)
- Protocolo gerado

## Segurança

- Pastas `logs/` e `protocolos/` protegidas por `.htaccess`
- Validação de formato de e-mail
- Validação de existência de certificados
- Sanitização de dados de entrada

## Atualização via Git

Para atualizar o sistema:

```bash
git pull origin main
```

**Atenção:** Não versione os arquivos em:
- `data/estudantes.csv`
- `data/certificados/*.pdf`
- `logs/*.log`
- `protocolos/*.txt`

## Troubleshooting

### E-mails não estão sendo enviados

- Verifique se a função `mail()` está habilitada no PHP
- Confira as configurações de SMTP do servidor
- Verifique os logs em `logs/envios.log`

### Erro de permissão

```bash
chmod 777 logs/
chmod 777 protocolos/
```

### Certificado não encontrado

- Verifique se o nome do arquivo PDF corresponde ao prontuário
- Exemplo: `GU0000001.pdf` para prontuário `GU0000001`

## Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/nova-funcionalidade`)
3. Commit suas mudanças (`git commit -am 'Adiciona nova funcionalidade'`)
4. Push para a branch (`git push origin feature/nova-funcionalidade`)
5. Abra um Pull Request

## Roadmap

### Versão 0.2 (Planejado)
- [ ] Interface para upload de CSV via web
- [ ] Visualização de histórico de envios
- [ ] Reenvio individual de certificados
- [ ] Relatório de envios em PDF

### Versão 0.3 (Planejado)
- [ ] Autenticação de usuários
- [ ] Agendamento de envios
- [ ] Templates de e-mail personalizáveis
- [ ] Dashboard com estatísticas

## Licença

Este projeto é de código aberto e está disponível sob a licença MIT.

## Autor

Professor - Especialista em Arquitetura de Computadores e Sistemas

## Suporte

Para dúvidas ou problemas, abra uma issue no GitHub.