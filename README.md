# Projeto Peneirada - API Node.js

## Como rodar o projeto

1. Instale as dependências:
   ```
npm install
   ```
2. Inicie a API:
   ```
npm start
   ```

Acesse em seu navegador:
- Página inicial: [http://localhost:3000/](http://localhost:3000/)
- Página home: [http://localhost:3000/home](http://localhost:3000/home)

## Endpoints

- `GET /` → Serve a página inicial (`index.html`)
- `POST /login` → Recebe `{ "user": "...", "password": "..." }` e retorna `{ "token": "..." }` (mock)
- `GET /home` → Serve a página de boas-vindas (`home.html`)

## Observações
- Os arquivos HTML estão na pasta `public_html`.
- O endpoint `/login` retorna um token fixo para qualquer usuário/senha (MVP).
