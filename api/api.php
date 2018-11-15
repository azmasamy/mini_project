<?php
header('Content-Type: application/json');

require_once 'database_credentials.php';
require_once 'database_functions.php';

$headers = getallheaders();
$token = $headers['token'];


if($token !== 'FEBB222BFE78A') {
  http_response_code(401);
  $response['status'] = array("code"=>"401","message"=>"Unauthorized access");
  $response['data'] = array();
  echo json_encode($response);
  die();
}



$conn = db_connect();

if (!$conn) {
  http_response_code(503);
  $response['status'] = array("code"=>"503","message"=>"Database connention failed");
  $response['data'] = mysqli_connect_error();
  echo json_encode($response);
  die();
}

switch ($_SERVER['REQUEST_METHOD']) {

  case 'GET':{

    $all_contacts_info = array();

    $sql = "SELECT * FROM contact;";

    $contacts_resutl = mysqli_query($conn, $sql);

    if($contacts_resutl) {

      if(mysqli_num_rows($contacts_resutl) == 0) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"No contacts found");
        $response['data'] = array();
        die(json_encode($response));
      }

      while ($contact_row = mysqli_fetch_assoc($contacts_resutl)) {

        $contact_info = array();

        $contact_info['id'] = $contact_row['id'];
        $contact_info['first_name'] = $contact_row['first_name'];
        $contact_info['last_name'] = $contact_row['last_name'];

        $sql = "SELECT * FROM phone_numbers WHERE contact_id = '{$contact_info['id']}';";

        $contact_numbers = mysqli_query($conn, $sql);

        if($contact_numbers) {
          while ($numbers_row = mysqli_fetch_assoc($contact_numbers)) {

            $contact_info['phone'][] = ['number' => $numbers_row['phone_number'], 'title' => $numbers_row['phone_title'], 'default' => $numbers_row['default_num']];
        }
      } else {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
        $response['data'] = array();
        die(json_encode($response));
      }
      $all_contacts_info[] = $contact_info;
    }

    http_response_code(200);
    $response['status'] = array("code"=>"200","message"=> "Retrieved successfully");
    $response['data'] = $all_contacts_info;
    die(json_encode($response));

  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }

  $conn -> close();

} break;

case 'VIEW':{

  $body = json_decode(file_get_contents('php://input', true), true);

  if ($body) {

    $id = $body["id"];

    if(empty($id)) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to Retrieve, empty contact id");
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=>"Failed to update, Empty body or not in jason format");
    $response['data'] = array();
    echo json_encode($response);
    die();
  }

  $contacts_info = array();

  $sql = "SELECT * FROM contact WHERE id = '{$id}';";

  if(mysqli_query($conn, $sql)){
    $contact = mysqli_query($conn, $sql)->fetch_assoc();

    if(empty($contact['id'])) {
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

    } else {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }


  $conn -> close();



} break;

case 'PUT': {

  $body = json_decode(file_get_contents('php://input', true), true);

  if ($body) {

    $contact_id = $body["id"];
    $first_name = $body["first_name"];
    $last_name = $body['last_name'];
    $numbers = array();
    $titles = array();
    $default = array();
    foreach ($body['phone'] as $contact_phone) {
      $numbers[] = $contact_phone['number'];
      $titles[] = $contact_phone['title'];
      $defaults[] = $contact_phone['default'];
    }
    foreach ($numbers as $index => $number) {
      if($number == "") {
        unset($numbers[$index]);
      }
    }
    foreach ($titles as $index => $title) {
      if($title == "") {
        unset($phone_titles[$index]);
      }
    }
    foreach ($defaults as $index => $default) {
      if($default == "") {
        unset($default_numbers[$index]);
      }
    }




    if(empty($contact_id) || empty($first_name) || empty($last_name) || empty($numbers) || empty($titles) || empty($defaults) || count($numbers) != count($titles) || count($numbers) != count($defaults)) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to update, Empty id, first name, last name, phone number, phone title, default number or all");
      $response['data'] = array();
      echo json_encode($response);
      die();
    }
    //making sure that numbers are unique
    if(count($numbers) != count(array_unique($numbers))) {
      http_response_code(400);
      $response['status'] = array("code"=>"404","message"=>"Failed to update, dublicated numbers");
      $response['data'] = array();
      die(json_encode($response));
    }
    //making sure that numbers are not too long
    foreach ($numbers as $number) {
      if(strlen($number) > 11) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Failed to update, " . $number . " is too long (11 digits max)");
        $response['data'] = array();
        die(json_encode($response));
      }
    }
    if(count($numbers) != count($titles) || count($numbers) != count($defaults)) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to update, Every phone number must have phone titles and default number");
      $response['data'] = array();
      echo json_encode($response);
      die();
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=>"Failed to update, Empty body or not in jason format");
    $response['data'] = array();
    echo json_encode($response);
    die();
  }

  $sql = "SELECT * FROM contact WHERE id = '{$contact_id}';";

  //check to see if contact exists in the database;
  if(mysqli_query($conn, $sql)){
    $contact = mysqli_query($conn, $sql)->fetch_assoc();
    if(empty($contact['id'])) {
      http_response_code(404);
      $response['status'] = array("code"=>"404","message"=>"Failed to update, Contact not found");
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }

  //check if first_name and last_name already exist in the database
  $sql = "SELECT * FROM contact WHERE first_name = '{$first_name}' AND last_name = '{$last_name}';";
  $result = mysqli_query($conn, $sql);
  if($result){
    if(mysqli_num_rows($result) == 1) {
      $row = $result->fetch_assoc();
      if($row['id'] != $contact_id) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Failed to update, ~" . $first_name . " " . $last_name . "~ already exists in the database");
        $response['data'] = array();
        die(json_encode($response));
      }
    } else if(mysqli_num_rows($result) > 1) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to update, Database Error");
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }
  //-------------------------------------------------------------

  //check if one of the numbers already exists in the database
  foreach ($numbers as $number) {
    $sql = "SELECT * FROM phone_numbers WHERE phone_number = '{$number}';";

    $result = mysqli_query($conn, $sql);

    if($result){
      if(mysqli_num_rows($result) == 1) {
        $row = $result->fetch_assoc();
        if($row['contact_id'] != $contact_id) {
          http_response_code(400);
          $response['status'] = array("code"=>"400","message"=>"Failed to update, " . $number . " already exists in the database");
          $response['data'] = array();
          die(json_encode($response));
        }
      } else if(mysqli_num_rows($result) > 1) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Failed to update, Database Error");
        $response['data'] = array();
        die(json_encode($response));
      }
    } else {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
      $response['data'] = array();
      die(json_encode($response));
    }
  }
  //-----------------------------------------------------------------------


  $sql = "UPDATE contact SET first_name = '{$first_name}', last_name = '{$last_name}' WHERE id = '{$contact_id}';";
  if(mysqli_query($conn, $sql)){
    $sql = "DELETE FROM phone_numbers WHERE contact_id = '{$contact_id}';";

    if(mysqli_query($conn, $sql)) {
      foreach ($numbers as $index => $number) {

        $sql = "INSERT INTO phone_numbers (phone_title, phone_number, default_num, contact_id) VALUES ('{$titles[$index]}', '{$numbers[$index]}', '{$defaults[$index]}', '{$contact_id}');";

        if(!mysqli_query($conn, $sql)) {
          die(json_encode(respose_sql_error($conn)));
        }
      }
    } else {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }

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
    $numbers = array();
    $titles = array();
    $default = array();
    foreach ($body['phone'] as $contact_phone) {
      $numbers[] = $contact_phone['number'];
      $titles[] = $contact_phone['title'];
      $defaults[] = $contact_phone['default'];
    }


    foreach ($numbers as $index => $number) {
      if($number == "") {
        unset($numbers[$index]);
      }
    }
    foreach ($titles as $index => $title) {
      if($title == "") {
        unset($phone_titles[$index]);
      }
    }
    foreach ($defaults as $index => $default) {
      if($default == "") {
        unset($default_numbers[$index]);
      }
    }





    if(empty($first_name) || empty($last_name) || empty($numbers) || empty($titles) || empty($defaults) || count($numbers) != count($titles) || count($numbers) != count($defaults)) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to insert,, Empty first name, last name, phone number, phone title, default number or all");
      $response['data'] = array();
      echo json_encode($response);
      die();
    }
    //making sure that numbers are unique
    if(count($numbers) != count(array_unique($numbers))) {
      http_response_code(400);
      $response['status'] = array("code"=>"404","message"=>"Failed to insert, dublicated numbers");
      $response['data'] = array();
      die(json_encode($response));
    }
    //making sure that numbers are not too long
    foreach ($numbers as $number) {
      if(strlen($number) > 11) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Failed to insert, " . $number . " is too long (11 digits max)");
        $response['data'] = array();
        die(json_encode($response));
      }
    }

    if(count($numbers) != count($titles) || count($numbers) != count($defaults)) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to insert, Every phone number must have phone titles and default number");
      $response['data'] = array();
      echo json_encode($response);
      die();
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=>"Failed to insert, Empty body or not in jason format");
    $response['data'] = array();
    echo json_encode($response);
    die();
  }

  //check if first_name and last_name already exist in the database
  $sql = "SELECT * FROM contact WHERE first_name = '{$first_name}' AND last_name = '{$last_name}';";
  $result = mysqli_query($conn, $sql);
  if($result){
    if(mysqli_num_rows($result) > 0) {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=>"Failed to insert, ~" . $first_name . " " . $last_name . "~ already exists in the database");
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }
  //-------------------------------------------------------------

  //check if one of the numbers already exists in the database
  foreach ($numbers as $number) {
    $sql = "SELECT * FROM phone_numbers WHERE phone_number = '{$number}';";
    $result = mysqli_query($conn, $sql);
    if($result){
      if(mysqli_num_rows($result) > 0) {
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=>"Failed to insert, " . $number . " already exists in the database");
        $response['data'] = array();
        die(json_encode($response));
      }
    } else {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
      $response['data'] = array();
      die(json_encode($response));
    }
  }
  //----------------------------------------------------------

  $sql = "INSERT INTO contact (first_name, last_name) VALUES ('{$first_name}', '{$last_name}');";

  if(mysqli_query($conn, $sql)){

    $contact_id = mysqli_insert_id($conn);

    foreach ($numbers as $index => $number) {

      $sql = "INSERT INTO phone_numbers (phone_title, phone_number, default_num, contact_id) VALUES ('{$titles[$index]}', '{$numbers[$index]}', '{$defaults[$index]}', '{$contact_id}');";

      if(!mysqli_query($conn, $sql)){
        http_response_code(400);
        $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
        $response['data'] = array();
        die(json_encode($response));
      }
    }

    http_response_code(200);
    $response['status'] = array("code"=>"200","message"=>"Inserted successfully");
    $response['data'] = $body;
    echo json_encode($response);

  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }

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

  $sql = "SELECT * FROM contact WHERE id = '{$contact_id}';";

  if(mysqli_query($conn, $sql)){
    $contact = mysqli_query($conn, $sql)->fetch_assoc();

    if(empty($contact['id'])) {
      http_response_code(404);
      $response['status'] = array("code"=>"404","message"=>"Contact not found");
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }


  $sql = "DELETE FROM phone_numbers WHERE contact_id = '{$contact_id}';";

  if(mysqli_query($conn, $sql)){

    $sql = "DELETE FROM contact WHERE id = '{$contact_id}';";

    if(mysqli_query($conn, $sql)) {
      http_response_code(200);
      $response['status'] = array("code"=>"200","message"=>"Deleted successfully");
      $response['data'] = $body;
      echo json_encode($response);

    } else {
      http_response_code(400);
      $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
      $response['data'] = array();
      die(json_encode($response));
    }
  } else {
    http_response_code(400);
    $response['status'] = array("code"=>"400","message"=> respose_sql_error($conn));
    $response['data'] = array();
    die(json_encode($response));
  }



  $conn -> close();
} break;

default: {
  $response['status'] = array("code"=>"405","message"=>"Wrong request method");
  $response['data'] = array();;
  echo json_encode($response);

}
}


?>
