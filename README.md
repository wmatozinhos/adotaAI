# AdotaAi

O **AdotaAi** é uma plataforma desenvolvida para facilitar o processo de adoção de animais de estimação. O objetivo principal é conectar pessoas interessadas em adotar pets com animais que precisam de um lar, promovendo o bem-estar animal e incentivando a adoção responsável.

## Funcionalidades

painel de administrador
login/

admin@adotaai.com
admin123456


### Para Usuários
- Cadastro e login de usuários.
- Atualização de perfil com informações pessoais e foto.
- Busca de pets disponíveis para adoção com filtros (idade, porte, sexo, etc.).
- Manifestação de interesse em pets.
- Histórico de interesses e adoções realizadas.

### Para Administradores
- Gerenciamento de usuários (ativação, bloqueio e exclusão).
- Cadastro, edição e exclusão de pets.
- Gerenciamento de categorias de pets.
- Aprovação ou rejeição de solicitações de adoção.
- Painel administrativo com estatísticas e gráficos sobre adoções e usuários.

### Pets
- Cadastro de pets com informações detalhadas (nome, idade, porte, sexo, descrição e imagens).
- Atualização do status do pet (disponível, em processo de adoção, adotado).
- Exclusão de pets.

## Tecnologias Utilizadas

- **Frontend**: HTML5, CSS3, JavaScript (com Bootstrap para estilização).
- **Backend**: PHP com PDO para interação com o banco de dados.
- **Banco de Dados**: MySQL.
- **Bibliotecas e Ferramentas**:
  - [Chart.js](https://www.chartjs.org/) para gráficos no painel administrativo.
  - [noUiSlider](https://refreshless.com/nouislider/) para filtros interativos.
  - [Font Awesome](https://fontawesome.com/) para ícones.

## Estrutura do Projeto
adotaai/
 ├── admin/ # Funcionalidades administrativas 
 ├── assets/ # Arquivos estáticos (CSS, JS, imagens) 
 ├── includes/ # Arquivos de configuração e funções reutilizáveis 
 ├── uploads/ # Diretório para uploads de imagens 
 ├── usuario/ # Funcionalidades para usuários 
 ├── categorias.php # Gerenciamento de categorias 
 ├── database_setup.sql # Script para criação do banco de dados 
 ├── index.php # Página inicial 
 ├── login.php # Página de login 
 ├── registro.php # Página de registro 
 
 └── README.md # Documentação do projeto


## Configuração do Ambiente

### Pré-requisitos
- PHP 7.4 ou superior.
- Servidor MySQL.
- Servidor web (Apache ou Nginx).
- Composer (opcional, para gerenciar dependências).

### Passos para Configuração

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/adotaai.git
   cd adotaai

#### Configure o banco de dados:

Crie um banco de dados MySQL.
Importe o arquivo database_setup.sql para criar as tabelas necessárias.
Configure o arquivo includes/config.php:

u794393669_adotaai.sql (É o banco de dados criado no MySQL da hostinger)

Para usar em localhost:
- Altere o host do arquivo includes/config.php para 'localhost'.
- Certifique-se de que o arquivo database_setup.sql esteja no mesmo diretório que o arquivo includes/config.php.

copyright (c) 2025, Adotaai. All rights reserved.
## Licença
Este projeto foi desenvolvido por **Wellington Matozinhos** e está disponível ao uso geral
