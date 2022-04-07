<?php
session_start();

require_once('../inc/User.class.php');
// create a user object
$user = new User();

// check to see if a user_id is stored in the session array, 
// if so assign it to user id var and assign user level var
if(isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userLevel = $_SESSION['user_level'];
}

// if($user->checkLogin($userId)) {
//     $userArray = array();
//     $errorsArray = array();
// }
if($user->isAdminLevel($userLevel)){
    $userList = array();

    if (isset($_GET['download']) && $_GET['download'] == "1") {

        // echo the data
        $userList = $user->getListFiltered(
            (isset($_GET['filterColumn']) ? $_GET['filterColumn'] : null),
            (isset($_GET['filterText']) ? $_GET['filterText'] : null),
            (isset($_GET['sortColumn']) ? $_GET['sortColumn'] : null),
            (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : null)
        );

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="user_report_' . date("YmdHis") . '.csv"');
        
        foreach ($userList as $rowData) {
            echo '"' . implode('","', $rowData) . '"';
            echo "\r\n";
        }
        
        exit;
    }

    // check to see if button was click
    if (isset($_GET['btnViewReport'])) {
        
        // run report
        $userList = $user->getListFiltered(
            (isset($_GET['filterColumn']) ? $_GET['filterColumn'] : null),
            (isset($_GET['filterText']) ? $_GET['filterText'] : null),
            (isset($_GET['sortColumn']) ? $_GET['sortColumn'] : null),
            (isset($_GET['sortDirection']) ? $_GET['sortDirection'] : null),
            (isset($_GET['page']) ? $_GET['page'] : 1),
            ($_GET['howManyPerPage'] = 2)
        );
    }
    //var_dump($_SERVER["QUERY_STRING"], $_GET);die;

    if(count($userList) != 0){
        // get total number of rows from query
        $totalReturnedRows = count($user->getListFiltered());

        // get total of rows viewed per page
        $rowsPerPage = $_GET['howManyPerPage'];

        // get last page num to display by dividing total rows by rows per page 
        // then round up and make num an int
        $lastPage = intval(ceil($totalReturnedRows / $rowsPerPage));

        // store last page num needed in $_GET array
        $_GET['lastPage'] = $lastPage;
        
    }
    
    // variable arrays for page links
    $nextPageGET = $_GET;
    $previousPageGET = $_GET;
    
    // sets up page value for next page is null or not null
    $nextPageGET['page'] = (isset($nextPageGET['page']) ? $nextPageGET['page'] + 1 : 2);
    $nextPageLink = http_build_query($nextPageGET);

    // sets up page value for previous page if null or not null
    $previousPageGET['page'] = (isset($previousPageGET['page']) ? $previousPageGET['page'] - 1 : 1);
    $previousPageLink = http_build_query($previousPageGET);

    include('../tpl/user-report.tpl.php');
}else{
    $user->errors = "Invalid User";
    header('location: index.php');
    exit;
}
?>