/*
  Sources:
  https://github.com/m5stack/M5Stack/blob/master/examples/Unit/UWB_DW1000/UWB_DW1000.ino
  https://docs.arduino.cc/tutorials/communication/wifi-nina-examples/#wifinina-connect-with-wpa
  https://docs.arduino.cc/tutorials/communication/wifi-nina-examples/#wifinina-wifi-web-client-repeating
*/
#include<ctype.h>
#include<WiFiNINA.h>
#include<UrlEncode.h>

// Transceiver Config
int SENSOR_MODE = 0;    // Used to set sensor mode .  0=Tag, 1=Anchor/base
int anchor_number = 2; // sets the number of the anchor, only used in anchor mode (SENSOR_MODE = 1)

// WLAN Config
char ssid[] = " ";
char pass[] = " ";
char serverAddress[] = " "; // IP
int port = 80;

// WLAN connection variables
int status = WL_IDLE_STATUS;     // the Wifi radio's status
WiFiClient client; // Initialize the Wifi client library
unsigned long lastConnectionTime = 0; // last time you connected to the server, in milliseconds

// Program Variables
String DATA = " "; // Used to store distance data sent by the sensor.
String CMD = " "; // Used to store commands sent to the arduino by the user
int available_anchors = 0; // tag returns 11 bytes per anchor -> int(response.length / 11) -> not reliable
bool send_http = true; // Used to control wether sensor data should be sent to the webserver
unsigned long last_sensor_data_available = 0;
unsigned long now = 0;
unsigned long time_since_last_sensor_data_available = 0;

// Debugging
bool send_serial = false; // Used to activate writing to the Serial USB port, for use with the Serial Monitor (send 'r')
unsigned long loop_count = 0; // counting loops between reads/sends shows if the arduino can handle the throughput


// clear serial buffer, variable, and timer
void UWB_clear() {
    if (Serial1.available()) {
        delay(3);
        DATA = Serial1.readString();
    }
    DATA       = " ";
}

// Read UART data from the sensor and save it in DATA
void sensorRead() {
  
  switch (SENSOR_MODE) {
    case 0: // TAG Mode
      delay(5);
      int serialBytesAvailable = Serial1.available();
      delay(5);
      if (serialBytesAvailable > 0) {
        
        // delay(20);
        available_anchors = (serialBytesAvailable / 11);  // Count the number of Base stations, "anX:00.00m\n"
        
        DATA = Serial1.readString();

        now = millis();
        time_since_last_sensor_data_available = now - last_sensor_data_available;
        
        // When Serial Connection is active (send 'r'), output data to it
        if (send_serial) {
          Serial.print("milliseconds since start: ");
          Serial.println(now);
          Serial.print("last sensor data available: ");
          Serial.println(last_sensor_data_available);
          Serial.print("Time since last sensor data available: ");
          Serial.println(time_since_last_sensor_data_available);
          Serial.print("Loop count: ");
          Serial.println(loop_count);
          Serial.print("serial bytes available: ");
          Serial.println(serialBytesAvailable);
          Serial.print("available anchors: ");
          Serial.println(available_anchors);
          Serial.println(DATA);
        }

        if (send_http) {
          httpRequest(DATA);
        }
        last_sensor_data_available = now;
      }
      break;
    // case 1:
    //   if (timer_data == 0 || timer_data > 70) {  // Indicates successful or lost connection with Tag
    //     if (Serial1.available()) {
    //       delay(2);
    //       DATA       = Serial1.readString();
    //       DATA       = "set up successfully!";
    //       // timer_data = 1;
    //       break;
    //     } else if (timer_data > 0 && Serial1.available() == 0) {
    //       DATA       = "Can't find the tag!!!";
    //       break;
    //     }
    //   }
    //   break;
  }
  
}

// AT command
void setupSensor() {
  switch (SENSOR_MODE) {
    // Repeat connection process twice to stabilize the connection
    case 0:
      for (int b = 0; b < 2; b++) {  
        delay(50);
        Serial1.write("AT+anchor_tag=0\r\n");  // 0 = tag mode
        delay(50);
        Serial1.write("AT+interval=5\r\n"); // time between measurements (in ms??)
        delay(50);
        Serial1.write("AT+switchdis=1\r\n");  // Begin to distance
        delay(50);
        if (b == 0) {
          Serial1.write("AT+RST\r\n");  // RESET
        }
      }
      UWB_clear();
      break;
    case 1:
      for (int b = 0; b < 2; b++) {
          delay(50);
          Serial1.write("AT+anchor_tag=1,");  // 1 = base station mode, anchor ID
          Serial1.print(anchor_number);  // anchor_number is base station ID0~ID3 // Print writes as human-readable (ASCII), converting the int to a string 
          Serial1.write("\r\n");
          delay(50);
          if (b == 0) {
            Serial1.write("AT+RST\r\n");
          }
        }
      UWB_clear();
      break;
  }
}

void handleSerialCommands() {
  if (Serial.available() > 0) {

    CMD = Serial.readString();
    char firstChar = CMD.charAt(0);
    Serial.print("Arduino received: ");
    Serial.println(CMD);

    // send a number to set the anchor number? not properly implemented yet
    if (isDigit(firstChar)) {
      int new_num = 0;
      new_num = firstChar - '0'; // converts char to int...
      if (1 <= new_num && new_num <= 4) {
        Serial.println("detected a valid anchor number.");
      } else {
        Serial.println("detected a number, NOT valid as anchor number.");
      }
    }

    // send 'r' to toggle serial output of sensor data
    if (firstChar == 'r') {
      send_serial = !send_serial;
      if (send_serial) {
        Serial.println("Activated Printing of Sensor Data to Serial Output. (send_serial = true)");
        DATA = "Test Data. If this is output, there was no Sensor read available.";
        delay(500);
      } else {
        Serial.println("Deactivated Printing of Sensor Data to Serial Output. (send_serial = false)");
      }
    }

    // send 'v' to ask the sensor for it's version
    if (firstChar == 'v') {
      Serial1.write("AT+version?\r\n");
      delay(20);
      if (Serial1.available()) {      
        DATA = Serial1.readString();
        Serial.println(DATA);
      } else {
        Serial.println("no response from the UWB Unit");
      }

    }

    // send 'w' to check the wifi connection
    if (firstChar == 'w') {
      printCurrentNet();
    }

    //toggle sending http requests with the ranging data to the server
    if (firstChar == 'h') {
      send_http = !send_http;
      if (send_http) {
        Serial.println("Activated Sending of Sensor Data to the server via WLAN. (send_http = true)");
      } else {
        Serial.println("Deactivated Sending of Sensor Data to the server via WLAN. (send_http = false)");
      }
    }
    
    CMD = " ";
  }
}

void setupWifi() {
  
  if (WiFi.status() == WL_NO_MODULE) {
    Serial.println("Communication with WiFi module failed!");
  }

  String fv = WiFi.firmwareVersion();
  if (fv < WIFI_FIRMWARE_LATEST_VERSION) {
    Serial.println("Please upgrade the firmware");
  }

  // attempt to connect to Wifi network:
  while (status != WL_CONNECTED) {
    listNetworks();
    Serial.print("Attempting to connect to WPA SSID: ");
    Serial.println(ssid);
    // Connect to WPA/WPA2 network:
    status = WiFi.begin(ssid, pass);
    // wait 10 seconds for connection:
    delay(10000);
  }

  // you're connected now, so print out the data:
  Serial.print("You're connected to the network");
  printCurrentNet();
  printWifiData();

}

void printWifiData() {

  // print your board's IP address:
  IPAddress ip = WiFi.localIP();
  Serial.print("IP Address: ");
  Serial.println(ip);

  // print your MAC address:
  byte mac[6];
  WiFi.macAddress(mac);
  Serial.print("MAC address: ");
  printMacAddress(mac);
}

void listNetworks() {

  // scan for nearby networks:
  Serial.println("** Scan Networks **");
  int numSsid = WiFi.scanNetworks();

  if (numSsid == -1) {
    Serial.println("Couldn't get a wifi connection");
    while (true);
  }

  // print the list of networks seen:
  Serial.print("number of available networks:");
  Serial.println(numSsid);

  // print the network number and name for each network found:
  for (int thisNet = 0; thisNet < numSsid; thisNet++) {
    Serial.print(thisNet);
    Serial.print(") ");
    Serial.print(WiFi.SSID(thisNet));
    Serial.print("\tSignal: ");
    Serial.print(WiFi.RSSI(thisNet));
    Serial.print(" dBm");
    Serial.print("\tEncryption: ");
    printEncryptionType(WiFi.encryptionType(thisNet));
  }
}

void printEncryptionType(int thisType) {
  // read the encryption type and print out the title:
  switch (thisType) {
    case ENC_TYPE_WEP:
      Serial.println("WEP");
      break;
    case ENC_TYPE_TKIP:
      Serial.println("WPA");
      break;
    case ENC_TYPE_CCMP:
      Serial.println("WPA2");
      break;
    case ENC_TYPE_NONE:
      Serial.println("None");
      break;
    case ENC_TYPE_AUTO:
      Serial.println("Auto");
      break;
    case ENC_TYPE_UNKNOWN:
    default:
      Serial.println("Unknown");
      break;
  }
}

void printCurrentNet() {

  // print the SSID of the network you're attached to:
  Serial.print("SSID: ");
  Serial.println(WiFi.SSID());

  // print the MAC address of the router you're attached to:
  byte bssid[6];
  WiFi.BSSID(bssid);
  Serial.print("BSSID: ");
  printMacAddress(bssid);

  // print the received signal strength:
  long rssi = WiFi.RSSI();
  Serial.print("signal strength (RSSI):");
  Serial.println(rssi);

  // print the encryption type:
  byte encryption = WiFi.encryptionType();
  Serial.print("Encryption Type:");
  Serial.println(encryption, HEX);

  Serial.println();
}

void printMacAddress(byte mac[]) {

  for (int i = 5; i >= 0; i--) {
    if (mac[i] < 16) {
      Serial.print("0");
    }

    Serial.print(mac[i], HEX);
    if (i > 0) {
      Serial.print(":");
    }
  }

  Serial.println();
}

// this method makes a HTTP connection to the server:
void httpRequest(String message) {
  Serial.println("starting http request...");
  // close any connection before send a new request.
  // This will free the socket on the Nina module
  client.stop();

  // if there's a successful connection:
  if (client.connect(serverAddress, 80)) {

    Serial.println("connecting...");

    // send the HTTP PUT request:
    // client.println("GET / HTTP/1.1");
    // client.println("Host: example.org");
    client.print("GET /marcus/uwb_positioning/update_tag_position.php?data=");
    client.print(urlEncode("t:"));
    client.print(now);
    client.print(urlEncode("\r\n"));
    client.print(urlEncode(message));
    client.println(" HTTP/1.1");
    client.print("Host: "); // Same as server IP
    client.println(serverAddress);
    client.println("User-Agent: ArduinoWiFi/1.1");
    client.println("Connection: close");
    client.println();
    // note the time that the connection was made:
    lastConnectionTime = millis();
  } else {
    // if you couldn't make a connection:
    Serial.println("connection failed");
  }

}

void setup() {
  Serial.begin(9600);
  Serial1.begin(115200);
  delay(100);

  if (SENSOR_MODE == 0) {
    setupWifi();
  }

  Serial.print("Setup Sensor. Sensor mode = ");
  Serial.println(SENSOR_MODE);
  Serial.print("anchor number = ");
  Serial.println(anchor_number);
  delay(100);
  setupSensor();

}

void loop() {
  if (send_serial) {
    loop_count += 1; 
  }

  handleSerialCommands();
  sensorRead();

  DATA = " ";

  

  // delay(1000); 

}
