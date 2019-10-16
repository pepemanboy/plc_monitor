# Plc_monitor

This repo contains the implementation of a system for remote monitoring and control of [plcshield](https://github.com/pepemanboy/plcshield)

The system consists of two parts: software and firmware

## Software
Software consists of a web application, hosted in AWS.

This is the main tab, where the connected PLCs can be seen, added, or removed.
![Admin tab of plcmonitor](https://github.com/pepemanboy/plc_monitor/blob/master/images/plcmonitor_admin.PNG)

This is the visualization tab, where one can graph different signals of different PLCs.
![Viz tab of plcmonitor](https://github.com/pepemanboy/plc_monitor/blob/master/images/plcmonitor_viz.PNG)

This is the configuration tab, where one can configure the PLCs.
![Config tab of plcmonitor](https://github.com/pepemanboy/plc_monitor/blob/master/images/plcmonitor_config.PNG)

All software code is in the website folder.

## Firmware
Firmware consists of a C program, intended to be run in an arduino connected to the plcshield.

All firmware code is in the firmware folder and is self documented.

## Installation

1. Install the plcshield library and its associated external libraries, as explained in the documentation (https://github.com/pepemanboy/plcshield)

2. Open plc_monitor/arduino/plcmonitor/plcmonitor.ino file

3. Sketch -> Include library -> Manage libraries. Buscar "Ethernet". Escoger la version a instalar (2.0), y clic en instalar.

4. Ir a plc_config, y cambiar el PLC_ID al ID del PLC correspondiente. Dejar todo lo demás igual (No mover IP ni nada)

5. Compilar para Arduino mega 2560

6. Seleccionar puerto

7. Download!
