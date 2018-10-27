<?php
header('Content-Type: application/json');

function isJson($string) {
  json_decode($string);
  return (json_last_error() == JSON_ERROR_NONE);
}



function db_connect() {
  $servername = "localhost";
  $dbusername 	= "root";
  $dbpassword 	= "";
  $dbname 		= "contacts";

  $conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
  return $conn;
}

////////////////////////////////////////////////////////////////////////////////////


$username = '';
$password = '';
$token = '';
$data = array();
$data = json_decode(file_get_contents('php://input', true), true);

if ($data) {

  $username = $data["username"];
  $password = $data['password'];
  $headers = getallheaders();
  $token = $headers['token'];


  if(empty($username) || empty($password)) {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=>"Empty username, password or both");
    $response['data'] = array();
    echo json_encode($response);
    die();
  }
  if(empty($token) || $token !== 'FEBB222BFE78A') {
    http_response_code(401);
    $response['status'] = array("code"=>"401","message"=>"Unauthorized access");
    $response['data'] = array();
    echo json_encode($response);
    die();
  }
} else {
  http_response_code(400);
  $response['status'] = array("code"=>"400","message"=>"Empty body or not in jason format");
  $response['data'] = array();
  echo json_encode($response);
  die();
}

$conn = db_connect();
if(!$conn) {
  http_response_code(503);
  $response['status'] = array("code"=>"503","message"=>"Can't connect to database");
  $response['data'];
  echo json_decode($response);
  die ();
} else { //Authunticate
  $sql = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
  $con_results = mysqli_query($conn, $sql);
  $userdata = mysqli_fetch_assoc($con_results);

  if(empty($userdata)) {

    $sql = "SELECT * FROM users WHERE username = '{$username}'";
    $con_results = mysqli_query($conn, $sql);
    $userdata = mysqli_fetch_assoc($con_results);

    if(empty($userdata)){
      http_response_code(401);
      $response['status'] = array("code"=>"401","message"=>"User not found");
      $response['data'] = array();
      echo json_encode($response);
    } else {
      http_response_code(401);
      $response['status'] = array("code"=>"401","message"=>"User credentials are not correct");
      $response['data'] = array();
      echo json_encode($response);
    }
  } else {
    http_response_code(200);
    $response['status'] = array("code"=>"200","message"=>"User found");
    $response['data'] = $userdata;
    echo json_encode($response);
  }

}

$conn -> close();



?>
