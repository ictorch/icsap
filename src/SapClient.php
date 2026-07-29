<?php
namespace ictorch\icsap;
define('HTTP_GET', 'GET');
define('HTTP_POST', 'POST');
define('HTTP_PATCH', 'PATCH');
define('HTTP_DELETE', 'DELETE');
/**
 * @since 20-11-2022
 * @author Ignacio Cuadra
 */
class SapClient
{

  private $ssl;
  private $host;
  private $port;

  private $username;
  private $password;
  private $database;
  private $language;

  private $sessionId = null;
  private $routeId = null;
  private $fixedRouteId = null;
  private $sessionTimeout;
  private $loggedInAt;
  private $sessionExpireAt;
  private $lastRequest = [];

  public function __construct($host, $port, $database, $username, $password, $ssl = true, $language = null)
  {
    $this->host = $host;
    $this->port = $port;
    $this->username = $username;
    $this->password = $password;
    $this->database = $database;
    $this->ssl = $ssl;
    $this->language = $language;
  }

  private function login()
  {
    // A new SAP session must not inherit the route of a previous session.
    $this->routeId = $this->fixedRouteId;
    $loginBody = [
      'UserName' => $this->username,
      'Password' => $this->password,
      'CompanyDB' => $this->database
    ];
    if (!is_null($this->language)) {
      $loginBody["Language"] = $this->language;
    }
    $response = $this->curl('Login', HTTP_POST, $loginBody);
    if (empty($response['SessionId'])) {
      throw new SapException("Login response does not contain a SessionId", $loginBody, $response, $this->lastRequest);
    }
    $this->sessionId = $response['SessionId'];
    $this->sessionTimeout = isset($response['SessionTimeout']) ? (int) $response['SessionTimeout'] : 0;
    $this->loggedInAt = time();
    $this->sessionExpireAt = $this->loggedInAt + ($this->sessionTimeout * 60);
  }

  /**
   * Stores the load-balancer route returned by SAP so subsequent requests are
   * sent to the same Service Layer node as the login request.
   */
  private function captureRouteId($headerLine)
  {
    if (!is_null($this->fixedRouteId)) {
      return;
    }
    if (preg_match('/^Set-Cookie:\\s*ROUTEID=([^;\\r\\n]*)/i', $headerLine, $matches)) {
      $this->routeId = trim($matches[1], " \\t\\n\\r\\0\\x0B\\\"");
    }
  }

  /**
   * Forces all authenticated requests to use a specific SAP Service Layer
   * route. Pass null to resume using the route returned by SAP at login.
   */
  public function setFixedRouteId($routeId)
  {
    $this->fixedRouteId = is_null($routeId) || $routeId === '' ? null : (string) $routeId;
    $this->routeId = $this->fixedRouteId;
    // A session established on a different route must not be reused.
    $this->sessionId = null;
    $this->sessionExpireAt = null;
    return $this;
  }

  /**
   * Uses the ROUTEID issued by SAP in the next Login response. This is the
   * default behavior and keeps the session pinned to SAP's selected node.
   */
  public function useLoginRouteId()
  {
    return $this->setFixedRouteId(null);
  }

  private function curl($action, $method, $params = [], $header = [])
  {
    $host = $this->host;
    $port = $this->port;
    $sessionId = $this->sessionId;
    $curl = curl_init();
    $ssl = $this->ssl ? 'https' : 'http';
    $url = "$ssl://$host:$port/b1s/v1/$action";
    $this->lastRequest = [
      'url' => $url,
      'method' => $method
    ];
    curl_setopt($curl, CURLOPT_URL, $url);
    $customHeader = [];
    if (count($header) > 0) {
      foreach ($header as $key => $value) {
        array_push($customHeader, "$key: $value");
      }
    }
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $headerLine) {
      $this->captureRouteId($headerLine);
      return strlen($headerLine);
    });
    if ($this->ssl) {
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
        $body = is_array($params) && empty($params) ? new \stdClass() : $params;
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        break;
      case HTTP_PATCH:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_PATCH);
        $body = is_array($params) && empty($params) ? new \stdClass() : $params;
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        break;
      case HTTP_DELETE:
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, HTTP_DELETE);
        break;
    }
    if (!is_null($sessionId)) {
      $cookie = "B1SESSION=$sessionId";
      if (!is_null($this->routeId) && $this->routeId !== '') {
        $cookie .= "; ROUTEID={$this->routeId}";
      }
      array_push($customHeader, "Cookie: $cookie");
      array_push($customHeader, "Expect:");
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $customHeader);
    $result = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $this->lastRequest['httpStatus'] = $httpCode;
    if ($result === false) {
      $curlError = curl_error($curl);
      $this->lastRequest['curlErrorCode'] = curl_errno($curl);
      curl_close($curl);
      throw new SapException("Error at processing response", $params, $curlError, $this->lastRequest);
    }
    if (($method == HTTP_PATCH || $method == HTTP_DELETE) && ($result == "" || $result == null) && $httpCode >= 200 && $httpCode < 300) {
      $result = "[]";
    }
    $response = json_decode($result, true);
    curl_close($curl);
    if (is_null($response)) {
      throw new SapException("Error at processing response", $params, $result, $this->lastRequest);
    }
    if (array_key_exists('error', $response)) {
      throw new SapException("Response error", $params, $response, $this->lastRequest);
    }
    return $response;
  }

  private function isSessionExpired($aditionalTime = 1)
  {
    return (time() + $aditionalTime) > $this->sessionExpireAt;
  }

  public function fetch($action, $method, $params = [], $header = [])
  {
    if (!is_null($this->sessionId) && $this->isSessionExpired()) {
      $this->sessionId = null;
    }
    if (is_null($this->sessionId)) {
      $this->login();
    }
    return $this->curl($action, $method, $params, $header);
  }

  public function get($action, $params = [], $header = [])
  {
    return $this->fetch($action, HTTP_GET, $params, $header);
  }

  public function post($action, $params = [], $header = [])
  {
    return $this->fetch($action, HTTP_POST, $params, $header);
  }

  public function patch($action, $params = [], $header = [])
  {
    return $this->fetch($action, HTTP_PATCH, $params, $header);
  }

  public function delete($action, $params = [], $header = [])
  {
    return $this->fetch($action, HTTP_DELETE, $params, $header);
  }

}
