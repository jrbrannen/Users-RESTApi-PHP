<?php

class User {
    var $userArray = array();

    var $errors = array();

    var $db = null;

    // connect to the db when a new user object is created
    function __construct() {
        $this->db = new PDO('mysql:host=localhost;dbname=wdv441;charset=utf8', 
            'user', 'wdv441');
    }
    
    // uses a session variable id to see if a user is logged in or not
    function checkLogin($userId) {
        $loggedIn = false;

        if (!empty($userId)) {
            $loggedIn = true;
        }
        return $loggedIn;
    }// end of checkLogin()

    // uses a session variable as a paramenter to check a users level
    function isAdminLevel($userLevel) {
        $isAdmin = false;

        if(!empty($userLevel) && $userLevel == "admin") {
            $isAdmin = true;
        }
        return $isAdmin;
    }// end of isAdminLevel()

    // get a list of users as an array   
    function getList() {
        $userList = array();

        // load from the database with a prepared statement
        $stmt = $this->db->prepare("SELECT * FROM users");

        // execute the statement
        $stmt->execute(array());
         
        // check row count and store users in an array
        if ($stmt->rowCount() > 0){
            // if a user is loaded fetch the data as an assoc array
            $userList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }       
        // return the list of users
        return $userList;       
    }// end of getList()

    function getListFiltered(
		$searchColumn = null, 
		$searchFor = null, 
		$sortColumn = null, 
		$sortDirection = null,
		$page = null,
        $howManyPerPage = null
	    ) {
		
		$dataList = array();
		$searchColumn = filter_var($searchColumn, FILTER_SANITIZE_STRING);
		$sortColumn = filter_var($sortColumn, FILTER_SANITIZE_STRING);
		$sortDirection = filter_var($sortDirection, FILTER_SANITIZE_STRING);
		$page = filter_var($page, FILTER_SANITIZE_NUMBER_INT);
		$howManyPerPage = filter_var($howManyPerPage, FILTER_SANITIZE_NUMBER_INT);

		$sql = "SELECT * FROM users";
		// check we received search parameters
		if (!is_null($searchColumn) && !empty($searchColumn) && !is_null($searchFor) && !empty($searchFor)) {
			$sql .= " WHERE " . $searchColumn . " LIKE ? ";
		}

		if (!is_null($sortColumn) && !empty($sortColumn) && !is_null($sortDirection) && !empty($sortDirection)) {
			$sql .= " ORDER BY " . $sortColumn . " " . $sortDirection;
		}
		
		// setup paging if passed
		//$how_many_on_page = 3;
		
		if (!is_null($page) && is_numeric($page)) {
			$sql .= " LIMIT " . (($howManyPerPage * $page - 1) - 1) . ", " . $howManyPerPage;
		}
        // var_dump($sql); die;
		$stmt = $this->db->prepare($sql);
        // $stmt->execute();
        // var_dump($searchFor); die;
		$stmt->execute((is_null($searchFor) || empty($searchFor) ? null : array(
			'%' . $searchFor . '%'
		)));
		
        if ($stmt->rowCount() > 0) {
            $dataList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }		
		
		return $dataList;
	}// end of getListFiltered()

    function load($id){

        // tracking flag to see if the user was loaded 
        $isLoaded = false;

        // load from the database with a prepared statement
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");

        // execute the statement using the id as parameter for the user we want to load
        $stmt->execute(array($id));

        // check to see if the user was sucessfully loaded
        if ($stmt->rowCount() == 1){
            // if user is loaded fetch the data as an assoc array
            $userArray = $stmt->fetch(PDO::FETCH_ASSOC);
            // set the user data to internal class property
            $this->set($userArray);

            // set the flag to be true
            $isLoaded = true;
        }

        // return load success or failure
        return $isLoaded;
    }// end of load()

    function passTheSalt($password) {
        // password salt
        define("PASSWORD_SALT", "C0dingP#P!sS0S@MuchF()N");
        // hash password and store in variable
        $password = hash("sha256", $password . PASSWORD_SALT);
        return $password;
    }

    function save() {
        // flag to see if save is successful 
        $isSaved = false;

        // salt the new or updated password
        $this->userArray['password'] = $this->passTheSalt($this->userArray['password']);
        
        // determine if save is an insert or an update based of on the user id
        // true condition save data from the (userArray) property to the database
        if (empty($this->userArray['user_id'])){
            
            // create a prepared statement to insert the data into the table
            $stmt = $this->db->prepare(
                "INSERT INTO users
                    (user_name, password, user_level, user_first_name, user_last_name)
                VALUES (?, ?, ?, ?, ?)");

            // execute the insert statement, passing the data into the insert
            $isSaved = $stmt->execute(array(
                    $this->userArray['user_name'],
                    $this->userArray['password'],
                    $this->userArray['user_level'],
                    $this->userArray['user_first_name'],
                    $this->userArray['user_last_name']
                )
            );

            // if the execute returns true, then store the new id back into the data property
            // this gets the newly assigned id number and stores it into the (userArray) property
            if ($isSaved){
                $this->userArray['user_id'] = $this->db->lastInsertId();
            }
        }else{
            // if this is an update of an existing record, create a prepare statement using a sql update
            $stmt = $this->db->prepare(
                "UPDATE users SET
                    user_name = ?,
                    password = ?,
                    user_level = ?,
                    user_first_name = ?,
                    user_last_name = ?
                WHERE user_id = ?"
            );

            // execute the update statement, passing the data into the update
            $isSaved = $stmt->execute(array(
                    $this->userArray['user_name'],
                    $this->userArray['password'],
                    $this->userArray['user_level'],
                    $this->userArray['user_first_name'],
                    $this->userArray['user_last_name'],
                    $this->userArray['user_id']
                )
            );
        }
        // return success flag
        return $isSaved;
    }// end of save()

    function saveImage($fileArray) {
		
		// var_dump($fileArray);die;
		
		if (isset($fileArray['upload_image'])) {
            // var_dump($fileArray);die;
			move_uploaded_file($fileArray['upload_image']['tmp_name'], __DIR__ . 
                '/../public/images/' . $this->userArray['user_id'] . '_user.jpg');
		}
	}// end of saveImage()

    function set($userArray) {
        $this->userArray = $userArray;
        // $this->userArray['password'] = $this->passTheSalt($this->userArray['password']);
    }// end of set()

    function sanitize($userArray) {
        if (!empty($userArray['user_name'])){
            $userArray['user_name'] = filter_var($userArray['user_name'], FILTER_SANITIZE_STRING);
        }
        if (!empty($userArray['password'])) {
            $userArray['password']= filter_var($userArray['password'], FILTER_SANITIZE_STRING);
        } 
        return $userArray;
    }// end of sanitize()

    function validate() {
        $isValid = true;

        if (empty($this->userArray['user_name'])) {
            $this->errors['user_name'] = "User name is required";
            $isValid = false;
        }
        if (empty($this->userArray['password'])) {
            $this->errors['password'] = "Password is required";
            $isValid = false;
        }
        return $isValid;
    }// end of validate()

    function verifyUser($userName, $password) {
        // set verifiedUser to false, flag tracks to see if user data is loaded
        $verifiedUser = false;

        // get data from the database where user id and password match
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_name = ? and password = ?");

        // execute the statment using the userName and password as parameters
        $stmt->execute(array($userName, $password));

        // check to see if a user was sucessfully loaded
        if ($stmt->rowCount() == 1) {
            $userArray = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->set($userArray);
            $verifiedUser = true;
        }else{
            $this->errors['invalid'] = "User name or password is incorrect";
        }

        return $verifiedUser;
    }// end of verifyUser()
}

?>