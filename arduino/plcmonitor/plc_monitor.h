#ifndef PLC_MONITOR_H
#define PLC_MONITOR_H

/*
 * PLC Monitor structures and actions
 *
 * Author: pepemanboy
*/

/* ETHERNET CONNECTION */
#define PLC_ID 1
#define PLC_IP { 192, 168, 100, 79 }
#define PLC_MAC { 0x90, 0xA2, 0xDA, 0x11, 0x08, 0x19 }
#include "plc_ethernet.h"

/* Config */
#define ANALOG_INPUT_COUNT (6)
#define DIGITAL_INPUT_COUNT (6)
#define INPUT_COUNT (ANALOG_INPUT_COUNT + DIGITAL_INPUT_COUNT)
#define OUTPUT_COUNT (6)

#define MAX_ACTIONS		(20)

/* Clear object using pointer */
#define clearObject(p) memset((p), 0, sizeof(*(p)))

/* Input types */
enum
{
  type_Digital = 0, ///< Digital input
  type_Counter, ///< Incremental counter input
  type_Analog, ///< Analog input
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

/* Action structure */
typedef struct Action Action;
struct Action
{
	uint8_t input_type; ///< Input number that will trigger the action
	uint8_t input_number; ///< Input number that will trigger the action
	float threshold; ///< Threshold for input value
  uint8_t threshold_side; ///< Threshold side that triggers the action
  uint8_t type; ///< Action type
  uint32_t elapsed_s; ///< Time since trigger
  uint32_t delay_s; ///< Time for trigger to activate
  uint8_t delay_triggered; ///< Delay triggered flag
	uint8_t output; ///< Output related
	uint32_t notification_interval_s; ///< Notification interval
};

/* Gain Offset Structure */
typedef struct plc_gof plc_gof;
struct plc_gof
{
	float g; ///< Gain
	float o; ///< Offset
};

/* Input struct */
typedef struct plc_in_t plc_in_t;
struct plc_in_t
{
  uint8_t type; ///< Input type
	int freq; ///< Logging frequency
  uint32_t elapsed_ms; ///< Elapsed seconds since last logging
  float value; ///< Input value
  float reading; ///< Input reading
  float reading_; ///< Past input reading
  plc_gof gof; ///< Gain and offset
};

/* Digital output struct */
typedef struct plc_do_t plc_do_t;
struct plc_do_t
{
	uint8_t value; ///< Digital output value
};

/* Plc struct */
typedef struct PlcDevice PlcDevice;
struct PlcDevice
{
  uint8_t id; ///< Id
  plc_in_t in[INPUT_COUNT]; ///< Inputs
  plc_in_t * din; ///< Digital inputs
  plc_in_t * ain; ///< Analog inputs
  plc_do_t dout[OUTPUT_COUNT]; ///< Digital outputs
	Action actions[MAX_ACTIONS]; ///< Actions
  uint32_t timeStamp;
};

/* Global variables */
PlcDevice plcDevice;

/* Initialize plcDevice */
void _plcDeviceInit()
{
	uint8_t i = 0;

  clearObject(&plcDevice);

  plcDevice.din = plcDevice.in;
  plcDevice.ain = &plcDevice.in[6];
  plcDevice.id = PLC_ID;

  // Initialize inputs and outputs
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    plcDevice.din[i].type = type_Digital;
		plcDevice.ain[i].type = type_Analog;
		plcDevice.ain[i].gof = {1,0};
  }
}

/* Get actions from server */
uint8_t _plcGetConfig()
{
	uint8_t r, i;

	int di_freq[6];
	uint8_t di_count[6];
	int ai_freq[6];
	float ai_gain[6];
	float ai_offs[6];

	r = getConfig(di_freq, di_count, ai_freq, ai_gain, ai_offs);
	if(r != Ok)
		return r;

	for(i = 0; i < DIGITAL_INPUT_COUNT; i++)
	{
		plcDevice.din[i].freq = di_freq[i];
		plcDevice.din[i].type = di_count[i] ? type_Counter : type_Digital;

		plcDevice.ain[i].freq = ai_freq[i];
		plcDevice.ain[i].gof = {ai_gain[i], ai_offs[i]};
	}

	return Ok;
}

/* Get actions from server */
uint8_t _plcGetActions()
{
	uint8_t n;

	uint8_t inputs_types[MAX_ACTIONS];
	uint8_t inputs_numbers[MAX_ACTIONS];
	float thresholds[MAX_ACTIONS];
	uint8_t updowns[MAX_ACTIONS];
	uint8_t outputs[MAX_ACTIONS];
	long notification_interval_s[MAX_ACTIONS];
	uint8_t action_types[MAX_ACTIONS];
	long delays_s[MAX_ACTIONS];

	uint8_t r = getActions(&n,inputs_types, inputs_numbers,thresholds,updowns,outputs,notification_interval_s,action_types,delays_s);
	if(r != Ok)
		return r;

	memset(&plcDevice.actions, 0, sizeof(plcDevice.actions));

	for (uint8_t i = 0; i < n; ++i)
	{
		plcDevice.actions[i].output = outputs[i];
		plcDevice.actions[i].input_number = inputs_numbers[i];
		plcDevice.actions[i].input_type = inputs_types[i];
		plcDevice.actions[i].threshold = thresholds[i];
		plcDevice.actions[i].threshold_side = updowns[i];
		plcDevice.actions[i].notification_interval_s = notification_interval_s[i];
		plcDevice.actions[i].type = action_types[i];
		plcDevice.actions[i].delay_s = delays_s[i];
	}

	return Ok;
}

/* Get outputs from server */
uint8_t _plcGetOutputs()
{
	uint8_t i;
	bool outputs[OUTPUT_COUNT];

	uint8_t r = getOutputs(outputs);
	if (r != Ok)
		return r;

	for (i = 0; i < OUTPUT_COUNT; i ++)
	{
		plcDevice.dout[i].value = outputs[i];
	}

	return Ok;
}

/* Send inputs to server */
uint8_t _plcSendInputs()
{
	uint8_t i;
	bool din[DIGITAL_INPUT_COUNT];
	int ain[ANALOG_INPUT_COUNT];

	for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
	{
		din[i] = plcDevice.din[i].value;
		ain[i] = plcDevice.ain[i].reading;
	}

	uint8_t r = setInputs(din,ain);
	if (r != Ok)
		return r;
}

/* Log input to server */
uint8_t _plcLogInput(uint8_t number, uint8_t type)
{
	uint8_t r = logInput(number, type, type == type_Digital ? plcDevice.din[number].value : plcDevice.ain[number].value);
	if (r != Ok)
		return r;
}

/* Digital input types string name */
String _typeString(uint8_t n)
{
  switch(n)
  {
    case type_Digital: return "Digital"; break;
    case type_Counter: return "Counter"; break;
    case type_Analog: return "Analog"; break;
  }
  return "None";
}

/* Print device */
void _printPlcDevice()
{
  uint8_t i;
  Serial.println("-------------------------------");
  // Digital inputs
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    Serial.print("DI #" + String(i+1));
    Serial.print(" Type: " + _typeString(plcDevice.din[i].type));
    Serial.print(", Value: " + String(plcDevice.din[i].value));
    Serial.print(", Freq: " + String(plcDevice.din[i].freq));
    Serial.print(", Reading: " + String(plcDevice.din[i].reading));
    Serial.println(", Reading_: " + String(plcDevice.din[i].reading_));
  }

  // Analog inputs
  for (i = 0; i < ANALOG_INPUT_COUNT; i ++)
  {
    Serial.print("AI #" + String(i+1));
    Serial.print(" Type: " + _typeString(plcDevice.ain[i].type));
    Serial.print(", Value: " + String(plcDevice.ain[i].value));
    Serial.print(", Freq: " + String(plcDevice.ain[i].freq));
    Serial.print(", Reading: " + String(plcDevice.ain[i].reading));
    Serial.print(", Reading_: " + String(plcDevice.ain[i].reading_));
    Serial.print(", Gain: " + String(plcDevice.ain[i].gof.g));
    Serial.println(", Offset: " + String(plcDevice.ain[i].gof.o));
  }

  // Outputs
  for (i = 0; i < OUTPUT_COUNT; i ++)
  {
    Serial.print("DO #" + String(i+1));
    Serial.println(" Value: " + String(plcDevice.dout[i].value));
  }

  // Actions
  for (i = 0; i < MAX_ACTIONS; i ++)
  {    
    uint8_t action_type = plcDevice.actions[i].input_type;
    if (action_type == action_None) break;
    
    Serial.print("Action #" + String(i));
    Serial.print(" Type: " + String(plcDevice.actions[i].type));
    Serial.print(", Input type: " +  _typeString(plcDevice.actions[i].input_type));
    Serial.print(", Input number: " + String(plcDevice.actions[i].input_number));
    Serial.print(", Output: " + String(plcDevice.actions[i].output));
    Serial.print(", Threshold: " + String(plcDevice.actions[i].threshold));
    Serial.print(", Threshold side: " + String(plcDevice.actions[i].threshold_side));
    Serial.print(", Elapsed_s: " + String(plcDevice.actions[i].elapsed_s));
    Serial.print(", Delay_s: " + String(plcDevice.actions[i].delay_s));
    Serial.print(", Triggered: " + String(plcDevice.actions[i].delay_triggered));
    Serial.println(", Notif: " + String(plcDevice.actions[i].notification_interval_s));
  }
  Serial.println("-------------------------------");
}

/* Digital read */
uint8_t _plcDigitalRead(uint8_t d)
{
	return true;
}

/* Analog read */
float _plcAnalogRead(uint8_t a)
{
	return 69;
}

/* Digital output */
void _plcDigitalWrite(uint8_t d, uint8_t v)
{
	return;
}

/* Apply gain offset */
void _applyGof(plc_in_t * in)
{
	in->value = in->reading * in->gof.g + in->gof.o;
}

/* Log inputs */
uint8_t _logInputs()
{
  uint32_t t = millis(); // new timestamp
  uint32_t e = t - plcDevice.timeStamp; // elapsed
  plcDevice.timeStamp = t; // update timestamp
  
  for (uint8_t i = 0; i < INPUT_COUNT; i++)
  {  
    if (plcDevice.in[i].freq == 0) continue; // No logging
    plcDevice.in[i].elapsed_ms += e;
    if (plcDevice.in[i].elapsed_ms/1000 > plcDevice.in[i].freq)
    {
      _plcLogInput(i,plcDevice.in[i].type);
      plcDevice.in[i].elapsed_ms = 0;
      delay(500);
    }
  }
}


/* Update io */
void _updateIo()
{  
  uint8_t i;
  
  // Digital inputs
  for(i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    plcDevice.din[i].reading_ = plcDevice.din[i].reading;
		plcDevice.din[i].reading = _plcDigitalRead(i);
    if (plcDevice.din[i].type == type_Digital)
    {
      plcDevice.din[i].value = plcDevice.din[i].reading;
    }else // Counter
    {
      if (plcDevice.din[i].reading_ == false && plcDevice.din[i].reading == true) // Rising edge
        plcDevice.din[i].value;
    }
  }

  // Analog inputs
  for (i = 0; i < ANALOG_INPUT_COUNT; i++)
  {
		plcDevice.ain[i].reading_ = plcDevice.ain[i].reading;
		plcDevice.ain[i].reading = _plcAnalogRead(i);
		_applyGof(&plcDevice.ain[i]);
  }

  // Outputs
  for (i = 0; i < OUTPUT_COUNT; i++)
  {
		_plcDigitalWrite(i, plcDevice.dout[i].value);
	}

  // Log input signals
  _logInputs();

  // Update inputs and outputs to/from server
  _plcSendInputs();
  _plcGetOutputs();
}

void testMonitor()
{
  uint8_t r;
  _plcDeviceInit();
  _printPlcDevice();

  // Connect
  Serial.println();
  Serial.println("Connecting to ethernet");
  initEthernet();
  Serial.println("Connected");
  Serial.println();

  // Get outputs
  Serial.println("Modified ouptuts");
  r = _plcGetOutputs();
  Serial.println("Error = " + String(r));
  _printPlcDevice();

  // Get config
  Serial.println("Get configuration");
  r = _plcGetConfig();
  Serial.println("Error = " + String(r));
  _printPlcDevice();

  // Get actions
  Serial.println("Get actions");
  r = _plcGetActions();
  Serial.println("Error = " + String(r));
  _printPlcDevice();

  // Update io
  Serial.println("Update io");
  _updateIo();
  _printPlcDevice();

  
  
  
  
}

#endif // PLC_MONITOR_H
