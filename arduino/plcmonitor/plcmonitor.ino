#include "plc_monitor.h"

void setup() {  
  watchdogDisable();
  watchdogReset();
  plc_init();
}

void loop() {
  plc_mainLoop();
}
