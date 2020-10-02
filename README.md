Connection Factory
=======

Helper system for Database connections and Querys.

There is also a inbuilt SQL Querry Builder.

Installation
------------

```bash
composer require amaranthnetwork/connectionfactory
```

Usage
-----

```php
<?php
use AmaranthNetwork\Database\ConnectionFactory;

//Return Single row from table
$q = ConnectionFactory::F()->C()->PQuery("SELECT * FROM some_table WHERE id = ?",$row_id)->result();
if($q){
    echo $q['name'];
}

//Loop All rows in table
$q = ConnectionFactory::F()->C()->PQuery("SELECT * FROM some_table WHERE id > ?",$row_id);
foreach($q->results() AS $row){
    echo $row['name'];
}

```

SQL Builder
```php
<?php
use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;
use AmaranthNetwork\Database\ConnectionFactory;

$builder = new SQLBuilder("dome_table", "", SQL_Type::UPDATE);
$builder->UpdateField("some_field", ":_some_field")->Bind(":_some_field",$some_field);
$builder->Where("id","=", ":_id")->Bind(":_id", (int)$field_id);
ConnectionFactory::F()->C()->ExecuteBuilder($builder);

```