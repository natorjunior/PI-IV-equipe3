# Acesso Seguro ao Banco de Dados - Projeto Peneirada

## 🔒 **PHPMyAdmin Removido por Segurança**

O PHPMyAdmin foi **desabilitado por padrão** para aumentar a segurança do projeto.

## 🛠️ **Alternativas para Gerenciar o Banco:**

### **1. Via Container MySQL (Recomendado)**
```bash
# Conectar ao MySQL diretamente
docker exec -it peneirada-mysql mysql -u peneirauser -pPeneiraUser2025!

# Ver databases
SHOW DATABASES;

# Usar o banco peneirada
USE peneirada;

# Ver tabelas
SHOW TABLES;

# Fazer queries
SELECT * FROM usuarios;
```

### **2. Via Cliente Externo**
Use ferramentas como:
- **MySQL Workbench**
- **DBeaver** 
- **HeidiSQL**

**Configuração:**
- **Host**: `localhost`
- **Porta**: `1520`
- **Usuário**: `peneirauser`
- **Senha**: `PeneiraUser2025!`
- **Database**: `peneirada`

### **3. Reativar PHPMyAdmin (Temporário)**

Se realmente precisar do PHPMyAdmin:

1. **Descomente** as linhas no `docker-compose.yml`:
   ```yaml
   phpmyadmin:
     image: phpmyadmin/phpmyadmin
     # ... resto da configuração
   ```

2. **Reinicie os containers**:
   ```bash
   docker compose up -d
   ```

3. **Acesse**: http://localhost:1522
   - **Usuário**: `peneirauser`
   - **Senha**: `PeneiraUser2025!`

4. **⚠️ IMPORTANTE**: Comente novamente após usar!

## 🔐 **Comandos Úteis MySQL:**

### **Backup**
```bash
docker exec peneirada-mysql mysqldump -u peneirauser -pPeneiraUser2025! peneirada > backup.sql
```

### **Restore**
```bash
docker exec -i peneirada-mysql mysql -u peneirauser -pPeneiraUser2025! peneirada < backup.sql
```

### **Ver dados das tabelas**
```sql
-- Usuários cadastrados
SELECT id, nome, email FROM usuarios;

-- Posts criados
SELECT p.id, p.content, u.nome FROM posts p JOIN usuarios u ON p.user_id = u.id;

-- Curtidas
SELECT l.id, u.nome, p.content FROM likes l 
JOIN usuarios u ON l.user_id = u.id 
JOIN posts p ON l.post_id = p.id;
```

## ✅ **Status Atual:**
- ✅ **MySQL**: Funcionando (porta 1520)
- ✅ **API PHP**: Funcionando (porta 1521)
- 🚫 **PHPMyAdmin**: Desabilitado por segurança
- ✅ **Sistema de Curtidas**: Totalmente funcional

**Acesse a aplicação**: http://localhost:1521