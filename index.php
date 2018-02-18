<?php
require_once __DIR__ . '/vendor/autoload.php';

$klein = new \Klein\Klein();


$klein->respond(function ($request, $response, $service, $app) use ($klein) {
    // Handle exceptions => flash the message and redirect to the referrer
    $klein->onError(function ($klein, $err_msg) {
        $klein->response()->code(400);
        $klein->response()->json(
            ['error'=> $err_msg]
        );
    });

    $app->register('db', function() {
        return new PDO('mysql:dbname=boom;host=mysql','root','root');
    });
});

//Store an address
$klein->respond('POST', '/address', function($request, $response, $service, $app) {
    $service->validateParam('name', 'Please enter a valid Name')->isLen(2, 255);
    $service->validateParam('address', 'Please enter a address')->isLen(2, 255);
    $service->validateParam('city', 'Please enter city')->isLen(2, 255);
    $service->validateParam('state', 'Please enter a state')->isLen(2, 255);
    $service->validateParam('postal_code', 'Please enter a postal code')->isLen(2, 255);
    $service->validateParam('country', 'Please enter a country')->isLen(2, 255);
    
    $params = $request->paramsPost();
    $insert = $app->db->prepare('
    INSERT INTO address(name, address, city, state, country, postal_code)
    VALUES(?,?,?,?,?,?)
    ');
    $results = $insert->execute([
        $params['name'],
        $params['address'],
        $params['city'],
        $params['state'],
        $params['country'],
        $params['postal_code']
    ]);
    if($results){
        $response->json(
            [
                'success'=>'Address Saved',
                'id'=>$app->db->lastInsertId()
            ]
        );
    }
});

//Return an address
$klein->respond('GET','/address/[i:id]', function($request, $response, $service, $app){
    $id = $request->param('id');
    $query = $app->db->prepare('SELECT * from address where id = ?');
    $query->execute([$id]);
    $result = $query->fetch(PDO::FETCH_OBJ);
    $response->json($result);
});

//Search for an address
$klein->respond('GET','/address/search', function($request,$response,$service,$app){
 
    $results = [];
    $name = $request->param('name');
    $postal_code = $request->param('postal_code');
    if( !empty( $name ) ){
        $query = $app->db->prepare('SELECT * from address where name like ?');
        $query->execute(['%'.$name.'%']);
        while($row = $query->fetch(PDO::FETCH_OBJ)){
            $results[] = $row;
        }
    }
    if( !empty($postal_code)){
        $query = $app->db->prepare('SELECT * from address where postal_code = ?');
        $query->execute([$postal_code]);
        while($row = $query->fetch(PDO::FETCH_OBJ)){
            $results[] = $row;
        }
    }

    $response->json($results);

});

$klein->dispatch();