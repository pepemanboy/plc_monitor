/*
 * PLC Server common definitions
 * 
 * Do not modify.
 *
 * Author: pepemanboy
 * Email: pepe_ciro@hotmail.com
*/

#ifndef PLC_COMMON_H
#define PLC_COMMON_H

#include <Ethernet.h>
#include "plc_config.h"
#include <plcshield.h>

#define SERIAL_BAUDRATE 115200

/* Input types */
enum
{
	input_Digital = 0, ///< Digital input
	input_Counter, ///< Incremental counter input
	input_Analog, ///< Analog input
}input_types_e;

/* Action types*/
enum
{
	action_None = 0, ///< No action
	action_Permanent, ///< Remain turned on
	action_Event, ///< Turn on while input above threshold
	action_Delay, ///< Turn on certain amount of time
}action_types_e;

/* Threshold sides */
enum
{
	threshold_trigger_above = 0, ///< Trigger when signal is above threshold
	threshold_trigger_below, ///< Trigger when signal is below threshold
}threshold_side_e;

/* Error codes */
enum error_codes
{
	Ok = 0, ///< No error
	Error = 1<<0, ///< Generic error
	Error_disconnect = 1<<1, ///< Disconnect error
	Error_connect = 1<<2, ///< Connect error
	Error_timeout = 1<<3, ///< Timeout error
	Error_inexistent = 1<<4, ///< PLC does not exist in server
  Error_overflow = 1<<5, ///< Overflow error
  Error_chunked = 1<<6, ///< Message does not come chunked
  Error_maintain = 1<<7, ///<Cannot maintain connection
};

/* Data types */
enum data_types
{
  type_int = 1, ///< Int 32
  type_float, ///< Float
  type_uint8, ///< Uint 8
  type_long, ///< Long
  type_ulong, ///< Unsigned long
};

/* Concatenate a string and a character*/
void strcat_c (char *str, char c)
{
  for (;*str;str++);
  *str++ = c; 
  *str++ = 0;
}

/* Debug to serial functions */
/* Open serial port */
void Serial_begin()
{
  #ifdef DEBUG
  static bool serial_initialized = false;
  if(!serial_initialized)
  {
    Serial.begin(SERIAL_BAUDRATE);
    serial_initialized = true;
  }
  #endif
  return;  
}

/* Print to serial */
void Serial_print(String s)
{
  #ifdef DEBUG
  Serial_begin();
  Serial.print(s);
  #endif
  return;
}

/* Print line to serial */
void Serial_println(String s = "")
{
  #ifdef DEBUG
  Serial_begin();
  Serial.println(s);
  #endif
  return;
}

/* Debug auxiliary function */
void plcDebug(String s)
{
  #ifdef DEBUG
  Serial_println("Debug: " + s);
  #endif
  return;
}

/* Reset function */
void(* softReset) (void) = 0; //declare reset function at address 0

#define PLC_LCD_BUFFER_SIZE 17
/* Debug monitor through lcd */
void lcdText(const char * s)
{
  plc_lcd.clear();
  plc_lcd.setCursor(0,0);
  char buf[PLC_LCD_BUFFER_SIZE] = "";
  sprintf(buf,"PLC Monitor %d", PLC_ID);
  plc_lcd.print(buf);
  plc_lcd.setCursor(0,1);
  plc_lcd.print(s);
}

/* Error code string */
uint8_t errorString(uint8_t e, char * s)
{
  if (!s) return Error;
  switch(e)
  {
    case Ok: strcpy(s, "Ok"); break;
    case Error: strcpy(s, "Error"); break;
    case Error_disconnect: strcpy(s, "Disc"); break;
    case Error_connect: strcpy(s, "Conn"); break;
    case Error_timeout: strcpy(s, "Tout"); break;
    case Error_inexistent: strcpy(s, "Inex"); break;
    case Error_overflow: strcpy(s, "Ovfl"); break;
    case Error_chunked: strcpy(s, "Chnk"); break;
    case Error_maintain: strcpy(s, "Mntn"); break;
    default: sprintf(s,"%d",e);
  }  
  return Ok;
}

#endif //PLC_COMMON_H
