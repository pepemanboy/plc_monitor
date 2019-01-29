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
uint8_t _internalUpdate();

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
	uint8_t logErrors; ///< Logging errors
	uint8_t ioErrors; ///< IO errors
	bool initialized; ///< Initialized flag
};

/* Global plcDevice */
PlcDevice plcDevice;

/* Function prototype declarations */
uint8_t _startupSequence();
uint8_t _setOutputs();

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
    #ifdef DEBUG
    Serial_begin();
    #endif
    _plcDeviceInit();
    initEthernet();
    lcdText("Connected!...");
    delay(500);
    lcdText("Configuring...");
    _startupSequence();
    delay(500);
    lcdText("All set!");
    delay(500);
    plcDevice.initialized = true;    
  }
}

/* Get config from server */
uint8_t _plcGetConfig()
{
	uint32_t di_freq[6];
	uint8_t di_count[6];
	uint32_t ai_freq[6];
	float ai_gain[6];
	float ai_offs[6];

	uint8_t r = getConfig(di_freq, di_count, ai_freq, ai_gain, ai_offs);
	if(r != Ok)
  {
    plcDebug("Failed to get config, error = ", r);
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

/* Get outputs from server */
uint8_t _plcGetOutputs()
{
	uint8_t i;
	bool outputs[OUTPUT_COUNT];

	uint8_t r = getOutputs(outputs);
	if (r != Ok)
  {
    plcDebug("Failed to get outputs. Error = ", r);
    return r;    
  }

  _setOutputs();

	return Ok;
}

/* Get counters from the server */
res_t _plcGetCounters()
{
  uint32_t di[DIGITAL_INPUT_COUNT];

  uint8_t r = getDigitalInputs(di);
  if (r != Ok)
  {
    plcDebug("Failed to get counters. Error = ", r);
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
res_t _plcResetCounters()
{
  int32_t rr[DIGITAL_INPUT_COUNT];

  uint8_t r = getResets(rr);
  if (r != Ok)
  {
    plcDebug("Failed to get resets. Error = ", r);
    return r;  
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    if(rr[i] < 0) continue;
    plcDevice.din[i].value = rr[i];
  }
  return Ok;
}

/* Send inputs to server */
uint8_t _plcSendInputs()
{
	uint8_t i;

	uint32_t din[DIGITAL_INPUT_COUNT];
	uint32_t ain[ANALOG_INPUT_COUNT];

	for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
	{
		din[i] = plcDevice.din[i].value;
		ain[i] = plcDevice.ain[i].reading;
	}

	uint8_t r = setInputs(din,ain);
	if (r != Ok)
  {
    plcDebug("Failed to send inputs. Error = ", r);
    return r;
  }
  return Ok;
}

/* Log input to server */
uint8_t _plcLogInput(plc_in_t * input)
{
	uint8_t r = logInput(input->number, input->type == input_Analog ? input_Analog : input_Digital , input->value);
	if (r != Ok)
  {
    plcDebug("Failed to log input. Error = ", r);
    return r;
  }
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
  #ifndef DEBUG
  return;
  #else
  uint8_t i;
  Serial_println("-------------------------------");
  // Plc info
  Serial_print("PLC ID:" + String(plcDevice.id));
  Serial_print(" timestamp: " + String(plcDevice.timeStamp));
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

	// Errors
	Serial_print("Errors. Error io: " + String(plcDevice.ioErrors));
	Serial_println(" Error log: " + String(plcDevice.logErrors));

  Serial_println("-------------------------------");\
  Serial_println();
  Serial_println();
  #endif
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

  uint8_t i;

  // Input timestamps
  for (i = 0; i < INPUT_COUNT; i ++)
	{
    plcDevice.in[i].log_elapsed_ms += e;
  }
}

/* Log inputs */
uint8_t _logInputs()
{  
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

/* Read inputs */
uint8_t _readInputs()
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
uint8_t _setOutputs()
{
  // Outputs
  for (uint8_t i = 0; i < OUTPUT_COUNT; i++)
  {
    _plcDigitalWrite(i, plcDevice.dout[i].value);
  }

  return Ok;
}

/* Internal update */
uint8_t _internalUpdate()
{
  _updateTimestamps();
  _readInputs();
}

/* Update io */
uint8_t _updateIo()
{  
	uint8_t i;
	uint8_t r = Ok;

  r |= _plcGetConfig();
  r |= _plcResetCounters();

	// Update to/from server
	r |= _plcSendInputs();
	r |= _plcGetOutputs();

  _setOutputs();
  
 return plcDevice.ioErrors = r;
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
    r |= _plcGetOutputs();
  }
  return r;
}

/* Update plc */
uint8_t updatePlc()
{ 
  uint8_t r = Ok;
  
	r |= _updateIo();
	r |= _logInputs();

  ethernetResetWatchdog(); 
  _printPlcDevice();
  return r;
}

/* Display analog inputs */
uint8_t displayRaw()
{
  plc_lcd.clear(); // Clear the screen 
  for(uint8_t i = 1; i <= 6; i ++){
    plc_lcd.setCursor(((i-1)%3)*4,(i-1)/3); // Set the cursor in Column 0, Row 0 of the LCD  
    char buf[PLC_LCD_BUFFER_SIZE] = "";
    sprintf(buf,"%d",plc_analogRead(i));
    plc_lcd.print(buf); // Print this text where the cursor is
  }
  delay(500);
  return Ok;
}

void plc_init()
{
  _initPlcMonitor();
  _printPlcDevice();
}

void plc_mainLoop()
{
  if (plc_buttonRead(1)) 
  {    
    displayRaw();
    return;
  }
  updatePlc();
}

#endif // PLC_MONITOR_H
