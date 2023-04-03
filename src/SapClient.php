<?php
namespace ictorch\icsap;
define('HTTP_GET', 'GET');
define('HTTP_POST', 'POST');
define('HTTP_PATCH', 'PATCH');
define('HTTP_DELETE', 'PUT');
/**
 * @since 20-11-2022
 * @author Ignacio Cuadra
 */
class SapClient {

  private $ssl;
  private $host;
  private $port;

  private $username;
  private $password;
  private $database;
  private $language;

  private $sessionId = null;
  private $sessionTimeout;
  private $loggedInAt;
  private $sessionExpireAt;

  public function __construct($ssl, $host, $port, $database, $username, $password, $language = null)
  {
    $this->ssl = $ssl;
    $this->host = $host;
    $this->port = $port;
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    $this->language = $language;
  }

  private function login() {
    $loginBody = [
      'UserName' => $this->username,
      'Password' => $this->password,
      'CompanyDB' => $this->database
    ];
    if (!is_null($this->language)) {
      $loginBody["Language"] = $this->language;
    }
    $response = $this->curl('Login', HTTP_POST, $loginBody);
    $this->sessionId = $response['SessionId'];
    $this->sessionTimeout = $response['SessionTimeout'];
    $this->loggedInAt = time();
    $this->sessionExpireAt = $this->loggedInAt + ($this->sessionTimeout * 60);
  }

  private function curl($action, $method, $params = [], $header = []) {
    $host = $this->host;
    $port = $this->port;
    $sessionId = $this->sessionId;
    $curl = curl_init();
    $ssl = $this->ssl? 'https' : 'http';
    curl_setopt($curl, CURLOPT_URL, "$ssl://$host:$port/b1s/v1/$action");
    $customHeader = [];
    if (count($header) > 0) {
      foreach ($header as $key => $value) {
        array_push($customHeader, "$key: $value");
      }
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if($this->ssl) {
      curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    }
    //curl_setopt($curl, CURLOPT_VERBOSE, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 15); 
    curl_setopt($curl, CURLOPT_TIMEOUT, 400);
    switch ($method) { // by default is GET
      case HTTP_GET:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_GET);
        break;
      case HTTP_POST:
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        break;
      case HTTP_PATCH:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_PATCH);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        break;
      case HTTP_DELETE:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_DELETE);
        break;
    }
    if (!is_null($sessionId)) {
      array_push($customHeader, "Cookie: B1SESSION=$sessionId");
      array_push($customHeader, "Expect:");
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $customHeader);
    $result = curl_exec($curl);
    if ($method == HTTP_PATCH && ($result == "" || $result == null)) {
      $result = "[]";
    }
    $response = json_decode($result, true);
    curl_close($curl);
    if (is_null($response)) {
      throw new SapException("Error at processing response", $params, $result);
    }
    if (array_key_exists('error', $response)) {
      throw new SapException("Response error", $params, $response);
    }
    return $response;
  }

  private function isSessionExpired($aditionalTime = 1) {
    return (time() + $aditionalTime) > $this->sessionExpireAt;
  }

  public function fetch($action, $method, $params = [],  $header = []) {
    if (!is_null($this->sessionId) && $this->isSessionExpired()) {
      $this->sessionId = null;
    }
    if (is_null($this->sessionId)) {
      $this->login();
    }
    return $this->curl($action, $method, $params, $header);
  }

}