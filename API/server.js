

const express = require('express');
const path = require('path');
const pool = require('./db.js');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, 'public_html')));

// Rota de login
app.post('/login', async (req, res) => {
  const { email, senha } = req.body;
  if (!email || !senha) {
    return res.status(400).json({ error: 'Email e senha obrigatórios.' });
  }
  try {
    const [rows] = await pool.execute(
      'SELECT * FROM usuario WHERE email = ? LIMIT 1',
      [email]
    );
    if (rows.length === 0 || rows[0].senha !== senha) {
      return res.status(401).json({ error: 'Usuário ou senha inválidos.' });
    }
    return res.json({ token: 'mock-token-123', nome: rows[0].nome });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Erro interno do servidor.' });
  }
});


app.listen(3000, () => {
  console.log('Servidor rodando na porta 3000');
});