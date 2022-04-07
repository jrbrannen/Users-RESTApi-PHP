<?php
require_once('../inc/User.class.php');

// new user object
$user = new User();

// retrieve a list of users (getListFiltered function)
$userList = $user->getListFiltered(
    (isset($_GET['sortColumn']) ? $GET['sortColumn'] : null),
    (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : null),
    (isset($_GET['filterColumn']) ? $_GET['filterColumn'] : null),
    (isset($_GET['filterText']) ? $_GET['filterText'] : null)
);

// splice each user array stored in the $userList array 
// to remove the password element from each user
for($i=0; $i < count($userList); $i++){
    array_splice($userList[$i], 2, 1);
}

// var_dump($userList); 

// convert to json
echo json_encode($userList);
?>