<?php
namespace ictorch\icsap;
/**
 * @since 20-11-2022
 * @author Ignacio Cuadra
 */
class SapClientFactory {
  public function __invoke(
    $host = null, 
    $port = null, 
    $database = null, 
    $username = null, 
    $password = null
  ) {
    if (is_null($host)) $host = getenv("ICSAP_HOST");
    if (is_null($port)) $port = getenv("ICSAP_HOST");
    if (is_null($database)) $database = getenv("ICSAP_HOST");
    if (is_null($username)) $username = getenv("ICSAP_HOST");
    if (is_null($password)) $password = getenv("ICSAP_HOST");
    return new SapClient($host, $port, $database, $username, $password);
  }  
}