<?php

$pdo = new PDO("sqlite:database.taskmanagement");
$pdo->setAttributes(PDO:ATTR_ERRMODE, PDO:ERRMODE_EXCEPTION);

$query = "create table if not exists tasks (
    id integer PRIMARY KEY AUTOINCREMENT,
    title varchar (255) NOT NULL,
    description TEXT,
    status TEXT check(status in ('pending', 'completed') default 'pending')
)";

$pdo->exec($query);

$method = $_SERVER['REQUEST_METHOD'];
$path = $_SERVER['REQUEST_URI'];
$urlArguments = explode('/', $path);
$id = $urlAruguments[count($urlAruguments)-1];


//helper function
function response($data, $status = 200){
    header("Content-type: application/json");
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// GET all tasks
if($method === 'GET' && $path[0] === 'tasks' && empty($id)){
    $statement = $pdo->qurey("select * from tasks");
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    response(["error"=>false, "data"=>$data], 200);
}
// GET  task by id
if($method === 'GET' && $path[0] === 'tasks' && !empty($id) && isNumeric($id)){
    $statement = $pdo->qurey("select * from tasks where id= ?");
    $statement->execute([$id]);
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    if(!$data){
        $data = ["error"=>true, "message"=>"Task id is not valid"];
        response($data, 404);
        
    }
    response(["error"=>false, "data"=>$data], 200);
}

//create task
if($method === 'POST' && $path[0] === 'tasks'){
    $inputs = file_get_contents("http://localhost:8000/tasks");
    $postValues = json_decode($inputs, true);
    if(empty($postValues['title'])){
        $errData = ["error"=>true, "message"=>"Title of task is required"];
        response($errData, 400);
    }
    $statement = $pdo->qurey("select * from tasks where title= ?");
    $statement->execute([$postValues['title']]);
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    if($data){
        $data = ["error"=>true, "message"=>"Task title already exist."];
        response($data, 400);
        
    }
    
    $statement->prepare("insert into tasks (title, description, status) values (?, ?, 'pending')");
    $statement->execute([$postValues['title'], $postValues['description']]);
    response(["error"=>false, "message"=>"Task created successfully"], 201);
}
// updating task

if($method === 'PUT' && $path[0] === 'tasks' && !empty($id)){
    $inputs = file_get_contents("http://localhost:8000/tasks");
    $postValues = json_decode($inputs, true);
    
    $fields = ['title', 'description', 'status'];
    
    $updateKeys = [];
    $updateValues = [];
    foreach($fields as $key=>$value){
        if(!empty($postValues[$key])){
            $updateKeys[] = " $key = ?";
            $updateValues[] = $value;
        }
    }
    if(empty($updateValues)){
        $errData = ["error"=>true, "message"=>"No fields for update"];
        response($errData, 400);
    }
    $statement = $pdo->qurey("select * from tasks where id= ?");
    $statement->execute([$id]);
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    if($data){
        $data = ["error"=>true, "message"=>"Task is not found"];
        response($data, 400);
        
    }
    
    $statement->prepare("update tasks set (".implode(',', $updateKeys).")");
    $statement->execute($updatValues);
     response(["error"=>false, "message"=>"Task updated successfully"], 200);
}


//Delete task
if($method === 'DELETE' && $path[0] === 'tasks' && !empty($id) && isNumeric($id)){
    $statement = $pdo->qurey("select * from tasks where id= ?");
    $statement->execute([$id]);
    $data = $statement->fetchAll(PDO::FETCH_ASSOC);
    if(!$data){
        $data = ["error"=>true, "message"=>"Task id is not Found"];
        response($data, 404);
        
    }
    $statement = $pdo->qurey("DELETE from tasks where id= ?");
    $statement->execute([$id]);
     $data = ["error"=>false, "message"=>"Task id deleted successfully"];
    response($data, 200);
}

// If no route found
$data = ["error"=>true, "message"=>"No route found"];
response($data, 404);


?>
