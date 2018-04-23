#include <EEPROM.h>

#define ANALOG_INPUT_COUNT 6
#define DIGITAL_INPUT_COUNT 6
#define INPUT_COUNT (ANALOG_INPUT_COUNT + DIGITAL_INPUT_COUNT)
#define OUTPUT_COUNT 6

/** Clear object using pointer */
#define clearObject(p) memset((p), 0, sizeof(*(p)))

/* Digital input types */
enum
{
  type_Digital = 0, ///< Digital input
  type_Counter, ///< Incremental counter input
  type_Analog, ///< Analog input
}di_types_e;

/* Digital output actions */
enum
{
  action_None = 0, ///< No action
  action_Permanent, ///< Remain turned on
  action_Event, ///< Turn on while input above threshold
  action_Delay, ///< Turn on certain amount of time
}do_actions_e;

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
  uint8_t input; ///< Input number that will trigger the action
  float threshold; ///< Threshold for input value
  uint8_t threshold_side; ///< Threshold side that triggers the action
  uint8_t type; ///< Action type
  uint32_t elapsed_s; ///< Time since trigger
  uint32_t delay_s; ///< Time for trigger to activate
  uint8_t delay_triggered; ///< Delay triggered flag
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
  String nametag; ///< Nametag identifier
  uint8_t type; ///< Input type
  float value; ///< Input value
  float reading; ///< Input reading
  float reading_; ///< Past input reading
  plc_gof gof; ///< Gain and offset
};

/* Digital output struct */
typedef struct plc_do_t plc_do_t;
struct plc_do_t
{
  String nametag; ///< Nametag identifier
  uint8_t value; ///< Digital output value
  Action action; ///< Action
};

/* Plc struct */
typedef struct PlcDevice PlcDevice;
struct PlcDevice
{
  plc_in_t in[INPUT_COUNT]; ///< Inputs
  plc_in_t * din; ///< Digital inputs
  plc_in_t * ain; ///< Analog inputs
  plc_do_t dout[OUTPUT_COUNT]; ///< Digital outputs
  String nametag; ///< Nametag
  uint8_t ip[4]; ///< IP
};

/* Initialize plcDevice */
void _plcDeviceInit(PlcDevice *d)
{
  uint8_t i = 0;
  String n;

  clearObject(d);
  d->nametag = "PLC";

  d->din = d->in;
  d->ain = &d->in[6];

  // Initialize inputs and outputs
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    d->din[i].type = type_Digital;
    d->din[i].nametag = "DI" + String(i);

    d->ain[i].type = type_Analog;
    d->ain[i].nametag = "AI" + String(i);

    d->dout[i].nametag = "DO" + String(i);
  }
}

/* Digital output actions string */
String _actionsString(plc_do_t * d)
{
  String s = "";
  switch(d->action.type)
  {
    case action_None:
      s = s + "None";
      break;
    case action_Permanent:
      s = s + "Permanent";
      break;
    case action_Delay:
      s = s + "Delay";
      break;
    case action_Event:
      s = s + "Event";
      break;
  }

  s = s + " ";

  s = s + "i: " + String(d->action.input) + " ";
  s = s + "t: " + String(d->action.threshold) + " ";
  s = s + "ts: " + String(d->action.threshold_side) + " ";
  s = s + "d: " + String(d->action.delay_s) + " ";
  s = s + "e: " + String(d->action.elapsed_s) + " ";
  s = s + "dt: " + String(d->action.delay_triggered) + " ";
  return s;
}

/* Digital input types string name */
String _typeString(uint8_t n)
{
  switch(n)
  {
    case type_Digital: return "Digital"; break;
    case type_Counter: return "Counter"; break;
    case type_Analog: return "Analog"; break;
    default: return "None"; break;
  }
  return "None";
}

/* Ip string */
String _ipString(uint8_t * ip)
{
  String s = "";
  uint8_t i;
  for (i = 0; i < 3; i ++)
    s = s + String(ip[i]) + ".";
  s = s + String(ip[3]);
  return s;
}

/* Print device */
void _printPlcDevice(PlcDevice *d)
{
  Serial.println("Name: " + String(d->nametag));
  Serial.println("IP: " + _ipString(d->ip));

  uint8_t i;
  for (i = 0; i < INPUT_COUNT; i ++)
  {
    Serial.print("Input #" + String(i));
    Serial.print("\tName: " + d->in[i].nametag);
    Serial.print("\tType: " + _typeString(d->in[i].type));
    Serial.println("\tValue: " + String(d->in[i].value));
  }

  for(i = 0; i < OUTPUT_COUNT; i ++)
  {
    Serial.print("Output #" + String(i));
    Serial.print("\tName: " + d->dout[i].nametag);
    Serial.print("\tValue: " + String(d->dout[i].value));
    Serial.println("\tAction: " + _actionsString(&d->dout[i]));
  }
}

/* Digital read */
uint8_t _digitalRead(uint8_t d)
{
  return true;
}

/* Analog read */
float _analogRead(uint8_t a)
{
  return true;
}

/* Digital output */
void _digitalOutput(uint8_t d, uint8_t v)
{
  return;
}

/* Apply gain offset */
void _applyGof(plc_in_t * in)
{
  in->value = in->reading * in->gof.g + in->gof.o;
}


/* Update inputs */
void _updateInputs(PlcDevice * d)
{
  uint8_t i;
  for (i = 0; i < INPUT_COUNT; i ++)
  {
    switch(d->in[i].type)
    {
      case type_Digital:
        d->in[i].value = _digitalRead(i);
        break;
      case type_Counter:
        d->in[i].reading_ = d->in[i].value;
        d->in[i].reading = _digitalRead(i);
        if (d->in[i].reading_ != d->in[i].reading) // Edge
        {
          if (d->in[i].value == 1) // Rising edge
            d->in[i].value ++;
        }
        break;
      case type_Analog:
        d->in[i].reading = _analogRead(i);
        _applyGof(& d->in[i]);
        break;
    }
  }
}

/* Threshold activated */
uint8_t _thresholdPassed(plc_do_t * o, plc_in_t * in)
{
  return o->action.threshold_side == threshold_trigger_above ? in->value > o->action.threshold : in->value < o->action.threshold;
}


/* Find output action */
int8_t _findOutputAction(PlcDevice * d, uint8_t in)
{
  uint8_t i;
  for (i = 0; i < OUTPUT_COUNT; i++)
  {
    if (d->dout[i].action.input == in)
      return i;
  }
  return -1;
}

/* Perform actions */
void _performActions(PlcDevice * d)
{
  uint8_t i;
  for (i = 0; i < INPUT_COUNT; i ++)
  {
    // Find output that is related to this input
    int8_t o = _findOutputAction(d, i);
    if (o < 0) // No output related to this input
      break;

    switch(d->dout[o].action.type)
    {
      // Turn on output permanently
      case action_Permanent:
        if (_thresholdPassed(&d->dout[o], &d->in[i]))
          _digitalOutput(i, true);
        break;

      // Ouptut matches input
      case action_Event:
        _digitalOutput(i,_thresholdPassed(&d->dout[o], &d->in[i]));
        break;

      // Turn on output for a certain amount of time
      case action_Delay:
        if (_thresholdPassed(&d->dout[o], &d->in[i]))
          d->dout[o].action.delay_triggered = true;
        if (d->dout[o].action.delay_triggered)
        {
          d->dout[o].action.elapsed_s++;
          if (d->dout[o].action.elapsed_s < d->dout[o].action.delay_s)
          {
            _digitalOutput(i,true);
          }
          else
          {
            d->dout[o].action.elapsed_s = 0;
            d->dout[o].action.delay_triggered = false;
            _digitalOutput(i,false);
          }
        }
    }
  }
}

/* Update io */
void _updateIo(PlcDevice * d)
{
  _updateInputs(d);
  _performActions(d);
}

/* Write to non-volatile memory */
void _memorySave(PlcDevice * d)
{
  EEPROM.put(0, *d);
}

/* Read from non-volatile memory */
void _memoryRead(PlcDevice * d)
{
  EEPROM.get(0, *d);
}


/* Global variables */
PlcDevice plcDevice;

void setup()
{
  Serial.begin(9600);
  delay(100);


  // _plcDeviceInit(&plcDevice);
  _memoryRead(&plcDevice);
  _printPlcDevice(&plcDevice);

  _memorySave(&plcDevice);

  // plc_setup();
}

void loop()
{
  _updateIo(&plcDevice);
  delay(1000);
}

