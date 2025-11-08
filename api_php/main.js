// main.js - funções utilitárias para login, registro e feed
async function postJson(url, data){
  const res = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data) });
  return await res.json();
}
