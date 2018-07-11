/*
 * PLC Monitor structures and actions
 *
 * Author: pepemanboy
*/

#ifndef PLC_MONITOR_H
#define PLC_MONITOR_H

/* Test variables */
float test_di[6];
float test_ai[6];

/* Ethernet connection configuration */
#define PLC_IP {192, 168, 0, (50+PLC_ID)}
#define PLC_MAC { 0x90, 0xA2, 0xDA, 0x11, 0x08, PLC_ID }

#include "plc_ethernet.h"

/* PLC Configuration */
#define ANALOG_INPUT_COUNT (6)
#define DIGITAL_INPUT_COUNT (6)
#define INPUT_COUNT (ANALOG_INPUT_COUNT + DIGITAL_INPUT_COUNT)
#define OUTPUT_COUNT (6)
#define MAX_ACTIONS		(20)

/* Clear object using pointer */
#define clearObject(p) memset((p), 0, sizeof(*(p)))

/* Error counter for watchdog*/
#define MAX_CONTINUOUS_ERRORS 5
int continuous_errors = 0;

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
	int32_t notification_period_ms; ///< Notification period
  uint32_t notification_elapsed_ms; ///< Elapsed ms since last notification was sent
	bool permanent_triggered; ///< Permanent action triggered flag
	bool event_triggered; ///< Event action triggered flag
  bool notification_triggered; ///< First notification sent flag
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
  uint32_t timeStamp; ///< Timestamp
	uint8_t logErrors; ///< Logging errors
	uint8_t ioErrors; ///< IO errors
	uint8_t actionErrors; ///< action errors
	bool initialized; ///< Initialized flag
};

/* Global plcDevice */
PlcDevice plcDevice;

/* Function prototype declarations */
uint8_t _startupSequence();

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
    lcdText("Connecting...");
    Serial_begin();
    _plcDeviceInit();
    initEthernet();
    lcdText("Connected!...");
    delay(500);
    lcdText("Configuring...");
    delay(500);
    _startupSequence();
    lcdText("All set!");
    delay(500);
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
		plcDevice.actions[i].permanent_triggered = e ? actions_[j].permanent_triggered : false;
		plcDevice.actions[i].event_triggered = e ? actions_[j].event_triggered : false;
    plcDevice.actions[i].notification_triggered = e ? actions_[j].notification_triggered : false;
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

/* Get counters from the server */
uint8_t _plcGetCounters()
{
  _initPlcMonitor();
  
  int di[DIGITAL_INPUT_COUNT];

  uint8_t r = getDigitalInputs(di);
  if (r != Ok)
  {
    plcDebug("Failed to get counters. Error = " + String(r));
    return r;  
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    if (plcDevice.din[i].type == input_Counter)
    {
      plcDevice.din[i].value = di[i];
    }
  }

  return Ok;  
}

/* Get reset counters from the server */
uint8_t _plcResetCounters()
{
  _initPlcMonitor();

  int rr[DIGITAL_INPUT_COUNT];
  uint8_t r = getResets(rr);
  if (r != Ok)
  {
    plcDebug("Failed to get resets. Error = " + String(r));
    return r;  
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    if(rr[i] < 0) continue;
    plcDevice.din[i].value = rr[i];
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
	int din[DIGITAL_INPUT_COUNT];
	int ain[ANALOG_INPUT_COUNT];

	for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
	{
		din[i] = plcDevice.din[i].value;
		ain[i] = plcDevice.ain[i].value;
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
  uint8_t r = sendEmail(action->id);
  if (r != Ok)
  {
    plcDebug("Failed to send notification. Error = " + String(r));
    return r;
  }
  plcDebug("Sending notification");
	return r;
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
  #ifndef DEBUG
  return;
  #endif
  uint8_t i;
  Serial_println("-------------------------------");
  // Plc info
  Serial_print("PLC ID:" + String(plcDevice.id));
  Serial_print(" timestamp: " + String(plcDevice.timeStamp));
  Serial_println(" actions: " + String(plcDevice.actions_number));
  // Digital inputs
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    Serial_print("DI #" + String(plcDevice.din[i].number));
    Serial_print(" Type: " + _typeString(plcDevice.din[i].type));
    Serial_print(", Value: " + String(plcDevice.din[i].value));
    Serial_print(", Log period ms: " + String(plcDevice.din[i].log_period_ms));
    Serial_print(", Log elapsed ms: " + String(plcDevice.din[i].log_elapsed_ms));
    Serial_print(", Reading: " + String(plcDevice.din[i].reading));
    Serial_println(", Reading_: " + String(plcDevice.din[i].reading_));
  }

  // Analog inputs
  for (i = 0; i < ANALOG_INPUT_COUNT; i ++)
  {
    Serial_print("AI #" + String(plcDevice.ain[i].number));
    Serial_print(" Type: " + _typeString(plcDevice.ain[i].type));
    Serial_print(", Value: " + String(plcDevice.ain[i].value));
    Serial_print(", Log period ms: " + String(plcDevice.ain[i].log_period_ms));
    Serial_print(", Log elapsed ms: " + String(plcDevice.ain[i].log_elapsed_ms));
    Serial_print(", Reading: " + String(plcDevice.ain[i].reading));
    Serial_print(", Reading_: " + String(plcDevice.ain[i].reading_));
    Serial_print(", Gain: " + String(plcDevice.ain[i].gof.g));
    Serial_println(", Offset: " + String(plcDevice.ain[i].gof.o));
  }

  // Outputs
  for (i = 0; i < OUTPUT_COUNT; i ++)
  {
    Serial_print("DO #" + String(plcDevice.dout[i].number));
    Serial_println(" Value: " + String(plcDevice.dout[i].value));
  }

  // Actions
  for (i = 0; i < plcDevice.actions_number; i ++)
  {    
    // uint8_t action_type = plcDevice.actions[i].type;
    // if (action_type == action_None) continue;
    
    Serial_print("Action #" + String(i));
		Serial_print(" ID: " + String(plcDevice.actions[i].id));
    Serial_print(" Type: " + String(plcDevice.actions[i].type));
    Serial_print(", Input type: " +  _typeString(plcDevice.actions[i].input_type));
    Serial_print(", Input number: " + String(plcDevice.actions[i].input_number));
    Serial_print(", Output: " + String(plcDevice.actions[i].output));
    Serial_print(", Threshold: " + String(plcDevice.actions[i].threshold));
    Serial_print(", Threshold side: " + String(plcDevice.actions[i].threshold_side));
    Serial_print(", Delay_Elapsed_ms: " + String(plcDevice.actions[i].delay_elapsed_ms));
    Serial_print(", Delay_ms: " + String(plcDevice.actions[i].delay_ms));
		Serial_print(", Notif: " + String(plcDevice.actions[i].notification_period_ms));
		Serial_print(", Notif elapsed: " + String(plcDevice.actions[i].notification_elapsed_ms));
		Serial_print(", Delay triggered: " + String(plcDevice.actions[i].delay_triggered));
		Serial_print(", Delay finished: " + String(plcDevice.actions[i].delay_finished));
		Serial_print(", Event triggered: " + String(plcDevice.actions[i].event_triggered));
		Serial_print(", Permanent triggered: " + String(plcDevice.actions[i].permanent_triggered));
    Serial_println(", Notification triggered: " + String(plcDevice.actions[i].notification_triggered));
  }

	// Errors
	Serial_print("Errors. Error io: " + String(plcDevice.ioErrors));
	Serial_print(" Error actions: " + String(plcDevice.actionErrors));
	Serial_println(" Error log: " + String(plcDevice.logErrors));

  Serial_println("-------------------------------");\
  Serial_println();
  Serial_println();
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


/* Digital read */
uint8_t _plcDigitalRead(uint8_t d)
{
  #ifdef DEBUG
  mockInputs();
  return test_di[d];
  #else
  return plc_digitalRead(d+1);
  #endif	
}

/* Analog read */
float _plcAnalogRead(uint8_t a)
{
  #ifdef DEBUG
  mockInputs();
  return test_ai[a];
  #else
  return plc_analogRead(a+1);
  #endif
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
	uint8_t r = Ok;
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
	uint8_t r = Ok;
  
  r |= _plcGetConfig();
  r |= _plcResetCounters();
  
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
	uint8_t r = Ok;
  bool send_outputs = false;
  
  r |= _plcGetActions();
  for (uint8_t i = 0; i < plcDevice.actions_number; ++i)
  {
    uint8_t output = plcDevice.actions[i].output - 1;
    if(output > 0)
    {
      switch (plcDevice.actions[i].type)
      {
        case action_None: 
          break;
        case action_Permanent:
          if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].permanent_triggered)
          {
            _plcDigitalWrite(output, HIGH);
            send_outputs = true;
            plcDevice.actions[i].permanent_triggered = true;
          }
          if (plcDevice.dout[output].value == LOW && plcDevice.actions[i].permanent_triggered && !_thresholdPassed(&plcDevice.actions[i]))
          {
            plcDevice.actions[i].permanent_triggered = false;
          }
          break;
        case action_Event:
          if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].event_triggered)
          {
            _plcDigitalWrite(output,HIGH);
            send_outputs = true;
            plcDevice.actions[i].event_triggered = true;
          }
          else if (!_thresholdPassed(&plcDevice.actions[i]) && plcDevice.actions[i].event_triggered)
          {
            _plcDigitalWrite(output,LOW);
            send_outputs = true;
            plcDevice.actions[i].event_triggered = false;
          }
          break;
        case action_Delay:
          if (_thresholdPassed(&plcDevice.actions[i]) && !plcDevice.actions[i].delay_triggered)
          {
            plcDevice.actions[i].delay_triggered = true;
            _plcDigitalWrite(output, HIGH);
            send_outputs = true;
            plcDevice.actions[i].delay_elapsed_ms = 0;            
          }
          if (!plcDevice.actions[i].delay_finished && plcDevice.actions[i].delay_triggered && (plcDevice.actions[i].delay_elapsed_ms > plcDevice.actions[i].delay_ms))
          {
            _plcDigitalWrite(output, LOW);
            send_outputs = true;
            plcDevice.actions[i].delay_finished = true;
          }    
          if (!_thresholdPassed(&plcDevice.actions[i]) && (plcDevice.actions[i].delay_finished || ((plcDevice.dout[output].value == LOW )&& (plcDevice.actions[i].delay_triggered))))
          {
            plcDevice.actions[i].delay_finished = false;
            plcDevice.actions[i].delay_triggered = false;
          }
          break;    
      }
      if(send_outputs)
      {
        r |= _plcSendOutputs();
      }   
    }     
          
    // Notifications
    if (plcDevice.actions[i].notification_period_ms != 0)
    {
      if (_thresholdPassed(&plcDevice.actions[i]))
      {
        bool b = false;
        if(!plcDevice.actions[i].notification_triggered)
        {          
          b = true;
          plcDevice.actions[i].notification_triggered = true;
        }
        else if((plcDevice.actions[i].notification_elapsed_ms > plcDevice.actions[i].notification_period_ms) && (plcDevice.actions[i].notification_period_ms > 0))
        {
          b = true;
        }   
        if (b) // Send notification
        {
          r |= _sendNotification(&plcDevice.actions[i]);                    
          plcDevice.actions[i].notification_elapsed_ms = 0;
        }        
      }
      else // Threshold not passed
      {
        plcDevice.actions[i].notification_triggered = false;
      }         
    }
  }
  
	return plcDevice.actionErrors = r;
}

/* Startup sequence */
uint8_t _startupSequence()
{
  uint8_t r = Error;
  while (r != Ok)
  {
    r = Ok;
    r |= _plcGetConfig();    
    r |= _plcResetCounters(); // Dismiss
    r |= _plcGetCounters();     
    r |= _plcGetActions();  
    r |= _plcGetOutputs();    
  }
  return r;
}


/* Update plc */
uint8_t updatePlc()
{  
  ethernetMaintain();  
  _updateTimestamps();
  
  uint8_t  r;
	r |= _updateIo();
  r |= _updateActions();
	r |= _logInputs();
 
  _printPlcDevice();
  return r;
}

void plc_mainLoop()
{
  updatePlc();
  delay(100);
}

#endif // PLC_MONITOR_H
