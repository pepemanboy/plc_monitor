#include "plc_monitor.h"

void setup() {
  // put your setup code here, to run once:
  Serial.begin(115200);
  // testEthernet();
  // testMonitor();
  // _plcDeviceInit();  
  str_buf.reserve(500);
  str_buf_1.reserve(500);
}

void loop() {
  // put your main code here, to run repeatedly:
  testLoop();
}
