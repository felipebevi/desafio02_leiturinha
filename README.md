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

para evitar erros na linha, processarei a linha com try catch

logs de comandos em /instalacaolaravel.log

primeira execucao rodar as migration (pode ser automatizado no docker compose ou SH)
docker compose exec app php artisan migrate

fiz um SH para ver o banco do docker no console e faciliar o debug final
./conferir_banco.sh

--------------FIM--------------

DOC FINAL COM ANOTACOES

---
Você pode escolher:
● Como identificar arquivos já processados: os arquivos sao controlados por renomeacao garantindo que cada csv seja processado apenas uma vez
● Como evitar/resolver concorrência: o uso de renomeacao atomica impede que multiplas instancias processem o mesmo arquivo ao mesmo tempo
● Como registrar erros: os erros sao tratados e registrados por linha sem interromper o processamento do arquivo inteiro, alem dos logs nativos do laravel
● Como estruturar o código: o worker foi implementado como um command separado com responsabilidades claras e bem definidas por nao se misturar com interfaces ou outras frentes do projeto como um todo

Mas explique suas decisões no README.
acrescentando mais um detalhe, isso é um teste de desenvolvimento, varios pontos foram simplificados para melhor entrega e cumprimento dos objetivos principais.

---
:-) - Felipe Bevi - 20260128
---

