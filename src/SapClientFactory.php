<?php
namespace ictorch\icsap;
/**
 * @since 20-11-2022
 * @author Ignacio Cuadra
 */
class SapClientFactory
{
  public function __invoke(
    $host,
    $port,
    $database,
    $username,
    $password,
    $ssl = true,
    $language = null
  ) {
    $missing = [];
    if (empty($host)) {
      $missing[] = 'host';
    }
    if (empty($port)) {
      $missing[] = 'port';
    }
    if (empty($database)) {
      $missing[] = 'database';
    }
    if (empty($username)) {
      $missing[] = 'username';
    }
    if (empty($password)) {
      $missing[] = 'password';
    }

    if (!empty($missing)) {
      $message = 'Missing required configuration: ' . implode(', ', $missing);
      $input = [
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'username' => $username,
        'password' => $password,
        'ssl' => $ssl,
        'language' => $language
      ];
      throw new SapException($message, $input, []);
    }

    return new SapClient($host, $port, $database, $username, $password, $ssl, $language);
  }
}
