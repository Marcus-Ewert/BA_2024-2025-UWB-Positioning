let automaticUpdateId;

// from https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch 2025-01-02 (15:18:14 +1)
async function getManualUpdateTable() {
    const resultTable = document.querySelector("#result_table");
    const updateButton = document.querySelector('#manual_update_table_button');
    const url = window.location.href + "get_measurements.php";
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        const json = await response.json();
        
        resultTable.innerHTML = "";
        generateTableHead(resultTable, Object.keys(json[0]));
        generateTable(resultTable, json);

        document.querySelectorAll("#result_table tr").forEach(trElement => {
            trElement.addEventListener("click", function() {

                // console.log(trElement);
                let tdElements = trElement.children;
                document.querySelector('#anchor_1_range').setAttribute('r', tdElements[3].innerText);
                document.querySelector('#anchor_2_range').setAttribute('r', tdElements[4].innerText);
                document.querySelector('#anchor_3_range').setAttribute('r', tdElements[5].innerText);
                document.querySelector('#anchor_4_range').setAttribute('r', tdElements[6].innerText);


                const distances = {};
                for (let i = 1; i <= 4; i++) {
                    const dist = parseInt(tdElements[i+2].innerText)
                    if (0 < dist) {
                        distances[i] = dist
                    }
                }
                calculatePosition(distances);

            }); 
        });
        console.log(json);

    } catch (error) {
        console.error(error.message);
        resultTable.textContent = error.message;
    }
}

// `id`, `timestamp_server`, `runtime_arduino`, `dist_anchor_1`, `dist_anchor_2`, `dist_anchor_3`, `dist_anchor_4`

// table creation from: https://www.valentinog.com/blog/html-table/ 2025-01-02 (15:18:14 +1)
function generateTableHead(table, tableHeaders) {
    let thead = table.createTHead();
    let row = thead.insertRow();
    for (let key of tableHeaders) {
        let th = document.createElement("th");
        let text = document.createTextNode(key);
        th.appendChild(text);
        row.appendChild(th);
    }
}

function generateTable(table, data) {
    for (let element of data) {
        let row = table.insertRow();
        for (key in element) {
            let cell = row.insertCell();
            let text = document.createTextNode(element[key]);
            cell.appendChild(text);
        }
    }

}

async function updateAnchorPosition(inputElement) {
    updateDisplayMaximum();
    const id = inputElement.dataset.anchorId;
    const axis = inputElement.dataset.axis;
    const value = parseInt(inputElement.value);
    const viewBox = document.querySelector('#positioning_display').viewBox.baseVal;
    const offset = 15;

    if (axis == 'z') {
        return
    }


    const label = document.querySelector(`#positioning_display #anchor_${id}_label`)

    document.querySelectorAll(`#positioning_display #anchor_${id},#anchor_${id}_range`)
        .forEach(element => {
            element.setAttribute(`c${axis}`, value)
        });

    if (axis == 'x') {
        if (value <= viewBox.width / 2) {
            label.setAttribute('x', value + offset)
        } else {
            label.setAttribute('x', value - offset)
        }
    } else {
        if (value <= viewBox.height / 2) {
            label.setAttribute('y', value + offset)
        } else {
            label.setAttribute('y', value - offset)
        }
    }

    updateDisplayMaximum();
}

function updateDisplayMaximum() {
    
    // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/reduce
    const xInputs = Array.from(document.querySelectorAll(`.anchor-position[data-axis=x]`));
    // console.log(xInputs);
    const yInputs = Array.from(document.querySelectorAll(`.anchor-position[data-axis=y]`));
    const maxX = xInputs.reduce((a, b) => Math.max(a, b.value), 0);
    const maxY = yInputs.reduce((a, b) => Math.max(a, b.value), 0);
    const minX = -5;
    const minY = -5;
    const svg = document.querySelector('#positioning_display');
    document.querySelector('#max-x').innerText = maxX;
    document.querySelector('#max-y').innerText = maxY;
    svg.setAttribute('viewBox', `-5 -5 ${maxX + 10} ${maxY + 10}`);
    // console.log(`max x: ${maxX}, max y: ${maxY}`);

    // --- GRID LINES ---
    // remove old grid lines
    document.querySelectorAll(".svg-grid-line").forEach(line => line.remove());
    // draw grid lines
    // vertical lines
    for (let i = 0; i <= maxX; i += 100) {
        let line = document.createElementNS('http://www.w3.org/2000/svg','line');
        line.setAttribute('class', 'svg-grid-line');
        line.setAttribute('x1', i);
        line.setAttribute('y1', minY);
        line.setAttribute('x2', i);
        line.setAttribute('y2', maxY + 10);
        line.setAttribute('stroke', i%500 == 0 ? 'black' : 'grey');
        line.setAttribute('stroke-width', 1);
        svg.appendChild(line);
    }
    // stroke-width="2"
    // horizontal lines
    for (let i = 0; i <= maxY; i += 100) {
        let line = document.createElementNS('http://www.w3.org/2000/svg','line');
        line.setAttribute('class', 'svg-grid-line');
        line.setAttribute('x1', minX);
        line.setAttribute('y1', i);
        line.setAttribute('x2', maxX + 10);
        line.setAttribute('y2', i);
        line.setAttribute('stroke', i%500 == 0 ? 'black' : 'grey');
        line.setAttribute('stroke-width', 1);
        svg.appendChild(line);
    }
    
}

async function saveAnchorSetup() {
    const data = {};
    document.querySelectorAll(`.anchor-position`)
        .forEach(inputElement => {
            const id = inputElement.dataset.anchorId;
            const axis = inputElement.dataset.axis;
            const value = inputElement.value;
            data[`anchor_${id}_${axis}`] = value
        })

    const url = window.location.href + "save_anchor_setup.php";

    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(data)
        });
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }
        console.log(response);
        


    } catch (error) {
        console.error(error.message);
    }
}

async function loadAnchorSetup(selectElement) {
    const url = window.location.href + "get_anchor_setup.php" + "?anchor_setup=" + selectElement.value;
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        const json = await response.json();

        document.querySelectorAll(`.anchor-position`)
            .forEach(inputElement => {
                inputElement.value = json[`anchor_${inputElement.dataset.anchorId}_${inputElement.dataset.axis}`]
                updateAnchorPosition(inputElement);
            })
        
        // console.log(json);

    } catch (error) {
        console.error(error.message);
    }
}

// https://developer.mozilla.org/en-US/docs/Web/API/Window/setInterval

function updateLatestPosition() {
    console.log("starting position tracking...");
    
    if (automaticUpdateId) {
        stopAutomaticUpdates();
    }

    automaticUpdateId = setInterval(getLatestMeasurement, 1000)

}

function playRecordedUpdates() {
    console.log("starting record playing...");

    if (automaticUpdateId) {
        stopAutomaticUpdates();
    }
    
    automaticUpdateId = setInterval(getRecordedMeasurement, 1000)

}

function stopAutomaticUpdates() {
    clearInterval(automaticUpdateId);
    automaticUpdateId = null;
}

function calculatePosition(distances) {

    if (Object.keys(distances).length < 2) {
        return;
    }


    let measurements = Array();

    console.log("got :");
    console.log(distances);
    
    Object.keys(distances).forEach(anchorId => {

        let measurement = {}
        measurement['distance'] = distances[anchorId]

        document.querySelectorAll(`.anchor-position[data-anchor-id="${anchorId}"]`)
            .forEach(input => {
                measurement[input.dataset.axis] = input.value
            })

        measurements.push(measurement);
    });

    const calculatedPosition = locate(measurements, { geometry: '3d' })
    console.log(calculatedPosition);

    const positionMarker = document.querySelector('#position_marker');
    positionMarker.setAttribute('cx', calculatedPosition.x)
    positionMarker.setAttribute('cy', calculatedPosition.y)

    console.log(positionMarker);

    ["x", "y", "z"].forEach(dimension => document.querySelector(`#position_${dimension}`).innerHTML = parseInt(calculatedPosition[dimension]));

}

async function getLatestMeasurement() {
    const resultTable = document.querySelector("#result_table");
    const url = window.location.href + "get_latest_measurement.php";
    
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        const json = await response.json();
        
        console.log(json);
        
        

        // updateButton.setAttribute('last-id', json[json.length - 1].id);

        resultTable.innerHTML = "";
        generateTableHead(resultTable, Object.keys(json));
        generateTable(resultTable, [json]);

        document.querySelector('#anchor_1_range').setAttribute('r', json.dist_anchor_1);
        document.querySelector('#anchor_2_range').setAttribute('r', json.dist_anchor_2);
        document.querySelector('#anchor_3_range').setAttribute('r', json.dist_anchor_3);
        document.querySelector('#anchor_4_range').setAttribute('r', json.dist_anchor_4);

        const distances = {};
        for (let i = 1; i <= 4; i++) {
            if (0 < parseInt(json[`dist_anchor_${i}`])) {
                distances[i] = json[`dist_anchor_${i}`]
            }
        }

        calculatePosition(distances);



        //     }); 
        // });

    } catch (error) {
        console.error(error.message);
    }
}

async function getRecordedMeasurement() {
    const resultTable = document.querySelector("#result_table");
    const recordingCounter = document.querySelector("#recording_counter");
    const url = window.location.href + "get_measurement.php" + `?id=${recordingCounter.value}`;
    
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        const json = await response.json();
        
        console.log(json);
        
        

        // updateButton.setAttribute('last-id', json[json.length - 1].id);
        recordingCounter.value = parseInt(json.id) + 1

        resultTable.innerHTML = "";
        generateTableHead(resultTable, Object.keys(json));
        generateTable(resultTable, [json]);

        document.querySelector('#anchor_1_range').setAttribute('r', json.dist_anchor_1);
        document.querySelector('#anchor_2_range').setAttribute('r', json.dist_anchor_2);
        document.querySelector('#anchor_3_range').setAttribute('r', json.dist_anchor_3);
        document.querySelector('#anchor_4_range').setAttribute('r', json.dist_anchor_4);

        const distances = {};
        for (let i = 1; i <= 4; i++) {
            if (0 < parseInt(json[`dist_anchor_${i}`])) {
                distances[i] = json[`dist_anchor_${i}`]
            }
        }

        calculatePosition(distances);



        //     }); 
        // });

    } catch (error) {
        console.error(error.message);
    }
}

async function saveSession() {

        const anchorSetupId = document.querySelector("#anchor-setup-selection").value
        const url = window.location.href + "save_session.php" + `?anchor_setup_id=${anchorSetupId}`;

        try {

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Response status: ${response.status}`);
            }
    
            alert(`Successfully saved session with anchor setup ${anchorSetupId}.`)
    
        } catch (error) {
            console.error(error.message);
        }

}

async function deleteMeasurement() {
    const confirmDelete = confirm("Are you sure you want to DELETE current measurements?");
    if (!confirmDelete) {
        return; 
    }
    const url = window.location.href + "delete_measurements.php";
    
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        alert("Successfully Deleted measurements.")

    } catch (error) {
        console.error(error.message);
    }
}

async function loadSession() {
    const confirmDelete = confirm("Are you sure you want to load session data? this will DELETE all current measurements.");
    if (!confirmDelete) {
        return; 
    }
    const sessionId = document.querySelector("#session_selection").value
    const url = window.location.href + "load_session.php"  + `?session_id=${sessionId}`;
    
    try {

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }

        alert("Successfully loaded Session.")

    } catch (error) {
        console.error(error.message);
    }
}