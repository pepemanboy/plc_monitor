#ifndef PLC_ETHERNET_H
#define PLC_ETHERNET_H

/*
 * PLC Server connectivity and communication library
 *
 * Author: pepemanboy
*/

#include <Ethernet.h>
#include <SPI.h>
#include "plc_common.h"
#include <MD5.h>

/* PLC ID */
#ifndef PLC_ID
#error PLEASE DEFINE "PLC_ID" BEFORE INCLUDING PLC_ETHERNET.H
#endif // PLC_ID

/* Device IP */
#ifndef PLC_IP
#error PLEASE DEFINE "PLC_IP" BEFORE INCLUDING PLC_ETHERNET.H
#endif // IP

/* MAC ADDRESS */
#ifndef PLC_MAC
#error PLEASE DEFINE "PLC_MAC" BEFORE INCLUDING PLC_ETHERNET.H
#endif // MAC

/* Timeout settings */
#define PLC_TIMEOUT_MS (1000)
#define PLC_TIMEOUT_DELAY_MS (1)

/* Delays */
#define PLC_LOG_INPUT_DELAY_MS (500)

/* Server settings */
#define PLC_SERVER {192, 185, 131, 113}
#define PLC_PORT 80

/* Device settings */
const byte mac[] = PLC_MAC; // Mac Address unico. Calcomania atras del Arduino.
const byte ip[] = PLC_IP; // IP fija del Arduino. Ver Ip de computadora en red. Eg (192.168.1.55), y usar los primeros 3 numeros y el 4to numero escogerlo. Eg (192.168.1.69)

/* Pepemanboy.com server */
const byte SERVER[] = PLC_SERVER; // 20x faster to use ip than name.
const uint8_t PORT = PLC_PORT;

/* Global string buffer for comm */
String str_buf;

/* Communication protocol */
const char comm_opening = '{';
const char comm_closing = '}';

/* Global ethernet client */
EthernetClient client;

/* Data types */
enum data_types
{
  type_int = 1, ///< Int 32
  type_float, ///< Float
  type_uint8, ///< Uint 8
  type_long, ///< Long
  type_ulong, ///< Unsigned long
};

/* Check the integrity of a message using md5 checksum
 *  
 *  @return true if checksum is correct, false otherwise
 */
bool checkIntegrity()
{
  // Calculate md5
  int l = str_buf.length() + 1; // Include null termination
  char tmp[l];
  str_buf.substring(0,str_buf.indexOf("md5")).toCharArray(tmp,l);
  unsigned char* hash=MD5::make_hash(tmp);
  char *md5str = MD5::make_digest(hash, 16);
  String calculated_md5 = String(md5str);

  // Obtain md5 from original string
  int a = str_buf.indexOf("md5(");
  if (a < 0) return false;
  a += 4;

  int b = str_buf.indexOf(")",a);
  if (b < 0) return false;

  String original_md5 = str_buf.substring(a,b);

  bool eq = original_md5.equals(calculated_md5);

  return eq;
}

/*  Get an array from str_buf
 *
 *  @param arr destination array
 *  @param type type of destination array
 *  @param key element key
 *  @param n number of elements
 *  @return error code
 */
uint8_t _getArray(void * arr, uint8_t type, String key, uint8_t n)
{
  int a,b;
  b = str_buf.indexOf(key);
  if (b < 0)
    return Error;
  a = b + key.length();
  uint8_t i;
  for(i = 0; i < n; ++i)
  {
    b = i < n - 1 ? str_buf.indexOf(',',a) : str_buf.indexOf(')',a);
    if (b < 0)
      return Error;
    switch(type)
    {
      case type_uint8: ((uint8_t *)arr)[i] = (uint8_t)str_buf.substring(a,b).toInt(); break;
      case type_int: ((int *)arr)[i] = str_buf.substring(a,b).toInt(); break;
      case type_float: ((float *)arr)[i] = str_buf.substring(a,b).toFloat(); break;
      case type_long: ((long *)arr)[i] = (long)str_buf.substring(a,b).toInt(); break;
    }
    a = b + 1;
  }
  return Ok;
}

/* Wait for client to be available or timeout
 *
 * @return error code
*/
uint8_t _waitClientAvailable()
{
  int i = 0;
  int d = PLC_TIMEOUT_MS/PLC_TIMEOUT_DELAY_MS;
  while(!client.available())
  {
    delay(PLC_TIMEOUT_DELAY_MS);
    ++i;
    if(i > d)
      return Error_timeout;
  }
  return Ok;
}

/* Wait for client to disconnect or timeout
 *
 * @return error code
*/
uint8_t _waitClientDisconnect()
{
  int i = 0;
  int d = PLC_TIMEOUT_MS/PLC_TIMEOUT_DELAY_MS;
  while(client.connected())
  {
    delay(PLC_TIMEOUT_DELAY_MS);
    ++i;
    if(i > d)
      return Error_timeout;
  }
  return Ok;
}

/* Check for errors in response
 */
uint8_t checkErrors()
{
  return str_buf.indexOf("error(OK)") >= 0 ? Ok : Error;
}

/* Check if character is hex
 */
bool isHex(char x)
{
  return ((x >= '0' && x <= '9') || (x >= 'a' && x <= 'f'));
}

/* Send a POST request to the server
 *
 * @param url php file address from /plcmonitor/
 * @param params POST arguments
 * @return error code
*/
uint8_t _post(String url, String params)
{
  // Connect to server
  uint8_t r = client.connect(SERVER, PORT);
  if (r != 1)
  {
    client.stop();
    return Error_connect;
  }

  // Send request
  client.print("POST /plcmonitor/");
  client.print(url);
  client.println(" HTTP/1.1");
  client.println("Host: www.pepemanboy.com");
  client.println("User-Agent: Arduino/1.0");
  client.println("Connection: close");
  client.println("Content-Type: application/x-www-form-urlencoded;");
  client.println("Authorization: Basic cGVwZW1hbmJveTpwZXBlMTk5NSo="); // Base 64 encoded user:pass
  client.print("Content-Length: ");
  client.println(params.length());
  client.println();
  client.println(params);
  client.flush();

  // Wait for response
  r = _waitClientAvailable();
  if(r != Ok)
  {
    client.stop();
    return r;
  }

  // Read response
  str_buf = "";
  /*  
  bool start = false;
  while (client.available()) {
    // Read character
    char c = client.read();
    //Serial.print(c);
    // Pre-message
    if(!start)
    {
      if(c == comm_opening)
        start = true;
    }
    // Message
    else
    {
      if (c == comm_closing)
      {
        start = false;
        continue; // Message ends
      }
      str_buf += c;
    }
  }*/

  // Read raw text from request
  String str_buf_1 = "";
  while (client.available())
  {
    // Read character
    char c = client.read();
    str_buf_1 += c;
  }

  // Search for message
  int a,b,n;
  const String header_end = "Connection: close";
  a = str_buf_1.indexOf(header_end) + header_end.length();
  for (b = a ; b < str_buf_1.length(); b ++)
    if (isHex(str_buf_1.charAt(b))) break;

  while(true)
  {
    // Extract number of following bytes
    String n_str = str_buf_1.substring(b, str_buf_1.indexOf("\r\n",b));
    char n_char[n_str.length() + 1];
    n_str.toCharArray(n_char, n_str.length() + 1);
    n = strtol(n_char,0,16);
    if(n == 0) break;
    // Extract line of text
    a = str_buf_1.indexOf("\r\n",b) + 2;
    b = str_buf_1.indexOf("\r\n",a);
    str_buf += str_buf_1.substring(a,b);
    b = b + 2;
  }

  // Remove opening and closing braces
  str_buf = str_buf.substring(1,str_buf.length()-1);

  // Wait for server to terminate
  r = _waitClientDisconnect();
  if(r != Ok)
  {
    client.stop();
    return r;
  }

  // Disconnect
  client.stop();
  delay(100);
  return Ok;
}

/* Get outputs
 * pepemanboy.com/plcmonitor/control_outputs.php
 * Args: plc_number = ID, operation = "get"
 * Returns: digiif (checkErrors() != Ok)
    return Error;tal_outputs(0,0,0,0,0,0,0)
 *
 * @param e placeholder for outputs array
 * @return error code
*/
uint8_t getOutputs(bool * o)
{
	uint8_t r = _post("control_outputs.php","plc_number=" + String(PLC_ID) + "&operation=get");
  if (r != Ok)
    return r;
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;
  uint8_t i = 0;
  for (i = 0; i < 6; i ++)
    o[i] = str_buf[String("digital_outputs(").length() + 2 * i] == '1';
  return Ok;
}

/* Set outputs
 * pepemanboy.com/plcmonitor/control_outputs.php
 * Args: plc_number = ID, operation = "set", do1 = 0, do2 = 1, ... , arduino = true
 * Returns: error(OK)
 *
 * @param dout digital output array
*/
uint8_t setOutputs(bool * dout)
{
  str_buf = "plc_number=" + String(PLC_ID) + "&operation=set&arduino=true&";
  uint8_t i;
  for(i = 0; i < 6; i ++)
  {
    str_buf += "do" + String(i+1) + "=" + String(dout[i]);
    if(i != 5) str_buf += "&";
  }
  uint8_t r = _post("control_outputs.php", str_buf);  
  if (checkErrors() != Ok)
    return Error;
  return r;
}

/* Set inputs
 * pepemanboy.com/plcmonitor/control_inputs.php
 * Args: plc_number = ID, operation = "set", di1 = 0, di2 = 1, ... , ai1 = 0, ai2 = 1024, ...
 * Returns: error(OK)
 *
 * @param di digital input array
 * @param analog input array
*/
uint8_t setInputs(bool * di, int * ai)
{
  str_buf = "plc_number=" + String(PLC_ID) + "&operation=set&";
  uint8_t i;
  for(i = 0; i < 6; i ++)
  {
    str_buf += "di" + String(i+1) + "=" + String(di[i]) + "&";
    str_buf += "ai" + String(i+1) + "=" + String(ai[i]);
    if(i != 5) str_buf += "&";
  }
	uint8_t r = _post("control_inputs.php", str_buf);
  if (checkErrors() != Ok)
    return Error;
  return r;
}

/* Log input
 * pepemanboy.com/plcmonitor/viz_graph.php
 * Args: plc_number = ID, signal_number = 1-6, signal_type = di/ai, operation = set, value = val
 *
 * @param n input number
 * @param type input type
 * @param val value
 * @return error code
*/
uint8_t logInput(uint8_t n, uint8_t type, float val)
{
	str_buf = "plc_number=" + String(PLC_ID) + "&operation=set&signal_number=" + String(n) + "&signal_type=" + (type == input_Analog ? "ai" : "di") + "&value=" + String(val);
	uint8_t r = _post("viz_graph.php", str_buf);
	delay(PLC_LOG_INPUT_DELAY_MS);
  if (checkErrors() != Ok)
    return Error;
  return r;
}

/* Get actions
 * pepemanboy.com/plcmonitor/viz_actions.php
 * Args: plc_number = ID, operation = "get", arduino = "true"
 * Returns: n(), inputs(), thresholds(), updowns(), outputs(), notification_interval_s(), action_types(), delays_s()
 *
 * @param num
 * @param inputs_types
 * @param inputs_numbers
 * @param ids
 * @param thresholds
 * @param updowns
 * @param outputs
 * @param notification_interval_s
 * @param action_types
 * @param delays_S
 * @return error code
*/
uint8_t getActions(uint8_t * num, uint8_t * inputs_types, uint8_t * inputs_numbers, uint8_t * ids, float * thresholds, uint8_t * updowns, uint8_t * outputs, long * notification_interval_s, uint8_t * action_types, long * delays_s)
{
	uint8_t r = _post("viz_action.php","plc_number=" + String(PLC_ID) + "&operation=get&arduino=true");
  if (r != Ok)
    return r;    
  plcDebug(str_buf);
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;


  // Get n
  int a = str_buf.indexOf("n(") + 2;
  if (a < 0)
    return Error;
  int b = str_buf.indexOf(")", a);
  if (b < 0)
    return Error;
  int n = str_buf.substring(a,b).toInt();
  *num = n;
  
  // Get inputs types
  r = _getArray(inputs_types,type_uint8,"inputs_types(",n);
  if (r != Ok)
    return r;
   
  // Get inputs numbers
  r = _getArray(inputs_numbers,type_uint8,"inputs_numbers(",n);
  if (r != Ok)
    return r;

  // Get ids
  r = _getArray(ids,type_uint8,"ids(",n);
  if (r != Ok)
    return r;

  // Get thresholds
  r = _getArray(thresholds,type_float,"thresholds(",n);
  if (r != Ok)
    return r;

  // Get updowns
  r = _getArray(updowns,type_uint8,"updowns(",n);
  if (r != Ok)
    return r;

  // Get outputs
  r = _getArray(outputs,type_uint8,"outputs(",n);
  if (r != Ok)
    return r;

  // Get notification interval
  r = _getArray(notification_interval_s,type_long,"notification_intervals_s(",n);
  if (r != Ok)
    return r;

  // Get action types
  r = _getArray(action_types,type_uint8,"action_types(",n);
  if (r != Ok)
    return r;

  // Get delays
  r = _getArray(delays_s,type_long,"delays_s(",n);
  if (r != Ok)
    return r;

  return Ok;
}

/* Get config
 * pepemanboy.com/plcmonitor/config_program.php
 * Args: plc_number = ID, operation = "get", arduino = "true"
 * Returns:di1(freq,count)ai1(freq,gain,ofs)...
 *
 * @param dif digital input frequencies
 * @param dic digital input counts
 * @param aif analog input frequencies
 * @param aig analog input gains
 * @param aio analog input offsets
 * @return error code
*/
uint8_t getConfig(int * dif, uint8_t * dic, int * aif, float * aig, float * aio)
{
	uint8_t r = _post("config_program.php","plc_number=" + String(PLC_ID) + "&operation=get&arduino=true");
  if (r != Ok)
    return r;
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;
  float float_buf[3];
  uint8_t i = 0;
  for(i = 0; i < 6; ++i)
  {
    r = _getArray(float_buf,type_float,"di" + String(i + 1) + "(",2);
    if (r != Ok)
      return r;
    dif[i] = float_buf[0];
    dic[i] = float_buf[1];

    r = _getArray(float_buf,type_float,"ai" + String(i + 1) + "(",3);
    if (r != Ok)
      return r;
    aif[i] = float_buf[0];
    aig[i] = float_buf[1];
    aio[i] = float_buf[2];
  }
  return Ok;
}

/* Initialization.
 * Initialize ethernet module
 *
 * @return error code
*/
uint8_t initEthernet()
{
  plcDebug("Connecting to ethernet");
  Ethernet.begin(mac /*, ip*/); // Without IP, about 20 seconds. With IP, about 1 second.
  plcDebug("Connected to ethernet. IP = " + Ethernet.localIP());
  return Ok;
}

/* Test */
void testEthernet()
{  
  uint8_t i = 0;
  uint8_t r;
  
  // Initialization  
  // Serial.begin(9600);
  Serial.println("Ethernet begin");
  initEthernet();
  Serial.println(Ethernet.localIP());

  // Conectivity test
  Serial.println("Prueba");
  r = _post("test.php", "txt=soy el pepemanboy");
  Serial.println("Error = " + String(r));
  Serial.println(str_buf);
  Serial.println();
  
  // Get outputs
  bool outputs[6];
  Serial.println("Getting outputs");
  r = getOutputs(outputs);
  Serial.println("Error = " + String(r));
  Serial.print("Outputs = ");
  for(i = 0; i < 6; i ++)
  {
    Serial.print(outputs[i]);
    if(i != 5)  Serial.print(",");
  }
  Serial.println();  
  Serial.println();

  // Set inputs 
  bool din[] = {1,0,1,1,0,0};
  int ain[] = {1,2,4,8,16,32};
  Serial.println("Set inputs");
  r = setInputs(din,ain);
  Serial.println("Error = " + String(r));
  Serial.println();

  // Log inputs
  float x = 0;
  for(i = 0; i < 10; i ++)
  {
    Serial.println("Logging input");
    r = logInput(3,1,sin(x));
    x = x + 0.1;
    Serial.println("Error = " + String(r));
    delay(500);
  } 
  Serial.println();

  // Get config
  int dif[6];
  uint8_t dic[6];
  int aif[6];
  float aig[6];
  float aio[6];
  Serial.println("Querying config");
  r = getConfig(dif,dic,aif,aig,aio);
  Serial.println("Error = " + String(r));
  for (i = 0; i < 6; i ++)
  {
    Serial.println("DI" + String(i+1) + " freq = " + String(dif[i]) + " counter = " + String(dic[i]));
    Serial.println("AI" + String(i+1) + " freq = " + String(aif[i]) + " gain = " + String(aig[i]) + " offs = " + String(aio[i]));
  }
  Serial.println();

  // Get actions
  uint8_t MAX_ACTIONS = 6;
  uint8_t n;
  uint8_t inputs_types[MAX_ACTIONS];
  uint8_t inputs_numbers[MAX_ACTIONS];
  uint8_t ids[MAX_ACTIONS];
  float thresholds[MAX_ACTIONS];
  uint8_t updowns[MAX_ACTIONS];
  uint8_t outputs2[MAX_ACTIONS];
  long notification_interval_s[MAX_ACTIONS];
  uint8_t action_types[MAX_ACTIONS];
  long delays_s[MAX_ACTIONS];
  Serial.println("Querying actions");
  r = getActions(&n,inputs_types, inputs_numbers,ids,thresholds,updowns,outputs2,notification_interval_s,action_types,delays_s);
  Serial.println("N = " + String(n));
  Serial.println("Error = " + String(r));
  for (i = 0; i < n; ++i)
  {
    Serial.print("input type = " + String(inputs_types[i]));
    Serial.print(" input number = " + String(inputs_numbers[i]));  
    Serial.print(" th = " + String(thresholds[i]));
    Serial.print(" ud = " + String(updowns[i]));
    Serial.print(" output = " + String(outputs2[i]));
    Serial.print(" notif = " + String(notification_interval_s[i]));
    Serial.print(" at = " + String(action_types[i]));
    Serial.println(" d = " + String(delays_s[i]));
  }
	Serial.println();
}

#endif // PLC_ETHERNET_H
