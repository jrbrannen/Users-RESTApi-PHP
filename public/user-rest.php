<?php
require_once('../inc/User.class.php');

// create a new user
$user = new User();

$userArray = array();

// load the user if user is in the database
if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] > 0) {
    $user->load($_REQUEST['user_id']);
    $userArray = $user->userArray;
}

// splice the array to remove the password element from the array
array_splice($userArray, 2, 1);

// convert to json
echo json_encode($userArray);

?>