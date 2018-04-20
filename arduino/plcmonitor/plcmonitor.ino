#include "plcshield.h"

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

/* Value union */
typedef union Value Value;
union Value {
   uint8_t d; ///< Digital
   uint32_t c; ///< Counter
   float a; ///< Analog
};  

/* Gain Offset Structure */
typedef struct plc_gof plc_gof;
struct plc_gof
{
  float g;
  float o;
};

/* Input struct */
typedef struct plc_in_t plc_in_t;
struct plc_in_t
{
  String nametag; ///< Nametag identifier
  uint8_t type; ///< Input type
  Value value; ///< Input value
  float treshold; ///< Threshold
  plc_gof gof; ///< Gain and offset
};

/* Digital output struct */
typedef struct plc_do_t plc_do_t;
struct plc_do_t
{
  String nametag; ///< Nametag identifier
  uint8_t value; ///< Digital output value
  uint8_t actions[INPUT_COUNT]; ///< Output action
};

/* Plc struct */
typedef struct PlcDevice PlcDevice;
struct PlcDevice
{
  plc_in_t in[INPUT_COUNT];
  plc_in_t * din;
  plc_in_t * ain;
  plc_do_t dout[OUTPUT_COUNT];
  String nametag;
  uint8_t ip[4];
};

/* Value string */
String _valueString(plc_in_t * p)
{
  switch (p->type)
  {
    case type_Digital: return String(p->value.d); break;
    case type_Analog: return String(p->value.a); break;
    case type_Counter: return String(p->value.c); break;
  }
  return "None";
}

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
  String n = "";
  uint8_t i;
  for (i = 0; i < INPUT_COUNT; i ++)
  {
    n = n + String(d->actions[i]) + " ";
  }
  return n;
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
    Serial.println("\tValue: " + _valueString(&d->in[i])); 
  }

  for(i = 0; i < OUTPUT_COUNT; i ++)
  {
    Serial.print("Output #" + String(i));
    Serial.print("\tName: " + d->dout[i].nametag);
    Serial.print("\tValue: " + String(d->dout[i].value));
    Serial.println("\tActions: " + _actionsString(&d->dout[i]));
  }
}

/* Update inputs */
void _updateInputs(PlcDevice * d)
{
  uint8_t i;
  for (i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    d->din[i].value.d = plc_digitalRead(i+1); // Count starts in 1   
    d->ain[i].value.a = plc_analogRead(i+1); 
  }
}

/* Update outputs */
void _updateOutputs(PlcDevice * d)
{
  uint8_t i;
  for (i = 0; i < OUTPUT_COUNT; i ++)
  {
    plc_digitalWrite(i+1, d->dout[i].value);
  }
}

void _outputStep(PlcDevice * d)
{
  uint8_t i, j;
  for (i = 0; i < OUTPUT_COUNT; i++)
  {
    for (j = 0; j < INPUT_COUNT; j++)
    {
      switch(d->dout[i].actions[j])
      {
        case action_Permanent:break;
        case action_Event: break;
        case action_Delay: break;
        default: break;
      }
    }
  }
}

/* Update io */
void _updateIo(PlcDevice * d)
{
  _updateInputs(d);
  _updateOutputs(d);
}

/* Global variables */
PlcDevice plcDevice;

void setup() 
{
  Serial.begin(9600);
  delay(100);

  
  _plcDeviceInit(&plcDevice);
  _printPlcDevice(&plcDevice);

  plc_setup();
}

void loop() 
{
  _updateIo(&plcDevice);
}
