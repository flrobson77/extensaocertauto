# Estrutura do Projeto EXTENSAO_SUAP

## 📁 Árvore de Diretórios e Arquivos

```
extensao-suap-certificados/
│
├── 📄 README.md                    # Documentação principal do projeto
├── 📄 INSTALACAO.md                # Guia detalhado de instalação
├── 📄 GUIA_RAPIDO.md               # Guia rápido de uso
├── 📄 .gitignore                   # Arquivos ignorados pelo Git
│
├── 📂 config/                      # Configurações do sistema
│   └── 📄 config.php              # Arquivo principal de configuração
│
├── 📂 data/                        # Dados do sistema
│   ├── 📄 README.md               # Instruções sobre dados
│   ├── 📄 estudantes.csv.exemplo  # Exemplo de arquivo CSV
│   ├── 📄 estudantes.csv          # Arquivo CSV real (não versionado)
│   └── 📂 certificados/           # PDFs dos certificados
│       ├── 📄 GU0000001.pdf       # (não versionado)
│       ├── 📄 GU0000002.pdf       # (não versionado)
│       └── ...
│
├── 📂 logs/                        # Logs do sistema
│   ├── 📄 .htaccess               # Proteção do diretório
│   └── 📄 envios.log              # Log de envios (não versionado)
│
├── 📂 protocolos/                  # Protocolos de envio
│   ├── 📄 .htaccess               # Proteção do diretório
│   └── 📄 PROTOCOLO-*.txt         # Arquivos de protocolo (não versionados)
│
├── 📂 assets/                      # Recursos estáticos
│   ├── 📂 css/
│   │   └── 📄 style.css           # Estilos da interface
│   └── 📂 js/
│       └── 📄 app.js              # JavaScript da interface
│
├── 📂 includes/                    # Bibliotecas PHP
│   ├── 📄 functions.php           # Funções auxiliares
│   └── 📄 email.php               # Funções de e-mail
│
├── 📄 index.php                    # Interface principal (frontend)
└── 📄 processar.php                # Processador de requisições (backend)
```

## 📋 Descrição dos Arquivos

### 🔧 Arquivos de Configuração

| Arquivo | Descrição | Versionado? |
|---------|-----------|-------------|
| `config/config.php` | Configurações principais (e-mail, timezone, caminhos) | ✅ Sim |
| `.gitignore` | Define arquivos não versionados | ✅ Sim |

### 📊 Arquivos de Dados

| Arquivo | Descrição | Versionado? |
|---------|-----------|-------------|
| `data/estudantes.csv` | Lista de estudantes (prontuário, e-mail) | ❌ Não |
| `data/estudantes.csv.exemplo` | Exemplo de formato CSV | ✅ Sim |
| `data/certificados/*.pdf` | Certificados em PDF | ❌ Não |

### 🎨 Frontend (Interface)

| Arquivo | Descrição | Linguagem |
|---------|-----------|-----------|
| `index.php` | Página principal do sistema | PHP/HTML |
| `assets/css/style.css` | Estilos visuais | CSS3 |
| `assets/js/app.js` | Lógica da interface | JavaScript |

### ⚙️ Backend (Servidor)

| Arquivo | Descrição | Função |
|---------|-----------|--------|
| `processar.php` | Processa requisições AJAX | API REST |
| `includes/functions.php` | Funções utilitárias | Biblioteca |
| `includes/email.php` | Envio de e-mails | Biblioteca |

### 📝 Logs e Protocolos

| Arquivo | Descrição | Versionado? |
|---------|-----------|-------------|
| `logs/envios.log` | Registro de todas as operações | ❌ Não |
| `logs/.htaccess` | Proteção contra acesso web | ✅ Sim |
| `protocolos/PROTOCOLO-*.txt` | Comprovantes de envio | ❌ Não |
| `protocolos/.htaccess` | Proteção contra acesso web | ✅ Sim |

### 📖 Documentação

| Arquivo | Descrição |
|---------|-----------|
| `README.md` | Documentação completa do projeto |
| `INSTALACAO.md` | Guia passo a passo de instalação |
| `GUIA_RAPIDO.md` | Guia rápido de uso |
| `data/README.md` | Instruções sobre os dados |

## 🔄 Fluxo de Dados

```
┌─────────────────┐
│  Usuário acessa │
│    index.php    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐      ┌─────────────────┐
│   JavaScript    │◄────►│  processar.php  │
│    (app.js)     │ AJAX │   (backend)     │
└─────────────────┘      └────────┬────────┘
                                  │
                    ┌─────────────┼─────────────┐
                    ▼             ▼             ▼
              ┌──────────┐  ┌─────────┐  ┌──────────┐
              │ CSV File │  │   PDF   │  │   Logs   │
              │estudantes│  │  Files  │  │protocolos│
              └──────────┘  └─────────┘  └──────────┘
                                  │
                                  ▼
                          ┌───────────────┐
                          │  Envio Email  │
                          │  (SMTP/mail)  │
                          └───────────────┘
```

## 🔐 Segurança

### Arquivos Protegidos

- ✅ `logs/.htaccess` - Bloqueia acesso web aos logs
- ✅ `protocolos/.htaccess` - Bloqueia acesso web aos protocolos
- ✅ `.gitignore` - Impede versionamento de dados sensíveis

### Dados Não Versionados

```
❌ data/estudantes.csv         (dados pessoais)
❌ data/certificados/*.pdf     (documentos)
❌ logs/*.log                  (registros)
❌ protocolos/*.txt            (protocolos)
```

## 📦 Tamanho Estimado

| Componente | Tamanho Aprox. |
|------------|----------------|
| Sistema base | ~100 KB |
| CSV (100 estudantes) | ~5 KB |
| Certificado PDF (cada) | 100-500 KB |
| Log de envios | 10-50 KB/dia |
| Protocolo (cada) | ~1 KB |

## 🎯 Arquivos Essenciais para Deploy

```bash
# Clone do repositório
git clone [repo]

# Arquivos que você DEVE adicionar/configurar:
✅ data/estudantes.csv          # Seus dados
✅ data/certificados/*.pdf      # Seus certificados
✅ config/config.php            # Ajustar configurações

# Arquivos que serão criados automaticamente:
🔄 logs/envios.log             # Criado no primeiro envio
🔄 protocolos/PROTOCOLO-*.txt  # Criado a cada envio
```

## 🚀 Comandos Úteis

```bash
# Ver estrutura
find . -type f -not -path "./.git/*" | sort

# Contar arquivos por tipo
find . -name "*.php" | wc -l
find . -name "*.js" | wc -l
find . -name "*.css" | wc -l

# Tamanho total do projeto
du -sh .

# Verificar permissões
ls -la logs/ protocolos/
```

## 📊 Estatísticas do Código

| Linguagem | Arquivos | Linhas Aprox. |
|-----------|----------|---------------|
| PHP | 5 | ~1200 |
| JavaScript | 1 | ~400 |
| CSS | 1 | ~400 |
| Markdown | 4 | ~800 |
| **Total** | **11** | **~2800** |

## 🔗 Dependências

### PHP (Nativas)
- `mail()` - Envio de e-mails
- `json_encode/decode()` - Manipulação JSON
- `fopen/fgetcsv()` - Leitura CSV
- `file_get_contents()` - Leitura de arquivos

### Frontend (Vanilla)
- JavaScript ES6+
- CSS3 com Flexbox/Grid
- Fetch API para AJAX

**Sem dependências externas!** 🎉

## 📱 Responsividade

O sistema é responsivo e funciona em:
- 💻 Desktop (1024px+)
- 📱 Tablet (768px - 1023px)
- 📱 Mobile (até 767px)

## 🎨 Design

- **Tema:** Azul institucional
- **Fonte:** Segoe UI (system font)
- **Ícones:** Unicode/Emoji
- **Layout:** Minimalista e profissional