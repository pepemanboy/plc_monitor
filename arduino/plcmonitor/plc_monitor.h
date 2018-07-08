#ifndef PLC_MONITOR_H
#define PLC_MONITOR_H

#include <plcshield.h>

/*
 * PLC Monitor structures and actions
 *
 * Author: pepemanboy
*/

/* Test */
float test_di[6];
float test_ai[6];

/* Ethernet connection configuration */
#define PLC_IP { 192, 168, 100, 79 }
#define PLC_MAC { 0x90, 0xA2, 0xDA, 0x11, 0x08, PLC_ID }

#include "plc_common.h"
#include "plc_ethernet.h"

/* PLC Configuration */
#define ANALOG_INPUT_COUNT (6)
#define DIGITAL_INPUT_COUNT (6)
#define INPUT_COUNT (ANALOG_INPUT_COUNT + DIGITAL_INPUT_COUNT)
#define OUTPUT_COUNT (6)
#define MAX_ACTIONS		(20)

/* Clear object using pointer */
#define clearObject(p) memset((p), 0, sizeof(*(p)))

/* Action structure */
typedef struct Action Action;
struct Action
{	
	uint8_t id; ///< Action id
	uint8_t type; ///< Action type
	uint8_t input_type; ///< Input number that will trigger the action
	uint8_t input_number; ///< Input number that will trigger the action
	float threshold; ///< Threshold for input value
	uint8_t threshold_side; ///< Threshold side that triggers the action
	uint8_t output; ///< Related output
  uint32_t delay_elapsed_ms; ///< Time since trigger
  uint32_t delay_ms; ///< Time for trigger to activate
	bool delay_triggered; ///< Delay triggered flag
  bool delay_finished; ///< Delay finished flag
	uint32_t notification_period_ms; ///< Notification period
  uint32_t notification_elapsed_ms; ///< Elapsed ms since last notification was sent
	bool permanent_triggered; ///< Permanent action triggered flag
	bool event_triggered; ///< Event action triggered flag
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
  uint8_t number; ///< Input number
	uint32_t log_period_ms; ///< Logging period
  uint32_t log_elapsed_ms; ///< Elapsed seconds since last logging
  float value; ///< Input value
  float reading; ///< Input reading
  float reading_; ///< Past input reading
  plc_gof gof; ///< Gain and offset
};

/* Digital output struct */
typedef struct plc_do_t plc_do_t;
struct plc_do_t
{
  uint8_t number; ///< Output number
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
  uint8_t actions_number; ///< Number of actions
  uint32_t timeStamp;
	uint8_t logErrors;
	uint8_t ioErrors;
	uint8_t actionErrors;
	bool initialized;
};

/* Global variables */
PlcDevice plcDevice;

/* Initialize plcDevice */
void _plcDeviceInit()
{
  clearObject(&plcDevice);

  plcDevice.din = plcDevice.in;
  plcDevice.ain = &plcDevice.in[6];
  plcDevice.id = PLC_ID;

  // Initialize inputs and outputs
	for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
		plcDevice.din[i].type = input_Digital;
    plcDevice.din[i].number = i+1;
		plcDevice.ain[i].type = input_Analog;
		plcDevice.ain[i].gof = {1,0};
    plcDevice.ain[i].number = i+1;
    plcDevice.dout[i].number = i+1;
  }
}

/* Init plc monitor */
void _initPlcMonitor()
{
  if(!plcDevice.initialized)
  {
    plc_setup();  
    plc_lcd.noBlink(); // Cursor does not blink  
    plc_lcd.noCursor(); // Hide cursor
    plc_lcd.clear(); // Clear the screen
    plc_lcd.setCursor(0,0);
    plc_lcd.print("PLC Monitor");
    plc_lcd.setCursor(0,1);
    plc_lcd.print("Connecting...");
    Serial.begin(115200);
    _plcDeviceInit();
    initEthernet();
    plcDevice.initialized = true;    
  }
}

/* Get config from server */
uint8_t _plcGetConfig()
{
	_initPlcMonitor();
	int di_freq[6];
	uint8_t di_count[6];
	int ai_freq[6];
	float ai_gain[6];
	float ai_offs[6];

	uint8_t r = getConfig(di_freq, di_count, ai_freq, ai_gain, ai_offs);
	if(r != Ok)
  {
    plcDebug("Failed to get config, error = " + String(r));
    return r;
  }

	for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i++)
	{
		plcDevice.din[i].log_period_ms = di_freq[i] * 1000;
    uint8_t previous_type = plcDevice.din[i].type;
		plcDevice.din[i].type = di_count[i] ? input_Counter : input_Digital;
    if (plcDevice.din[i].type == input_Counter && previous_type != input_Counter)
      plcDevice.din[i].value = 0;
		plcDevice.ain[i].log_period_ms = ai_freq[i] * 1000;
		plcDevice.ain[i].gof = {ai_gain[i], ai_offs[i]};
	}

	return Ok;
}

/* Get actions from server */
uint8_t _plcGetActions()
{
	_initPlcMonitor();
	uint8_t n; // Number of actions
	uint8_t inputs_types[MAX_ACTIONS];
	uint8_t inputs_numbers[MAX_ACTIONS];
  uint8_t ids[MAX_ACTIONS];
	float thresholds[MAX_ACTIONS];
	uint8_t updowns[MAX_ACTIONS];
	uint8_t outputs[MAX_ACTIONS];
	long notification_interval_s[MAX_ACTIONS];
	uint8_t action_types[MAX_ACTIONS];
	long delays_s[MAX_ACTIONS];

	uint8_t r = getActions(&n,inputs_types, inputs_numbers,ids,thresholds,updowns,outputs,notification_interval_s,action_types,delays_s);
	if(r != Ok)
  {
    plcDebug("Failed to get actions. Error = " + String(r));
    return r;
  }

  plcDevice.actions_number = n;

	// Store old actions and initialize actions in 0
	Action actions_[MAX_ACTIONS];
  memcpy(actions_,plcDevice.actions,sizeof(plcDevice.actions));
  memset(&plcDevice.actions, 0, sizeof(plcDevice.actions));

	// Fetch new actions
	for (uint8_t i = 0; i < n; ++i)
	{
    // Check if action exists
    bool e = false; 
    uint8_t j;
    for (j = 0; j < MAX_ACTIONS; ++j)
    {
			if (ids[i] == actions_[j].id)
      {
        e = true;
        break;
      }
		}
		// Fill action
		plcDevice.actions[i].id = ids[i];
		plcDevice.actions[i].type = action_types[i];
		plcDevice.actions[i].input_type = inputs_types[i];
		plcDevice.actions[i].input_number = inputs_numbers[i];
		plcDevice.actions[i].threshold = thresholds[i];
		plcDevice.actions[i].threshold_side = updowns[i] == 0 ? threshold_trigger_above : threshold_trigger_below;
		plcDevice.actions[i].output = outputs[i];
		plcDevice.actions[i].delay_elapsed_ms = e ? actions_[j].delay_elapsed_ms : 0;
		plcDevice.actions[i].delay_ms = delays_s[i] * 1000;
		plcDevice.actions[i].delay_triggered = e ? actions_[j].delay_triggered : false;
    plcDevice.actions[i].delay_finished = e ? actions_[j].delay_finished : false;
		plcDevice.actions[i].notification_period_ms = notification_interval_s[i] * 1000;
		plcDevice.actions[i].notification_elapsed_ms = e ? actions_[j].notification_elapsed_ms : 0;
		plcDevice.actions[i].permanent_triggered = e ? actions_[j].permanent_triggered : 0;
		plcDevice.actions[i].event_triggered = e ? actions_[j].event_triggered : 0;
	}

	return Ok;
}

/* Get outputs from server */
uint8_t _plcGetOutputs()
{
	_initPlcMonitor();
	uint8_t i;
	bool outputs[OUTPUT_COUNT];

	uint8_t r = getOutputs(outputs);
	if (r != Ok)
  {
    plcDebug("Failed to get outputs. Error = " + String(r));
    return r;    
  }

	for (i = 0; i < OUTPUT_COUNT; i ++)
	{
		plcDevice.dout[i].value = outputs[i] ? 1 : 0;
	}

	return Ok;
}

/* Send outputs to server */
uint8_t _plcSendOutputs()
{
  _initPlcMonitor();
  uint8_t i;
  bool dout[OUTPUT_COUNT];

  for (i = 0; i < OUTPUT_COUNT; i ++)
  {
    dout[i] = plcDevice.dout[i].value == 0 ? false : true;
  }

  uint8_t r = setOutputs(dout);
  if (r != Ok)
  {
    plcDebug("Failed to send outputs. Error = " + String(r));
    return r;
  }
  return Ok;
}

/* Send inputs to server */
uint8_t _plcSendInputs()
{
	_initPlcMonitor();
	uint8_t i;
	bool din[DIGITAL_INPUT_COUNT];
	int ain[ANALOG_INPUT_COUNT];

	for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
	{
		din[i] = plcDevice.din[i].value == 1 ? true : false;
		ain[i] = plcDevice.ain[i].reading;
	}

	uint8_t r = setInputs(din,ain);
	if (r != Ok)
  {
    plcDebug("Failed to send inputs. Error = " + String(r));
    return r;
  }
  return Ok;
}

/* Log input to server */
uint8_t _plcLogInput(plc_in_t * input)
{
	_initPlcMonitor();
	uint8_t r = logInput(input->number, input->type == input_Analog ? input_Analog : input_Digital , input->value);
	if (r != Ok)
  {
    plcDebug("Failed to log input. Error = " + String(r));
    return r;
  }
  return Ok;
}

/* Send email notification */
uint8_t _sendNotification(Action * action)
{
	_initPlcMonitor();
	return Ok;
}

/* Digital input types string name */
String _typeString(uint8_t n)
{
  switch(n)
  {
	case input_Digital: return "Digital"; break;
	case input_Counter: return "Counter"; break;
	case input_Analog: return "Analog"; break;
  }
  return "None";
}

/* Print device */
void _printPlcDevice()
{
  uint8_t i;
  Serial.println("-------------------------------");
  // Plc info
  Serial.print("PLC ID:" + String(plcDevice.id));
  Serial.print(" timestamp: " + String(plcDevice.timeStamp));
  Serial.println(" actions: " + String(plcDevice.actions_number));
  // Digital inputs
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    Serial.print("DI #" + String(plcDevice.din[i].number));
    Serial.print(" Type: " + _typeString(plcDevice.din[i].type));
    Serial.print(", Value: " + String(plcDevice.din[i].value));
    Serial.print(", Log period ms: " + String(plcDevice.din[i].log_period_ms));
    Serial.print(", Log elapsed ms: " + String(plcDevice.din[i].log_elapsed_ms));
    Serial.print(", Reading: " + String(plcDevice.din[i].reading));
    Serial.println(", Reading_: " + String(plcDevice.din[i].reading_));
  }

  // Analog inputs
  for (i = 0; i < ANALOG_INPUT_COUNT; i ++)
  {
    Serial.print("AI #" + String(plcDevice.ain[i].number));
    Serial.print(" Type: " + _typeString(plcDevice.ain[i].type));
    Serial.print(", Value: " + String(plcDevice.ain[i].value));
    Serial.print(", Log period ms: " + String(plcDevice.ain[i].log_period_ms));
    Serial.print(", Log elapsed ms: " + String(plcDevice.ain[i].log_elapsed_ms));
    Serial.print(", Reading: " + String(plcDevice.ain[i].reading));
    Serial.print(", Reading_: " + String(plcDevice.ain[i].reading_));
    Serial.print(", Gain: " + String(plcDevice.ain[i].gof.g));
    Serial.println(", Offset: " + String(plcDevice.ain[i].gof.o));
  }

  // Outputs
  for (i = 0; i < OUTPUT_COUNT; i ++)
  {
    Serial.print("DO #" + String(plcDevice.dout[i].number));
    Serial.println(" Value: " + String(plcDevice.dout[i].value));
  }

  // Actions
  for (i = 0; i < plcDevice.actions_number; i ++)
  {    
    // uint8_t action_type = plcDevice.actions[i].type;
    // if (action_type == action_None) continue;
    
    Serial.print("Action #" + String(i));
		Serial.print(" ID: " + String(plcDevice.actions[i].id));
    Serial.print(" Type: " + String(plcDevice.actions[i].type));
    Serial.print(", Input type: " +  _typeString(plcDevice.actions[i].input_type));
    Serial.print(", Input number: " + String(plcDevice.actions[i].input_number));
    Serial.print(", Output: " + String(plcDevice.actions[i].output));
    Serial.print(", Threshold: " + String(plcDevice.actions[i].threshold));
    Serial.print(", Threshold side: " + String(plcDevice.actions[i].threshold_side));
    Serial.print(", Delay_Elapsed_ms: " + String(plcDevice.actions[i].delay_elapsed_ms));
    Serial.print(", Delay_ms: " + String(plcDevice.actions[i].delay_ms));
		Serial.print(", Notif: " + String(plcDevice.actions[i].notification_period_ms));
		Serial.print(", Notif elapsed: " + String(plcDevice.actions[i].notification_elapsed_ms));
		Serial.print(", Delay triggered: " + String(plcDevice.actions[i].delay_triggered));
		Serial.print(", Delay finished: " + String(plcDevice.actions[i].delay_finished));
		Serial.print(", Event triggered: " + String(plcDevice.actions[i].event_triggered));
		Serial.println(", Permanent triggered: " + String(plcDevice.actions[i].permanent_triggered));
  }

	// Errors
	Serial.print("Errors. Error io: " + String(plcDevice.ioErrors));
	Serial.print(" Error actions: " + String(plcDevice.actionErrors));
	Serial.println(" Error log: " + String(plcDevice.logErrors));

  Serial.println("-------------------------------");\
  Serial.println();
  Serial.println();
}

/* Digital read */
uint8_t _plcDigitalRead(uint8_t d)
{
  return plc_digitalRead(d+1);
	/*return test_di[d];*/
}

/* Analog read */
float _plcAnalogRead(uint8_t a)
{
  return plc_analogRead(a+1);
	/*return test_ai[a];*/
}

/* Digital output */
void _plcDigitalWrite(uint8_t d, uint8_t v)
{
  plc_digitalWrite(d+1,v == 1 ? HIGH : LOW);
  plcDevice.dout[d].value = v;
	return;
}

/* Apply gain offset */
void _applyGof(plc_in_t * in)
{
	in->value = in->reading * in->gof.g + in->gof.o;
}

/* Update timestamps */
void _updateTimestamps()
{
	_initPlcMonitor();
  uint32_t t = millis(); // new timestamp
  uint32_t e = t - plcDevice.timeStamp; // elapsed
  plcDevice.timeStamp = t; // update timestamp

  uint8_t i;

  // Input timestamps
  for (i = 0; i < INPUT_COUNT; i ++)
	{
    plcDevice.in[i].log_elapsed_ms += e;
  }

  // Action timestamps
  for (i = 0; i < plcDevice.actions_number; ++ i)
  {
    plcDevice.actions[i].notification_elapsed_ms += e;
    plcDevice.actions[i].delay_elapsed_ms += e;
  }
}

/* Log inputs */
uint8_t _logInputs()
{  
	_initPlcMonitor();
	uint8_t r = 0;
  for (uint8_t i = 0; i < INPUT_COUNT; i++)
  {  
    if (plcDevice.in[i].log_period_ms == 0) continue; // No logging
    if (plcDevice.in[i].log_elapsed_ms > plcDevice.in[i].log_period_ms)
    {
			r |= _plcLogInput(&plcDevice.in[i]);
			plcDevice.in[i].log_elapsed_ms = 0;
    }
  }
	return plcDevice.logErrors = r;
}


/* Update io */
uint8_t _updateIo()
{  
	_initPlcMonitor();
	uint8_t i;
	uint8_t r = 0;

  
  r |= _plcGetConfig();
  
  // Digital inputs
  for(i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
		plcDevice.din[i].reading_ = plcDevice.din[i].reading; // Store previous reading
		plcDevice.din[i].reading = _plcDigitalRead(i);
		if (plcDevice.din[i].type == input_Digital)
    {
      plcDevice.din[i].value = plcDevice.din[i].reading;
		}
		else // Counter
    {
      if (plcDevice.din[i].reading_ == 0 && plcDevice.din[i].reading == 1) // Rising edge
        plcDevice.din[i].value++;
    }
  }

  // Analog inputs
  for (i = 0; i < ANALOG_INPUT_COUNT; i++)
  {
		plcDevice.ain[i].reading_ = plcDevice.ain[i].reading;
		plcDevice.ain[i].reading = _plcAnalogRead(i);
		_applyGof(&plcDevice.ain[i]);
	}

	// Update to/from server
	r |= _plcSendInputs();
	r |= _plcGetOutputs();

  // Outputs
  for (i = 0; i < OUTPUT_COUNT; i++)
  {
		_plcDigitalWrite(i, plcDevice.dout[i].value);
	}
 return plcDevice.ioErrors = r;
}

/* Get action input value */
float _actionInputValue(Action * action)
{
	if (action->input_type == input_Analog)
  {
    return plcDevice.ain[action->input_number-1].value;
	}
	else
  {
    return plcDevice.din[action->input_number-1].value;
  }
}

/* Check if action passed the threshold */
bool _thresholdPassed(Action * action)
{
  if (action->threshold_side == threshold_trigger_above)
    return _actionInputValue(action) > action->threshold;
  else
    return _actionInputValue(action) < action->threshold;
}

/* Update actions */
uint8_t _updateActions()
{
	_initPlcMonitor();
	uint8_t r = 0;
  
  r |= _plcGetActions();
  for (uint8_t i = 0; i < plcDevice.actions_number; ++i)
  {
    uint8_t output = plcDevice.actions[i].output - 1;
    switch (plcDevice.actions[i].type)
    {
      case action_None: 
        break;
      case action_Permanent:
        if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].permanent_triggered)
        {
          _plcDigitalWrite(output, HIGH);
          r |= _plcSendOutputs();
          plcDevice.actions[i].permanent_triggered = true;
        }
        break;
      case action_Event:
        if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].event_triggered)
        {
          _plcDigitalWrite(output,HIGH);
          r |= _plcSendOutputs();
          plcDevice.actions[i].event_triggered = true;
        }
        else if (!_thresholdPassed(&plcDevice.actions[i]) && plcDevice.actions[i].event_triggered)
        {
          _plcDigitalWrite(output,LOW);
          r |= _plcSendOutputs();
          plcDevice.actions[i].event_triggered = false;
        }
        break;
      case action_Delay:
        if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].delay_triggered)
        {
          plcDebug("Writing high");
          plcDevice.actions[i].delay_triggered = true;
          _plcDigitalWrite(output, HIGH);
          r |= _plcSendOutputs();
          plcDevice.actions[i].delay_elapsed_ms = 0;            
        }
        if (!plcDevice.actions[i].delay_finished && plcDevice.actions[i].delay_triggered && (plcDevice.actions[i].delay_elapsed_ms > plcDevice.actions[i].delay_ms))
        {
          plcDebug("Finished = " + String(plcDevice.actions[i].delay_finished) + " Triggered = " + String(plcDevice.actions[i].delay_triggered));
          plcDebug("Writing low");
          _plcDigitalWrite(output, LOW);
          r |= _plcSendOutputs();
          plcDevice.actions[i].delay_finished = true;
        }    
        if (!_thresholdPassed(&plcDevice.actions[i]) && plcDevice.actions[i].delay_finished)
        {
          plcDebug("Resetting");
          plcDevice.actions[i].delay_finished = false;
          plcDevice.actions[i].delay_triggered = false;
        }
        break;    
    }
    // Notifications
    if (plcDevice.actions[i].notification_period_ms > 0 && _thresholdPassed(&plcDevice.actions[i]) && plcDevice.actions[i].notification_elapsed_ms > plcDevice.actions[i].notification_period_ms)
    {
			r |= _sendNotification(&plcDevice.actions[i]);
      plcDevice.actions[i].notification_elapsed_ms = 0;
    }
  }
	return plcDevice.actionErrors = r;
}

/* Debug monitor */
void lcdReport(uint8_t error_code)
{
  plc_lcd.clear();
  plc_lcd.setCursor(0,0);
  plc_lcd.print("PLC Monitor");
  plc_lcd.setCursor(0,1);
  plc_lcd.print("Warning = " + String(error_code));
}

/* Update plc */
void updatePlc()
{
  uint8_t  r;
  r |= ethernetMaintain();
  _updateTimestamps();
	r |=_updateIo();
  // _updateActions();
	r |= _logInputs();
  _printPlcDevice(); 
  lcdReport(r);
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

/* Mock inputs */
void mockInputs()
{
  if (Serial.available())
  {
    String s = Serial.readString();
    uint8_t input_type = s.indexOf("ai") < 0 ? input_Digital : input_Analog;
    uint8_t input_number = s.substring(s.indexOf("i")+1,s.indexOf(",")).toInt()-1;
    float value = s.substring(s.indexOf(",")+1).toFloat();
    if(input_type == input_Analog)
    {
      test_ai[input_number] = value;
    }
    else
    {
      test_di[input_number] = value;
    }
  }
}

void plc_testLoop()
{
  mockInputs();
  updatePlc();
  delay(1000);
}

#endif // PLC_MONITOR_H