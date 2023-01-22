<?php
namespace ictorch\icsap;
/**
 * @since 03-01-2023
 * @author Ignacio Cuadra
 */
class SapException extends \Exception {
  private $input;
  private $output;
  public function __construct($message, $input, $output) {
    parent::__construct($message, 0, null);
    $this->input = $input;
    $this->output = $output;
  }
  public function getJsonErrors() {
    return 
    ["errors" => 
      [
        'miscellaneous' => [$this->message],
        'sapInput' => $this->input,
        'sapOutput' => $this->output
      ]
    ];
  }
}