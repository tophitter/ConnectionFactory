Connection Factory
=======

Helper system for Database connections and Querys.

There is also a inbuilt SQL Querry Builder.

Installation
------------

```bash
composer require amaranthnetwork/connectionfactory
```
___
# Usage
___

##PQuery
```php
<?php
use AmaranthNetwork\Database\ConnectionFactory;

$row_id = 1;

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

##LQuery
```php
<?php
use AmaranthNetwork\Database\ConnectionFactory;

$row_id = 1;

//Return Single row from table
$q = ConnectionFactory::F()->C()->LQuery("SELECT * FROM some_table WHERE id = :id",array(
"id"=>$row_id
))->result();
if($q){
    echo $q['name'];
}

//Loop All rows in table
$q = ConnectionFactory::F()->C()->LQuery("SELECT * FROM some_table WHERE id > :id",array(
"id"=>$row_id
));
foreach($q->results() AS $row){
    echo $row['name'];
}
```
___
##SQL Builder
___
###SQL Builder INSERT_ON_DUPLICATE
####Usage
```php
use AmaranthNetwork\Database\Builder\SQL_FUNCTION;use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;

$some_field="SomeData";
$some_field2="SomeData2";

$cols = array(
    array('name'=>'some_field', 'binds'=> $some_field),
    array('name'=>'some_field2', 'binds'=> $some_field2),   
);

$builder->Columns($cols);
ConnectionFactory::F()->C()->ExecuteBuilder($builder);
```

###SQL Builder Update
####Usage
```php
use AmaranthNetwork\Database\Builder\SQL_FUNCTION;use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;

$some_field="SomeData";

$builder = new SQLBuilder("dome_table", "", SQL_Type::UPDATE);

//Bind the Value to the field this will auto name bind to the field name ':_some_field'
$builder->UpdateFieldBind("some_field",$some_field);

//Bind by SQL Function this will bind the field to have the value now()
$builder->UpdateFieldFunc("some_date_field", SQL_FUNCTION::DATE_NOW);

//Default bind, bind by name so we can add the data later 
$builder->UpdateField("some_field", ":_some_field");
//Bind the data 
$builder->Bind(":_some_field", $some_field);
```
####Example
```php
<?php
use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;
use AmaranthNetwork\Database\ConnectionFactory;

$field_id = 1;
$some_field="SomeData";

//Define update fields separate to binds
$builder = new SQLBuilder("dome_table", "", SQL_Type::UPDATE);
$builder->UpdateField("some_field", ":_some_field")->Bind(":_some_field",$some_field);
$builder->Where("id","=", ":_id")->Bind(":_id", (int)$field_id);
ConnectionFactory::F()->C()->ExecuteBuilder($builder);

//Define update fields with binds
$builder = new SQLBuilder("dome_table", "", SQL_Type::UPDATE);
$builder->UpdateFieldBind("some_field", $some_field);
$builder->Where("id","=", ":_id")->Bind(":_id", (int)$field_id);
ConnectionFactory::F()->C()->ExecuteBuilder($builder);

```

###SQL Builder INSERT
####Usage
```php
use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;

$some_field="SomeData";

//Define columns and Binds
$builder = new SQLBuilder("some_table", "", SQL_Type::INSERT);

//Define Single Column to insert to 
$builder->Column("some_field"); 

//Define selection Columns to inset to
$builder->Columns(
    array(
        array('name'=>'some_field'),
        array('name'=>'some_field2'),
        array('name'=>'some_field3'),
    )
);

//Define single col bind data (with bind key so you ca bind later
$builder->Value("some_field",":_some_field");

//Define single col bind data and bind data from field name
$builder->ValueBind("some_field",$some_field);

//Define the binda data as array
$builder->Values(
    array(
        array('name'=>':_some_field', 'binds'=>$some_field, 'function'=> null),
    )
);

```
####Example
```php
<?php
use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;
use AmaranthNetwork\Database\ConnectionFactory;

$some_field="SomeData";

//Define columns and Binds
$builder = new SQLBuilder("some_table", "", SQL_Type::INSERT);
$builder->Columns(
    array(
        array('name'=>'some_field'),
    )
);
$builder->Values(
    array(
        array('name'=>':_some_field', 'binds'=>$some_field, 'function'=> null),
    )
);
ConnectionFactory::F()->C()->ExecuteBuilder($builder);

//Define and Bind 
$builder = new SQLBuilder("some_table", "", SQL_Type::INSERT);
$builder->Columns(
    array(
        array('name'=>'some_field', 'binds'=>$some_field),
    )
);

ConnectionFactory::F()->C()->ExecuteBuilder($builder);
```

###SQL Builder SELECT
####Usage

```php
<?php
use AmaranthNetwork\Database\Builder\SQL_SORT_TYPE;use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;
use AmaranthNetwork\Database\ConnectionFactory;

$builder = new SQLBuilder("some_table", "", SQL_Type::SELECT);

//Define Single Column to select from 
$builder->Column("some_field"); 

//Define all Column to select from 
$builder->Column("*");

//Define selection Columns to collect from 
$builder->Columns(
    array(
        array('name'=>'some_field'),
        array('name'=>'some_field2'),
        array('name'=>'some_field3'),
    )
);

//Define where
$builder->Where("some_field", "=", ":_some_field");

//Append to Where with the AND before the new where
$builder->WhereAnd("some_field2", "=", ":_some_field2");

//Append to Where with the OR before the new where
$builder->WhereOR("some_field2", "=", ":_some_field2");

//Add Where with Between function 
$builder->WhereBetween("some_field2", ":_between1",":_between2");

//Append to Where with the AND Between function 
$builder->WhereAndBetween("some_field2", ":_between1",":_between2");

//Append to Where with the OR Between function 
$builder->WhereOrBetween("some_field2", ":_between1",":_between2");

//Append LEFT Bracket to where
$builder->WhereBracket('l');
//Append Right Bracket to where
$builder->WhereBracket('r');
//Append LEFT Bracket to where and Add AND before bracket
$builder->WhereBracket('la');
//Append LEFT Bracket to where and Add AND after bracket
$builder->WhereBracket('ra');
//Append LEFT Bracket to where and Add OR before bracket
$builder->WhereBracket('lo');
//Append LEFT Bracket to where and Add OR after bracket
$builder->WhereBracket('ro');


//Limit to 10 reuslts
$builder->Limit(10);

//Limit 10 but get the next 10 of the 10 set
$builder->Limit(10,100);

//Sort Results
$builder->OrderBy("soem_field", SQL_SORT_TYPE::ASC);
```
####Exmaple
```php
<?php
use AmaranthNetwork\Database\Builder\SQL_Type;
use AmaranthNetwork\Database\Builder\SQLBuilder;
use AmaranthNetwork\Database\ConnectionFactory;

$field_id = 1;

$builder = new SQLBuilder("some_table", "", SQL_Type::SELECT);
$builder->Columns(
    array(
        array('name' => "some_field", "table_alias" => ""),
    )
);
$builder->Where("id","=",":_id")->Bind(":_id", $field_id);

$q = ConnectionFactory::F()->C()->ExecuteBuilder($builder);
foreach ($q->results() AS $row){
    //Loop Row data
}

//if you need to use brackets in the where it will look liek this 
$builder->WhereBracket("l");
{
    $builder->Where("field_1", "=", ":_field_1");
    $builder->WhereBracket("la");
    {
        $builder->Where("field_2", "=", ":_field_2");
        $builder->WhereAnd("field_3", "=", ":_field_3");
        $builder->WhereBracket("lo");
        {
            $builder->Where("field_4", "=", ":_field_4");
            $builder->WhereOR("field_5", "=", ":_field_5");
            
        }
        $builder->WhereBracket("r");
    }
    $builder->WhereBracket("r");
}
$builder->WhereBracket("r");

//will output 
$sql = "SELECT * FROM some_table WHERE ( field_1=:_field_1 AND ( field_2 = :_field_2 AND field_3 = :_field_3) OR ( field_4 = :_field_4 OR field_5 = :_field_5 ) )"


```