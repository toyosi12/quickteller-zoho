<?php
    $data = json_decode(file_get_contents('php://input'));


    $computedSignature = hash_hmac('sha256', $data->modParams, $data->payItemId);


    if ($computedSignature === $data->zohoSignature) {
        echo json_encode([
            'success' => true
        ]);
    } else {
        echo json_encode([
            'success' => false
        ]);
    }