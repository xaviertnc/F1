<?php namespace F1;

/**
 * 
 * F1 - HTTP Class
 * 
 * @author  C. Moller <xavier.tnc@gmail.com>
 * 
 * @version 1.1.0 - 08 Jul 2022
 *
 */

class HTTP {

  public $baseUri;
  public $req;


  public function __construct( $baseUri )
  {
    $this->baseUri = trim( $baseUri, '/' );
    $req = new \stdClass();
    $req->uri = $_SERVER[ 'REQUEST_URI' ];
    $req->path = $this->getRequestPath( $req->uri );
    $req->segments = $req->path ? explode( '/', $req->path ) : [];
    $req->method = $_SERVER[ 'REQUEST_METHOD' ];
    $req->data = $_REQUEST;
    $this->req = $req;
  }


  public function getRequestPath( $requestUri )
  {
    return trim( $this->baseUri ? str_replace( $this->baseUri, '', $requestUri )
      : $requestUri, '/' );
  }


  public function get( $param, $default = null )
  {
    return isset( $_REQUEST[ $param ] ) ? $_REQUEST[ $param ] : $default;
  }

}