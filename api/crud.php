<?php
header('Content-Type: application/json');

function db_connect() {
  $servername = "localhost";
  $dbusername 	= "root";
  $dbpassword 	= "machine1";
  $dbname 		= "contacts";

  $conn = mysqli_connect($servername, $dbusername, $dbpassword, $dbname);
  return $conn;
}

function respose_sql_error($conn)  {
  http_response_code(503);
  $response['status'] = array("code"=>"503","message"=>"SQL error");
  $response['data'] = mysqli_error($conn);
  return $response;
}

$conn = db_connect();

if (!$conn) {
  http_response_code(503);
  $response['status'] = array("code"=>"503","message"=>"Database connention failed");
  $response['data'] = mysqli_connect_error();
  echo json_encode($response);
  die();
}

$headers = getallheaders();
$token = $headers['token'];

if(empty($token) || $token !== 'FEBB222BFE78A') {
  http_response_code(401);
  $response['status'] = array("code"=>"401","message"=>"Unauthorized access");
  $response['data'] = array();
  echo json_encode($response);
  die();
}

switch ($_SERVER['REQUEST_METHOD']) {

  case 'GET':{

    $id = $_GET['id'];


      if(empty($id)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Empty ID");
        $response['data'] = array();
        die(json_encode($response));
      }

    $contacts_info = array();

    $sql = "SELECT * FROM contact WHERE id = '{$id}';";

    if(mysqli_query($conn, $sql)){
      $contact = mysqli_query($conn, $sql)->fetch_assoc();

      if($contact['id'] == NULL) {
        http_response_code(404);
        $response['status'] = array("code"=>"404","message"=>"Contact not found");
        $response['data'] = array();
        die(json_encode($response));
      }

      $sql = "SELECT * FROM phone_numbers WHERE contact_id = '{$id}';";
      $phone_result = mysqli_query($conn, $sql);

      if($phone_result){
        $all_contact_info = array();
        while($phones = $phone_result->fetch_assoc()) {
          $all_contact_info[] = array('phone_title' => $phones['phone_title'], 'phone_number' => $phones['phone_number'], 'default_num' => $phones['default_num']);
        }

        $data = array('id' => $contact['id'], 'first_name' => $contact['first_name'], 'last_name' => $contact['last_name'], 'contact_info' => $all_contact_info);

        http_response_code(200);
        $response['status'] = array("code"=>"200","message"=>"Retrieved successfully");
        $response['data'] = $data;
        echo json_encode($response);

      } else { die(json_encode(respose_sql_error($conn))); }
    } else { die(json_encode(respose_sql_error($conn))); }


    $conn -> close();



  } break;

  case 'PUT': {

    $body = json_decode(file_get_contents('php://input', true), true);

    if ($body) {

      $contact_id = $body["id"];
      $first_name = $body["first_name"];
      $last_name = $body['last_name'];
      $numbers = $body['numbers'];
      $phone_titles = $body['phone_title'];
      $default_numbers = $body['default_numbers'];

      if(empty($contact_id) || empty($first_name) || empty($last_name) || empty($numbers) || empty($phone_titles) || empty($default_numbers)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Empty ID, first name, last name, phone number, phone title, default number or all");
        $response['data'] = array();
        die(json_encode($response));
      }
      if(count($numbers) != count($phone_titles) || count($numbers) != count($default_numbers)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Every phone number must have phone titles and default number");
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

    $sql = "SELECT * FROM contact WHERE id = '{$id}';";

    if(mysqli_query($conn, $sql)){
      $contact = mysqli_query($conn, $sql)->fetch_assoc();

      if(empty($contact['id'])) {
        http_response_code(404);
        $response['status'] = array("code"=>"404","message"=>"Contact not found");
        $response['data'] = array();
        die(json_encode($response));
      }
    } else { die(json_encode(respose_sql_error($conn))); }

    $sql = "UPDATE contact SET first_name = '{$first_name}', last_name = '{$last_name}' WHERE id = '{$contact_id}';";

    if(mysqli_query($conn, $sql)){
      $sql = "DELETE FROM phone_numbers WHERE contact_id = '{$contact_id}';";

      if(mysqli_query($conn, $sql)) {
        foreach ($numbers as $index => $number) {

          $phone_title = $phone_titles[$index];
          $default_number = $default_numbers[$index];

          $sql = "INSERT INTO phone_numbers (phone_title, phone_number, default_num, contact_id) VALUES ('{$phone_title}', '{$number}', {$default_number}, {$contact_id});";

          if(!mysqli_query($conn, $sql)) {
            die(json_encode(respose_sql_error($conn)));
          }
        }
      } else { die(json_encode(respose_sql_error($conn))); }
    } else { die(json_encode(respose_sql_error($conn))); }

    http_response_code(200);
    $response['status'] = array("code"=>"200","message"=>"Updated successfully");
    $response['data'] = $body;
    echo json_encode($response);

    $conn -> close();

  }  break;

  case 'POST': {

    $body = json_decode(file_get_contents('php://input', true), true);

    if ($body) {

      $first_name = $body["first_name"];
      $last_name = $body['last_name'];
      $numbers = $body['numbers'];
      $phone_titles = $body['phone_title'];
      $default_numbers = $body['default_numbers'];

      if(empty($first_name) || empty($last_name) || empty($numbers) || empty($phone_titles) || empty($default_numbers)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Empty first name, last name, phone number, phone title, default number or all");
        $response['data'] = array();
        echo json_encode($response);
        die();
      }
      if(count($numbers) != count($phone_titles) || count($numbers) != count($default_numbers)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Every phone number must have phone titles and default number");
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

    $sql = "INSERT INTO contact (first_name, last_name) VALUES ('{$first_name}', '{$last_name}');";

    if(mysqli_query($conn, $sql)){

      $contactID = mysqli_insert_id($conn);

      foreach ($numbers as $index => $number) {

        $phone_title = $phone_titles[$index];
        $default_number = $default_numbers[$index];

        $sql = "INSERT INTO phone_numbers (phone_title, phone_number, default_num, contact_id) VALUES ('{$phone_title}', '{$number}', {$default_number}, {$contactID});";

        if(!mysqli_query($conn, $sql)){
          die(json_encode(respose_sql_error($conn)));
        }
      }
    } else {die(json_encode(respose_sql_error($conn)));}

    http_response_code(200);
    $response['status'] = array("code"=>"200","message"=>"Inserted successfully");
    $response['data'] = $body;
    echo json_encode($response);

    $conn -> close();

  }  break;

  case 'DELETE':{

    $body = json_decode(file_get_contents('php://input', true), true);

    if ($body) {

      $contact_id = $body["id"];

      if(empty($contact_id)) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Empty ID");
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

    $sql = "SELECT * FROM contact WHERE id = '{$id}';";

    if(mysqli_query($conn, $sql)){
      $contact = mysqli_query($conn, $sql)->fetch_assoc();

      if(empty($contact['id'])) {
        http_response_code(404);
        $response['status'] = array("code"=>"404","message"=>"Contact not found");
        $response['data'] = array();
        die(json_encode($response));
      }
    } else { die(json_encode(respose_sql_error($conn))); }


    $sql = "DELETE FROM phone_numbers WHERE contact_id = '{$contact_id}';";

    if(mysqli_query($conn, $sql)){

      $sql = "DELETE FROM contact WHERE id = '{$contact_id}';";

      if(mysqli_query($conn, $sql)) {
        http_response_code(200);
        $response['status'] = array("code"=>"200","message"=>"Deleted successfully");
        $response['data'] = $body;
        echo json_encode($response);

      } else { die(json_encode(respose_sql_error($conn))); }
    } else { die(json_encode(respose_sql_error($conn))); }



    $conn -> close();
  } break;

  default: {
    $response['status'] = array("code"=>"405","message"=>"Wrong request method");
    $response['data'] = array();;
    echo json_encode($response);

  }
}


?>
