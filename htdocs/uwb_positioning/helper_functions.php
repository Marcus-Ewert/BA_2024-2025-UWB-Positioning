<?php
function debug_print_var($var, $name, $debug) {
    if (!$debug) {
        return;
    }
    echo "<br>$name: ";
    print_r($var);
    echo "<br>";
}
?>