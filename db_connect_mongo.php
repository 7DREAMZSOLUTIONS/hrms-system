<?php
// db_connect_mongo.php

define('API_BASE_URL', 'https://hrms-system-if2q.onrender.com/api');

function callApi($endpoint, $method = 'GET', $data = null)
{
    $url = API_BASE_URL . $endpoint;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    if ($data) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);

    if ($response === false) {
        curl_close($ch);
        return null;
    }

    curl_close($ch);
    return json_decode($response, true);
}
