#define PLC_ID 8

#include "plc_monitor.h"

void setup() {
  plc_init();
}

void loop() {
  plc_mainLoop();
}
