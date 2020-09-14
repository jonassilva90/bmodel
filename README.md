# Bmodel
Binah Model

## Instalação

```sh
php composer require jonassilva/bmodel
```

## Configuração inicial - (Conexão com banco)

```php
<?php // Arquivo config.php

use Bmodel\Connection;

// Definir conexao com banco de dados
Connection::setConnection(
  'banco',   // Database
  null,           // Conexao ID
  'localhost',    // Hostname
  NULL,           // Port
  'usuario', // Username
  'senhaMuitoDificio'      // password
);

Connection::setModelPath('Model'); // pasta
```

## Exemplos de uso

### Trazer um registro(como objeto)

```php
<?php

use Bmodel\Query;

$objCliente = Query::getTable('cliente')->find($id);

if (!$objCliente) {
  throw new \Exception("Cliente não encontrado!", 404);
}
// Usar dados
echo $objCliente->id;
echo $objCliente->nome;
// $objCliente->toArray(); // como array associativo
// $objCliente->toArrayNum(); // como array (somente os valores)
// $objCliente->toJSON(); // como JSON

// Alterar:
$objCliente->nome = 'Joaquim';
$objCliente->save();

// Buscar por campo

// ... WHERE active = 1 AND email = 'joaquim@exemplo.com' LIMIT 1
$objCliente = Query::getTable('cliente')->findBy([
  'active' => '1',
  'email' => 'joaquim@exemplo.com'
]);

// Outras opcoes
// ... WHERE active = 1
// AND at_created > '2020-01-01 00:00:00'
// ORDER BY id DESC LIMIT 1
$objCliente = Query::getTable('cliente')
  ->where("active = 1")
  ->andWhere("at_created > '2020-01-01 00:00:00'")
  ->orderBy('id DESC')
  ->find();
```


### Trazer uma lista de resultados

```php
<?php

use Bmodel\Query;

$objCliente = Query::getTable('cliente')
  ->select(['id', 'at_created', 'nome', 'email'])
  ->where("active = 1")
  ->andWhere("at_created > '2020-01-01 00:00:00'")
  ->orderBy('id DESC')
  ->get();

// Usando JOINs (INNER, LEFT, RIGHT)

$objCliente = Query::getTable('cliente c')
  ->select(['c.id', 'c.at_created', 'c.nome', 'c.email'])
  ->innerJoin(
    'pedido p', // Table
    'c.id = p.cliente_id', // ON
    'Pedido')
  ->leftJoin(
    'pagamento_pedido pp',
    'p.id = pp.pedido_id',
    'Pedido.Pagamentos')
  ->innerJoin(
    'itens_pedido item',
    'p.id = item.pedido_id',
    'Pedido.Itens')
  ->where("c.active = 1")
  ->andWhere("c.at_created > '2020-01-01 00:00:00'")
  ->limit(10) // Podendo ser: ->page($page, $resultPerPage)
  ->orderBy('c.id DESC')
  ->get();

echo $objCliente->toJSON();
/*
// Output:
[
  {
    "id": 1,
    "at_created": "2020-01-30 12:53:54",
    "nome": "Joaquim",
    "email": "joaquim@exemplo.com",
    "Pedido": [{
      "id": 12,
      "at_created": "2020-01-30 12:53:54",
      "status": 1,
      ...
      Pagamentos: [],
      Itens: [
        {
          "id": 123,
          "descricao": "Laptop Evotion Master Da Galáxia",
          "valor_un": 9876.12
          ...
        }
      ]
    }]
  }
]
*/

```

### Insert/Update/Delete

```php
<?php

use Bmodel\Query;

// Exemplo de INSERT #1
$objCliente = Query::getTable('cliente')->create();
$objCliente->nome = "Maria";
$objCliente->email = "maria@exemplo.com";
$objCliente->save();

echo $objCliente->id; // Novo id

// Exemplo de INSERT #2
$objCliente = Query::getTable('cliente')->insert([
  'nome' => "Maria",
  "email" => "maria@exemplo.com"
]);

echo $objCliente->id; // Novo id

// Exemplo de UPDATE #1
$objCliente = Query::getTable('cliente')->find(1);
$objCliente->nome = "José";
$objCliente->email = "jose@exemplo.com";
$objCliente->save();

// Exemplo de UPDATE #2
$objCliente = Query::getTable('cliente')->update([
  'nome' => "José",
  "email" => "jose@exemplo.com"
]);

// Exemplo de DELETE #1
Query::getTable('cliente')->find(1)->delete();

// Exemplo de DELETE #2
Query::getTable('cliente')->findDelete(1);

// Exemplo de DELETE #3
Query::getTable('cliente')->where('active = 0')->delete();


```
