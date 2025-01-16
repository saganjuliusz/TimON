<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$dataFile = 'timer_data.json';

function readData() {
    global $dataFile;
    if (file_exists($dataFile)) {
        return json_decode(file_get_contents($dataFile), true);
    }
    return [
        'isRunning' => false,
        'startTime' => 0,
        'elapsedTime' => 0,
        'splits' => []
    ];
}

function writeData($data) {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data));
}

$data = readData();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action'])) {
        switch ($input['action']) {
            case 'start':
                $data['isRunning'] = true;
                $data['startTime'] = microtime(true) * 1000;
                break;
            case 'stop':
                $data['isRunning'] = false;
                $data['elapsedTime'] += (microtime(true) * 1000) - $data['startTime'];
                $data['startTime'] = 0;
                break;
            case 'reset':
                $data['isRunning'] = false;
                $data['startTime'] = 0;
                $data['elapsedTime'] = 0;
                $data['splits'] = [];
                break;
            case 'split':
                $currentTime = $data['isRunning'] 
                    ? $data['elapsedTime'] + (microtime(true) * 1000) - $data['startTime']
                    : $data['elapsedTime'];
                $data['splits'][] = [
                    'count' => count($data['splits']) + 1,
                    'time' => $currentTime,
                    'name' => ''
                ];
                break;
            case 'editSplitName':
                if (isset($input['index']) && isset($input['newName'])) {
                    $data['splits'][$input['index']]['name'] = $input['newName'];
                }
                break;
        }
        writeData($data);
    }
}

echo json_encode($data);
?>