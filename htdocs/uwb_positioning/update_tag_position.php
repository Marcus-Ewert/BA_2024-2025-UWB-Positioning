<?php
require_once "conn.php";
require_once "helper_functions.php";

$debug = false;
$test = false;
$test_data = 
"t:123456
an2:4.32m
an3:8.90m
an1:20.83m
an4:10.50m
";

if (isset($_GET['test'])) {$test = true;}

if (isset($_GET['data']) || $test) {
    
    $data = $test? $test_data : $_GET['data'];
    debug_print_var($data, "data", $debug);
    debug_print_var(urlencode($data), "url-encoded data", $debug);

    $conn->query("INSERT INTO debug_messages (`message`) VALUES ('$data')");
    // split by newline characters, should only be "\r\n" 
    $data_split = preg_split("/\R/", $data);
    debug_print_var($data_split, "data_split", $debug);

    if ($data_split[count($data_split) - 1] === "") {
        unset($data_split[count($data_split) - 1]);
    }
    debug_print_var($data_split, "data_split", $debug);

    // The first line is "t:(milliseconds of arduino runtime)\r\n".
    preg_match('/t:(\d+)/', $data_split[0], $match);
    $arduino_time = $match[1];
    // The next ones are 1 or more measurements with up to 4 anchors with "an(num):xx.xxm\r\n", depending on available anchors for ranging.

    $last_anchor = 0;
    $measurement = [$arduino_time, "0", "0", "0", "0"];
    debug_print_var($measurement, "measurement", $debug);
    $prep = $conn->prepare("INSERT INTO `measurements`(`runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`) VALUES (?, ?, ?, ?, ?)");

    for ($i=1; $i < count($data_split); $i++) {
        
        $line = explode(':', $data_split[$i]); 
        preg_match('/an(\d)/', $line[0], $match);
        $anchor_number = (int) $match[1];
        // remove non-digits "12,34m" -> "1234"
        $distance = preg_replace('/[^\d]/i', '', $line[1]);

        // The anchors are listed in order, and the same or a lower anchor number appearing again means there's a new measurement
        // problem: first measurement has anchors 1 and 2, second has 3 and 4 -> indistinguishable
        if ($anchor_number <= $last_anchor) {
            debug_print_var($measurement, "send intermediary measurement", $debug);
            $prep->execute($measurement);
            $measurement = [$arduino_time, "0", "0", "0", "0"];
        }

        $measurement[$anchor_number] = $distance;
        
        if ($i + 1 == count($data_split) ) {
            debug_print_var($measurement, "send final measurement", $debug);
            $prep->execute($measurement);
        }
        
        $last_anchor = $anchor_number;
        
    }
    

}

?>