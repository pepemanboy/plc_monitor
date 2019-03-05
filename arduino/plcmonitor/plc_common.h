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

/* PLC Configuration */
#define ANALOG_INPUT_COUNT (6)
#define DIGITAL_INPUT_COUNT (6)
#define INPUT_COUNT (ANALOG_INPUT_COUNT + DIGITAL_INPUT_COUNT)
#define OUTPUT_COUNT (6)
#define MAX_ACTIONS    (20)

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
  Error_maintain = 1<<7, ///< Cannot maintain connection
  Error_available = 1<<8, ///< Ethernet not available
  Error_httpstatus = 1<<9, ///< Error in HTTP status
  Error_httpheaders = 1<<10, ///< Error in HTTP headers
  Error_jsonerror = 1<<11, ///< Error parsing json
  Error_jsonvar = 1<<12, ///< Invalid json variable
  Error_shield = 1<<13, ///< Shield error
};

typedef uint16_t res_t;

/* Data types */
enum data_types
{
  type_uint8 = 1,
  type_int8,
  type_uint16,
  type_int16,
  type_uint32,
  type_int32,
  type_float,
};

/* Concatenate a string and a character*/
void strcat_c (char *str, char c)
{
  for (;*str;str++);
  *str++ = c; 
  *str++ = 0;
}
#ifdef DEBUG
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
#endif

#ifdef DEBUG
  #define PLC_DEBUG(s,n) plcDebug(s,n)
#else
  #define PLC_DEBUG(s,n) (void)0
#endif

/* Debug auxiliary function */
#ifdef DEBUG
void plcDebug(const char * s, int32_t n)
{
  Serial.print("Debug: ");
  Serial.print(s);
  Serial.print(" [");
  Serial.print(n);
  Serial.println("]");
  return;
}
#endif

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
res_t errorString(res_t e, char * s)
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
    case Error_available: strcpy(s, "Avai"); break;
    case Error_httpstatus: strcpy(s, "Stat"); break;
    case Error_httpheaders: strcpy(s, "Head"); break;
    case Error_jsonerror: strcpy(s, "Jerr"); break;
    case Error_jsonvar: strcpy(s, "Jvar"); break;
    case Error_shield: strcpy(s, "Jshd"); break;
    default: sprintf(s,"%d",e);
  }  
  return Ok;
}

/** Display error in LCD */
void lcdError(res_t error, const char * msg)
{
  char es[10];
  errorString(error,es);
  char lcd_buf[PLC_LCD_BUFFER_SIZE] = "";
  strcat(lcd_buf, msg);
  strcat(lcd_buf, es);
  lcdText(lcd_buf);
}

#endif //PLC_COMMON_H
