# [icsap](https://github.com/ictorch/icsap) ![php version](https://img.shields.io/badge/php-%3E%3D7.4-blue) ![license](https://img.shields.io/github/license/ictorch/icsap)

library to consume sap webservices

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