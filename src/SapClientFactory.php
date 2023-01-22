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
    if (is_null($port)) $port = getenv("ICSAP_PORT");
    if (is_null($database)) $database = getenv("ICSAP_DATABASE");
    if (is_null($username)) $username = getenv("ICSAP_USERNAME");
    if (is_null($password)) $password = getenv("ICSAP_PASSWORD");
    return new SapClient($host, $port, $database, $username, $password);
  }  
}