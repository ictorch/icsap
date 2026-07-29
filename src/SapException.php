<?php
namespace ictorch\icsap;
/**
 * @since 03-01-2023
 * @author Ignacio Cuadra
 */
class SapException extends \Exception
{
  private $input;
  private $output;
  private $request;
  public function __construct($message, $input, $output, $request = [])
  {
    parent::__construct($message, 0, null);
    $this->input = $input;
    $this->output = $output;
    $this->request = $request;
  }
  public function getJsonErrors()
  {
    $errors =
      [
        'miscellaneous' => [$this->message],
        'sapInput' => $this->input,
        'sapOutput' => $this->output
      ];
    if (!empty($this->request)) {
      $errors['sapRequest'] = $this->request;
    }
    return ['errors' => $errors];
  }
}
