/*
 * PLC Monitor structures and actions
 * 
 * Do not modify.
 *
 * Author: pepemanboy
 * Email: pepe_ciro@hotmail.com
*/

#ifndef PLC_MONITOR_H
#define PLC_MONITOR_H

/* Test variables */
float test_di[6];
float test_ai[6];

/* Functions to export */
void _internalUpdate();

#include "plc_common.h"
#include "plc_ethernet.h"

/* Clear object using pointer */
#define clearObject(p) memset((p), 0, sizeof(*(p)))

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
  uint32_t timeStamp; ///< Timestamp
	res_t logErrors; ///< Logging errors
	res_t ioErrors; ///< IO errors
	bool initialized; ///< Initialized flag
};

/* Global plcDevice */
PlcDevice plcDevice;

/* Function prototype declarations */
res_t _startupSequence();
res_t _setOutputs();

/* Initialize plcDevice */
void _plcDeviceInit()
{
  clearObject(&plcDevice);

  plcDevice.din = plcDevice.in;
  plcDevice.ain = &plcDevice.in[6];
  plcDevice.id = PLC_ID;

  // Initialize inputs and outputs
	for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
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
  res_t r = Ok;
  if(!plcDevice.initialized)
  {
    plc_setup();  
    plc_lcd.noBlink(); // Cursor does not blink  
    plc_lcd.noCursor(); // Hide cursor
    plc_lcd.clear(); // Clear the screen
    lcdText("Connecting...");
    #ifdef DEBUG
    Serial_begin();
    #endif
    _plcDeviceInit();
    initEthernet();
    lcdText("Connected!...");
    delay(500);
    lcdText("Configuring...");
    _startupSequence();
    PLC_DEBUG("Finished startup sequence", 0);
    delay(500);
    lcdText("All set!");
    delay(500);
    plcDevice.initialized = true;    
  }
}

/* Get config from server */
res_t _plcGetConfig()
{
	uint32_t di_freq[6];
	uint8_t di_count[6];
	uint32_t ai_freq[6];
	float ai_gain[6];
	float ai_offs[6];

  res_t r = Error;
  while (r != Ok)
  {
    r = getConfig(di_freq, di_count, ai_freq, ai_gain, ai_offs);
    PLC_DEBUG("Failed to get config, error = ", r);
    lcdError(r, "p_get_cfg: ");
  }

	for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
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

/* Get outputs from server */
res_t _plcGetOutputs()
{
	bool outputs[OUTPUT_COUNT];

  res_t r = Error;
  while (r != Ok)
  {
    r = getOutputs(outputs);
    PLC_DEBUG("Failed to get outputs. Error = ", r);
    lcdError(r, "p_get_out: ");
  }

  for (uint8_t i = 0; i < OUTPUT_COUNT; ++i)
    plcDevice.dout[i].value = outputs[i];

  _setOutputs();

	return Ok;
}

/* Get counters from the server */
res_t _plcGetCounters()
{
  uint32_t di[DIGITAL_INPUT_COUNT];

  res_t r = Error;
  while (r != Ok)
  {
    r = getDigitalInputs(di);
    PLC_DEBUG("Failed to get counters. Error = ", r);
    lcdError(r, "p_get_cnt: ");
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    if (plcDevice.din[i].type == input_Counter)
      plcDevice.din[i].value = di[i];
  }

  return Ok;  
}

/* Get reset counters from the server */
res_t _plcResetCounters()
{
  int32_t rr[DIGITAL_INPUT_COUNT];

  res_t r = Error;
  while (r != Ok)
  {
    r = getResets(rr);
    PLC_DEBUG("Failed to get resets. Error = ", r);
    lcdError(r, "p_cnt_res: ");
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    if(rr[i] < 0) continue;
    plcDevice.din[i].value = rr[i];
  }
  
  return Ok;
}

/* Send inputs to server */
res_t _plcSendInputs()
{
	uint32_t din[DIGITAL_INPUT_COUNT];
	uint32_t ain[ANALOG_INPUT_COUNT];

	for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
	{
		din[i] = plcDevice.din[i].value;
		ain[i] = plcDevice.ain[i].reading;
	}

  res_t r = Error;
  while (r != Ok)
  {
    r = setInputs(din,ain);
    PLC_DEBUG("Failed to send inputs. Error = ", r);
    lcdError(r, "p_set_in: ");
  }
  
  return Ok;
}

/* Log input to server */
res_t _plcLogInput(plc_in_t * input)
{
  res_t r = Error;
  while (r != Ok)
  {
    r = logInput(input->number, input->type == input_Analog ? input_Analog : input_Digital , input->value);
    PLC_DEBUG("Failed to log input. Error = ", r);
    lcdError(r, "p_log_in: ");
  }
  
  return Ok;
}

#ifdef DEBUG
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
      test_ai[input_number] = value;
    else
      test_di[input_number] = value;
  }
}
#endif // DEBUG

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
  plc_digitalWrite(d+1,v == 0 ? LOW : HIGH);
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
  uint32_t t = millis(); // new timestamp
  uint32_t e = t - plcDevice.timeStamp; // elapsed
  plcDevice.timeStamp = t; // update timestamp

  // Input timestamps
  for (uint8_t i = 0; i < INPUT_COUNT; ++i)
    plcDevice.in[i].log_elapsed_ms += e;
}

/* Log inputs */
res_t _logInputs()
{  
	res_t r = Ok;
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

/* Read inputs */
res_t _readInputs()
{
  // Digital inputs
  for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
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
  for (uint8_t i = 0; i < ANALOG_INPUT_COUNT; i++)
  {
    plcDevice.ain[i].reading_ = plcDevice.ain[i].reading;
    plcDevice.ain[i].reading = _plcAnalogRead(i);
    _applyGof(&plcDevice.ain[i]);
  }

  return Ok;
}

/* Set outputs */
res_t _setOutputs()
{
  for (uint8_t i = 0; i < OUTPUT_COUNT; i++)
    _plcDigitalWrite(i, plcDevice.dout[i].value);

  return Ok;
}

/* Internal update */
void _internalUpdate()
{
  _updateTimestamps();
  _readInputs();
}

/* Update io */
res_t _updateIo()
{  
	res_t r = Ok;

  r |= _plcGetConfig();
  r |= _plcResetCounters();

	// Update to/from server
	r |= _plcSendInputs();
	r |= _plcGetOutputs();

  _setOutputs();
  
 return plcDevice.ioErrors = r;
}

/* Startup sequence */
res_t _startupSequence()
{
  res_t r = Error;
  while (r != Ok)
  {
    r = Ok;
    r |= _plcGetConfig();
    r |= _plcResetCounters();
    r |= _plcGetCounters();  
    r |= _plcGetOutputs();
  }
  return r;
}

/* Update plc */
res_t updatePlc()
{ 
  res_t r = Ok;
  
	r |= _updateIo();
	r |= _logInputs();

  ethernetResetWatchdog(); 
  return r;
}

/* Display analog inputs */
void displayRaw()
{
  plc_lcd.clear(); // Clear the screen 
  for(uint8_t i = 1; i <= 6; ++i){
    plc_lcd.setCursor(((i-1)%3)*4,(i-1)/3); // Set the cursor in Column 0, Row 0 of the LCD  
    char buf[PLC_LCD_BUFFER_SIZE] = "";
    sprintf(buf,"%d",plc_analogRead(i));
    plc_lcd.print(buf); // Print this text where the cursor is
  }
  delay(500);
}

/* Initialize plc */
void plc_init()
{
  _initPlcMonitor();
}

/* Main loop */
void plc_mainLoop()
{
  #ifndef DEBUG
  if (plc_buttonRead(1)) 
  {    
    displayRaw();
    return;
  }
  #endif // !DEBUG
  updatePlc();
}

#endif // PLC_MONITOR_H
