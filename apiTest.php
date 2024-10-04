<?php
// Include the common cURL file
require_once 'apiClient.php';

try {
    // Get all employees
    $employees = sendRequest('/api.php/employees', 'GET');
    $employees = json_decode($employees, true);
    echo $employees;
} catch (Exception $e) {
    // Handle any exceptions that occur during the API calls
}
