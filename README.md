# Diretório de Dados

## Instruções

### 1. Arquivo CSV de Estudantes

Renomeie o arquivo `estudantes.csv.exemplo` para `estudantes.csv` e edite-o com os dados reais dos estudantes.

**Formato:**
```csv
prontuario,email
GU0000001,estudante1@email.com
GU0000002,estudante2@email.com
GU000000X,estudante3@email.com
```

**Importante:**
- A primeira linha deve conter os cabeçalhos: `prontuario,email`
- Prontuários devem seguir o padrão: `GU` + 7 dígitos + 1 dígito/letra (pode terminar com X)
- E-mails devem ser válidos

### 2. Certificados

Coloque os certificados em PDF na pasta `certificados/` com o nome igual ao prontuário:

```
certificados/
├── GU0000001.pdf
├── GU0000002.pdf
├── GU000000X.pdf
└── ...
```

**Importante:**
- Formato: PDF
- Nome do arquivo: exatamente igual ao prontuário + `.pdf`
- Tamanho máximo recomendado: 10MB por arquivo