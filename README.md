# importador_de_produto

Serviço interno para importação automática de produtos a partir de arquivos CSV

Este README será incrementado ao longo do desenvolvimento

---

## Stack

- PHP 8.3
- Laravel
- MySQL 8
- Docker / Docker Compose

---

## Containers

- `importador_de_produto_app` → aplicação Laravel
- `importador_de_produto_db` → banco MySQL

Aplicação disponível em:
http://localhost:8080


---

## Subindo o projeto

```bash
docker compose up -d
```

## Estrategia para importação ordenada (indempotencia e concorrencia)

vou renomear o arquivo .csv para .csv.processing, apos o processamento ele volta pra .csv.error se der erro, ou .csv.done se der sucesso

para evitar erros na linh, processarei a linha com try catch

logs de comandos em /instalacaolaravel.log


