const mysql = require('mysql2/promise');

const pool = mysql.createPool({
  host: 'localhost',
  port: 3307, // Altere para a porta definida no docker-compose.yml
  user: 'peneirauser',
  password: 'peneira123',
  database: 'peneirada',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  timezone: 'America/Fortaleza',
  charset: 'utf8mb4_unicode_ci'
});

module.exports = pool;
