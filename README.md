# ictorch/icsap

Librería PHP para consumir servicios web de SAP (SAP RFC/HTTP clients wrapper).

## Requisitos

- PHP >= 7.4
- Composer

## Instalación

Instalar mediante Composer:

```bash
composer require ictorch/icsap
```

## Uso

La fábrica `SapClientFactory` crea instancias de `SapClient`.
Debe proporcionar los parámetros obligatorios: `host`, `port`, `database`, `username` y `password`.
Si falta alguno, la fábrica lanzará una `SapException` indicando los campos faltantes.

Ejemplo básico:

```php
use ictorch\icsap\SapClientFactory;

$factory = new SapClientFactory();
try {
  $client = $factory('sap.example.com', 3300, 'MYDB', 'user', 'secret');
  // usar $client...
} catch (\ictorch\icsap\SapException $e) {
  // manejar error de configuración
  $errors = $e->getJsonErrors();
  print_r($errors);
}
```

## Notas

- `SapException` recibe mensaje, entrada y salida relacionadas con el error. Para validación de parámetros, la entrada contiene los valores suministrados.
- Esta librería sólo proporciona el envoltorio; configure su entorno SAP y dependencias según sea necesario.

## Licencia

MIT

# [icsap](https://github.com/ictorch/icsap) ![php version](https://img.shields.io/badge/php-%3E%3D7.4-blue) ![license](https://img.shields.io/github/license/ictorch/icsap)

library to consume sap service layer

## Usage

```php
// get data form sap
$sapClient = (new SapClientFactory())();
$query = http_build_query([
  '$select'=>"CardCode,CardName",
  '$filter'=>"CardType eq 'cSupplier'"
  ]);
  $response = $this->sapClient->fetch("BusinessPartners?$query", HTTP_GET, [], ["Prefer" => "odata.maxpagesize=100"]);
```

```php
// put data to sap
$sapClient = (new SapClientFactory())();
try {
  $response = $this->sapClient->fetch("Items", HTTP_POST, [
    "ItemCode" => "i001",
    "ItemName" => "Item1",
    "ItemType" => "itItem"
  ]);
} catch (\SapException $e) {
  print_r($e->getJsonErrors());
}
```
