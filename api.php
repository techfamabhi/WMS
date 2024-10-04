<?php

require('./apiService.php');
$apiService = new ApiService();
// Get the request method
$method = $_SERVER['REQUEST_METHOD'];
// Get the requested endpoint
$endpoint = $_SERVER['PATH_INFO'];
// Set the response content type
header('Content-Type: application/json');

$query_parameters = array();
$queryString=urldecode($_SERVER['QUERY_STRING']);
foreach (explode('&', $queryString) as $chunk) {
    $param = explode("=", $chunk);
    array_push($query_parameters, $param[1]);
}
    
$paths = explode('/', $endpoint);

switch ($method) {
    case 'GET':
        $getmethods = array_filter(get_class_methods($apiService), fn($x) => str_contains($x, 'get'));
        $methodName = $paths[array_key_last($paths)];

        if (in_array($methodName, $getmethods)) {
            $data = call_user_func_array(array($apiService, $methodName), $query_parameters);
            echo json_encode($data);
        } else
            return http_response_code(404);
        return;

    case 'POST':
        $postmethods = array_filter(get_class_methods($apiService), fn($x) => str_contains($x, 'set'));
        $methodName = $paths[array_key_last($paths)];
        if (in_array($methodName, $getmethods)) {
            $data = call_user_func_array(array($apiService, $methodName), $query_parameters);
            echo json_encode($data);
        } else
            return http_response_code(404);
        return;

    case 'PUT':
        $getmethods = array_filter(get_class_methods($apiService), fn($x) => str_contains($x, 'update'));
        $methodName = $paths[array_key_last($paths)];
        if (in_array($methodName, $getmethods)) {
            $data = call_user_func_array(array($apiService, $methodName), $query_parameters);
            echo json_encode($data);
        } else
            return http_response_code(404);
        return;

    case 'DELETE':
        $postmethods = array_filter(get_class_methods($apiService), fn($x) => str_contains($x, 'delete'));
        $methodName = $paths[array_key_last($paths)];
        if (in_array($methodName, $getmethods)) {
            $data = call_user_func_array(array($apiService, $methodName), $query_parameters);
            echo json_encode($data);
        } else
            return http_response_code(404);
        return;
}