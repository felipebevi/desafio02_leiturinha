#!/bin/bash

docker compose exec -T db mysql -u root -proot <<EOF
USE importador_de_produto;
select "----------------------";
SELECT COUNT(*) as QTDE_TOTAL_DE_PRODUTOS FROM products;
select "----------------------";
SELECT * FROM products LIMIT 10;
select "----------------------";
EOF
