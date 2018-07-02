#ifndef PLC_COMMON_H
#define PLC_COMMON_H

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
  Error_checksum = 1<<5, ///< Message comes with bad checksum
};

void plcDebug(String s)
{
  Serial.println("Debug: " + s);
}

#endif PLC_COMMON_H
