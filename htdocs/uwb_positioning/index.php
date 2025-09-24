<?php
require_once "conn.php";



$anchor_positons = [];
$max_x = 0;
$max_y = 0;
$sql = 'SELECT * FROM anchor_setups WHERE id = 1';
$res = $conn->query($sql)->fetch(PDO::FETCH_ASSOC);
$anchor_positons = [
    1 => [$res['anchor_1_x'], $res['anchor_1_y'], $res['anchor_1_z']],
    2 => [$res['anchor_2_x'], $res['anchor_2_y'], $res['anchor_2_z']],
    3 => [$res['anchor_3_x'], $res['anchor_3_y'], $res['anchor_3_z']],
    4 => [$res['anchor_4_x'], $res['anchor_4_y'], $res['anchor_4_z']]
];

$max_x = max([$max_x, $res['anchor_1_x'], $res['anchor_2_x'], $res['anchor_3_x'], $res['anchor_4_x']]);
$max_y = max([$max_y, $res['anchor_1_y'], $res['anchor_2_y'], $res['anchor_3_y'], $res['anchor_4_y']]);

$sql = 'SELECT id FROM anchor_setups';
$anchor_setups = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
// var_dump($anchor_setups);

$saved_sessions = $conn->query("SELECT id FROM sessions")->fetchAll(PDO::FETCH_ASSOC);


?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UWB Positioning</title>
    <link rel="stylesheet" href="style.css">
    <script src="multilateration.js"></script>
    <script src="script.js"></script>
    <!-- <meta http-equiv="refresh" content="10"> -->
</head>
<body>
    <header>
        <h1>UWB Positioning</h1>
        <p>A dashboard for a UWB Positioning demonstration</p>
    </header>

<main>
    <div class="row">
        <div class="anchor">
            <div id='anchor_settings' class="block">
                <table id="anchor_table">
                    <thead>
                        <th>Anchor ID</th>
                        <th>X</th>
                        <th>Y</th>
                        <th>Z</th>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($anchor_positons as $anchor_id => $val) {
                        ?>
                            <tr data-anchor-id="<?= $anchor_id ?>">
                                <td><?= $anchor_id ?></td>
                                <td>
                                    <input
                                    type="number"
                                    class="anchor-position"
                                    onchange="updateAnchorPosition(this)"
                                    data-anchor-id="<?= $anchor_id ?>"
                                    data-axis="x"
                                    autocomplete="off"
                                    value="<?= $val[0] ?>">
                                    </input> 
                                </td>
                                <td>
                                    <input
                                    type="number"
                                    class="anchor-position"
                                    onchange="updateAnchorPosition(this)"
                                    data-anchor-id="<?= $anchor_id ?>"
                                    data-axis="y"
                                    autocomplete="off"
                                    value="<?= $val[1] ?>">
                                    </input>
                                </td>
                                <td>
                                    <input
                                    type="number"
                                    class="anchor-position"
                                    data-anchor-id="<?= $anchor_id ?>"
                                    data-axis="z"
                                    autocomplete="off"
                                    value="<?= $val[2] ?>">
                                    </input>
                                </td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
                <div style="display: inline-block;">
                    <p>x max = <span id="max-x"><?= $max_x ?></span></p>
                    <p>y max = <span id="max-y"><?= $max_y ?></span></p>
                    <button type="button" onclick="saveAnchorSetup()">Save Anchor Setup</button>
                    <br>
                    <label>
                        select existing setup:
                        <select name="anchor-setup-selection" id="anchor-setup-selection" onchange="loadAnchorSetup(this)" autocomplete="off">
                            <?php
                            foreach ($anchor_setups as $setup) {
                            ?>
                                <option value="<?= $setup['id'] ?>"><?= $setup['id'] ?></option>
                            <?php
                            }
                            ?>
                        </select>
                    </label>
                </div>
                <div style="display: inline-block; padding: 1rem">
                    <p>Visualisation: Origin is in the top left. Grid lines 1m.</p>
                    <p>calculated Position:</p>
                    <p>X: <span id="position_x"></span>, Y: <span id="position_y"></span>, Z: <span id="position_z"></span></p>
                </div>
            </div> <!-- anchor_settings -->
        </div> <!-- anchor -->
        <div class="screen">
            <div class="block">
            <div class="svg-container">
                    <svg
                    id="positioning_display"
                    viewBox="-5 -5 <?= $max_x + 10 ?> <?= $max_y + 10 ?>"
                    preserveaspectratio="xMaxYMax"
                    xmlns="http://www.w3.org/2000/svg">
                        <?php
                        foreach ($anchor_positons as $anchor_id => $val) {
                        ?>
                            <circle 
                            id="anchor_<?= $anchor_id ?>"
                            cx="<?= $val[0] ?>" 
                            cy="<?= $val[1] ?>" 
                            r="5" 
                            fill="blue"
                            />
                            <circle 
                            id="anchor_<?= $anchor_id ?>_range"
                            cx="<?= $val[0] ?>" 
                            cy="<?= $val[1] ?>" 
                            r="100"
                            stroke="grey" 
                            stroke-width="20"
                            fill="none"
                            opacity="0.5"
                            />
                            <text 
                            id="anchor_<?= $anchor_id ?>_label"
                            text-anchor="middle"
                            x="<?= $val[0] ?>" 
                            y="<?= $val[1] ?>" 
                            fill="red">
                                <?= $anchor_id ?>
                            </text>
                        <?php
                        }
                        ?>
                        <circle 
                        id="position_marker"
                        cx="<?= $max_x / 2 ?>" 
                        cy="<?= $max_y / 2 ?>" 
                        r="5" 
                        fill="red" />
                    </svg>
                </div>     <!-- svg-container -->
            </div> <!-- screen -->            
            </div><!-- block -->

    </div> <!-- row -->

    <div class="control">
        <div class="block">
                <h2>Controls</h2>
                <button type="button" onclick="updateLatestPosition()">follow position</button>
                <button type="button" onclick="playRecordedUpdates()">play record</button>
                <input id="recording_counter" type="number" value=1>
                <button type="button" onclick="stopAutomaticUpdates()">stop updates</button>
                <span>--</span>
                <button id="manual_update_table_button" type="button" onclick="getManualUpdateTable()">manual table</button>
                <span>--</span>
                <button id="save_session_button"  type="button" onclick="saveSession()">save session</button>
                <button id="load_session_button"  type="button" onclick="loadSession()">load session</button>
                <select name="session_selection" id="session_selection">
                <?php
                foreach ($saved_sessions as $session) {
                ?>
                    <option value="<?= $session['id'] ?>"><?= $session['id'] ?></option>
                <?php
                }
                ?>
                </select>
                <span>--</span>
                <button id="delete_measurement_button"  type="button" onclick="deleteMeasurement()">delete measurements</button>
                        
                <table id="result_table"></table>
        </div>
    </div> <!-- control -->
</main>
</body>
</html>

