<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

function fetchCounts($pdo, $interval, $format, $limit, $step, $labelGenCb) {
    $sql = "SELECT DATE_FORMAT(visit_time, '$format') AS bucket, COUNT(*) AS c
            FROM visits
            WHERE visit_time > NOW() - INTERVAL $interval
            GROUP BY bucket";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_KEY_PAIR);
    $labels = [];
    $data = [];
    for ($i = 0; $i < $limit; $i++) {
        $label = $labelGenCb($i, $limit);
        $labels[] = $label;
        $data[] = isset($rows[$label]) ? (int)$rows[$label] : 0;
    }
    return ['labels' => $labels, 'data' => $data];
}

try {
    $seconds = fetchCounts(
        $pdo,
        '60 SECOND',
        '%H:%i:%s',
        60,
        1,
        function($i, $limit) {
            $t = time() - ($limit - 1 - $i);
            return date('H:i:s', $t);
        }
    );
    $minutes = fetchCounts(
        $pdo,
        '60 MINUTE',
        '%H:%i',
        60,
        1,
        function($i, $limit) {
            $t = time() - 60*($limit - 1 - $i);
            return date('H:i', $t);
        }
    );
    $hours = fetchCounts(
        $pdo,
        '24 HOUR',
        '%Y-%m-%d %H',
        24,
        1,
        function($i, $limit) {
            $t = time() - 3600*($limit - 1 - $i);
            return date('Y-m-d H', $t);
        }
    );
    $days = fetchCounts(
        $pdo,
        '30 DAY',
        '%Y-%m-%d',
        30,
        1,
        function($i, $limit) {
            $t = time() - 86400*($limit - 1 - $i);
            return date('Y-m-d', $t);
        }
    );
    $months = fetchCounts(
        $pdo,
        '12 MONTH',
        '%Y-%m',
        12,
        1,
        function($i, $limit) {
            $ts = strtotime(date('Y-m-01') . " -".(12 - 1 - $i)." month");
            return date('Y-m', $ts);
        }
    );
    $years = fetchCounts(
        $pdo,
        '5 YEAR',
        '%Y',
        5,
        1,
        function($i, $limit) {
            $ts = strtotime(date('Y-01-01') . " -".(5 - 1 - $i)." year");
            return date('Y', $ts);
        }
    );

    echo json_encode([
        'success' => true,
        'seconds' => $seconds,
        'minutes' => $minutes,
        'hours' => $hours,
        'days' => $days,
        'months' => $months,
        'years' => $years,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'server']);
}
