/*
 * PLC Server connectivity and communication library
 * 
 * Do not modify.
 *
 * Author: pepemanboy
 * Email: pepe_ciro@hotmail.com
*/

#ifndef PLC_ETHERNET_H
#define PLC_ETHERNET_H

#include <Ethernet.h>
#include <SPI.h>
#include <string.h>
#include "plc_common.h"
#include "plc_monitor.h"

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
#define PLC_TIMEOUT_MS (3000) /* Tiempo para esperar a que cargue una pagina antes de reportar error */
#define PLC_TIMEOUT_DELAY_MS (10)

/* Delays */
#define PLC_LOG_INPUT_DELAY_MS (0)
#define PLC_POST_DELAY (0)

/* Retry settings */
#define PLC_MAX_RETRY (5)
#define PLC_MAX_ERRORS (5)

/* Module errors */
uint8_t ethernet_error_count = 0;

/* Device settings */
uint8_t mac[] = PLC_MAC; // Mac Address unico.
const byte ip[] = PLC_IP; // IP fija del Arduino. Ver Ip de computadora en red. Eg (192.168.1.55), y usar los primeros 3 numeros y el 4to numero escogerlo. Eg (192.168.1.69)
const byte dns[] = PLC_DNS; // DNS para conectar al modem. Eg 8.8.8.8 (Google)
const byte gateway[] = PLC_GATEWAY; // Gateway del modem
const byte subnet[] = PLC_SUBNET; // Subnet del modem

/* Pepemanboy.com server */
const byte SERVER[] = PLC_SERVER; // 20x faster to use ip than name.
const uint8_t PORT = PLC_PORT;

/* Communication protocol */
const char comm_opening = '{';
const char comm_closing = '}';

/* Buffer size */
#define QUERY_BUFFER_SIZE 200
#define REPLY_BUFFER_SIZE 500

/* Packet header end */
char header_end[] = PLC_SERVER_HEADER_END;

/* Global char buffer */
char g_buf[REPLY_BUFFER_SIZE];

/* Global ethernet client */
EthernetClient client;

/* Ethernet watchdog for consecutive errors*/
uint8_t ethernetWatchdog(bool b)
{
  ethernet_error_count = b ? ethernet_error_count + 1 : 0;
  if (ethernet_error_count > PLC_MAX_ERRORS)
  {
    lcdText("Be right back!");
    delay(1000);
    softReset();
  }
}

uint8_t ethernetResetWatchdog()
{
  ethernet_error_count = 0;
}

/* Mantain ethernet connection */
uint8_t ethernetMaintain()
{
  uint8_t r = Ok;
  r = Ethernet.maintain() & 0x01 ? Error_maintain : Ok;
  return r; 
}

/* Todo: implement */
bool checkIntegrity()
{
  return true;
}

/*  Get an array from str_buf
 *
 *  @param arr destination array
 *  @param type type of destination array
 *  @param key element key
 *  @param n number of elements
 *  @return error code
 */
uint8_t _getArray(void * arr, uint8_t type, const char * key, uint8_t n)
{
  String str_buf = String(g_buf);
  int a,b;
  b = str_buf.indexOf(key);
  if (b < 0)
    return Error;
  a = b + strlen(key);
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
      return Error_disconnect;
  }
  return Ok;
}

/* Check for errors in response
 *  
 *  @return error code
 */
uint8_t checkErrors()
{
  return strstr(g_buf,"error(OK)") ? Ok : Error;
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
uint8_t _post(const char * url, const char * params)
{
  _internalUpdate();
  // Connect to server
  uint8_t r = client.connect(SERVER, PORT);
  if (r != 1)
  {
    plcDebug("Error de conexion");
    client.stop();
    return Error_connect;
  }

  // Send request
  client.print(F("POST /"));
  client.print(url);
  client.println(F(" HTTP/1.1"));
  client.print(F("Host: "));
  client.println(F(PLC_WEBSITE));
  client.println(F("User-Agent: Arduino/1.0"));
  client.println(F("Connection: close"));
  client.println(F("Content-Type: application/x-www-form-urlencoded;"));
  client.print(F("Authorization: Basic ")); // Base 64 encoded user:pass
  client.println(F(PLC_WEBSITE_USERPASS));
  client.print(F("Content-Length: "));
  client.println(strlen(params));
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

  // Buffer to store response
  char char_buf[REPLY_BUFFER_SIZE] = "";

  while (client.connected())
  {
    while (client.available())
    {
      char c = client.read();
      strcat_c(char_buf,c);
      // Serial.print(c);
    }
  }
  
  char b_[REPLY_BUFFER_SIZE] = "";  
  char * c_;

  if(strstr(char_buf,"Transfer-Encoding: chunked"))
  {
    char n_[4] = "";
    int nb;
    int i = 0;

    char *a_;
    // a_ = strstr(char_buf,"Connection: close") + 17;
    
    a_ = strstr(char_buf, header_end) + strlen(header_end);    
    if(!a_)
      return Error;

    char *b_; // End of char_buf
    b_ = char_buf + strlen(char_buf);

    while(!isHex(*a_) && a_ <= b_)
      a_++;
    
    while(true)
    {
      // Initialize n_
      memset(n_,0,4);
        
      // Number of following bytes
      while(*a_ != '\r')
      {
        strcat_c(n_,*a_);
        if (++i > 500)
        {
          client.stop();
          return Error;
        }
        a_++;
      }
      
      nb = strtol(n_,0,16);
      if (nb == 0) break;
      
      // Extract line of text    
      a_+=2;
      while(*a_ != '\r')
      {
        strcat_c(b_,*a_);      
        if (++i > 500)
        {
          client.stop();
          return Error;
        }
        a_++;
      }
      a_+=2;    
    }
    // Remove opening and closing braces
    c_ = b_;  
    c_++;
    c_[strlen(c_)-1] = '\0';
  }
  else // Not chunked
  {
    char * p = strstr(char_buf,"{");
    if (!p)
    {
      client.stop();      
      return Error_chunked;
    }
    p++;
    p = strtok(p,"}");
    if (!p)
    {
      client.stop();
      return Error_chunked;
    }
    strcat(b_,p);
    memset(p+strlen(p),'}',1); // Restore token  
    c_ = b_;
  }

  memset(g_buf,0,REPLY_BUFFER_SIZE);
  memcpy(g_buf,c_,strlen(c_));

  // Wait for server to terminate
  r = _waitClientDisconnect();
  if(r != Ok)
  {
    client.stop();
    return r;
  }

  // Disconnect
  client.stop();
  delay(PLC_POST_DELAY);
  return Ok;
}

/* Retry post request until valid */
uint8_t _retryPost(const char * url, const char * params, const char * msg)
{
  uint8_t r = Error;
  while (r != Ok)
  {
    r = _post(url,params);
    lcdText(String(msg) + errorString(r));
    ethernetWatchdog(r != Ok && r != Error_chunked); // Veces totales que puede fallar
  }
  return r;
}

/* Get resets
 * pepemanboy.com/plcmonitor/reset_counter.php
 * Args: plc_number = ID, operation = "get"
 * Returns: resets(-1,-1,-1,-1,-1,-1)
 *
 * @param e placeholder for outputs array
 * @return error code
*/
uint8_t getResets(int * rr)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q,"plc_number=%d&operation=get&arduino=true",PLC_ID);
  uint8_t r = _retryPost("reset_counter.php",q,"cnt_res: ");
  if (r != Ok)
    return r;  
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;
  
  // Get resets
  r = _getArray(rr,type_int,"resets(",6);
  if (r != Ok)
    return r;

  return Ok;
}

/* Get digital inputs
 * pepemanboy.com/plcmonitor/control_inputs.php
 * Args: plc_number = ID, operation = "get", arduino = true
 * Returns: di(0,0,0,0,0,0)
 *
 * @param e placeholder for outputs array
 * @return error code
*/
uint8_t getDigitalInputs(int * di)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q,"plc_number=%d&operation=get&arduino=true",PLC_ID);
  uint8_t r = _retryPost("control_inputs.php",q,"get_in: ");
  if (r != Ok)
    return r;  
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;
  
  // Get resets
  r = _getArray(di,type_int,"di(",6);
  if (r != Ok)
    return r;

  return Ok;
}

/* Get outputs
 * pepemanboy.com/plcmonitor/control_outputs.php
 * Args: plc_number = ID, operation = "get"
 * Returns: digital_outputs(0,0,0,0,0,0,0)
 *
 * @param e placeholder for outputs array
 * @return error code
*/
uint8_t getOutputs(bool * o)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q,"plc_number=%d&operation=get",PLC_ID);
	uint8_t r = _retryPost("control_outputs.php",q,"get_out: ");
  if (r != Ok)
    return r;
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;
    
  char * p;
  p = strstr(g_buf,"digital_outputs(");
  if (!p)
    return Error;
    
  p += strlen("digital_outputs(");
  for (uint8_t i = 0; i < 6; i ++)
  {
    o[i] = *p == '1';
    if (i < 5) p += 2;
  }
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
  char p [QUERY_BUFFER_SIZE] = "";
  sprintf(p,"plc_number=%d&operation=set&arduino=true&",PLC_ID);
  
  for(uint8_t i = 0; i < 6; i ++)
  {
    sprintf(p+strlen(p),"do%d=%d",i+1,dout[i] ? 1 : 0);
    if(i != 5) strcat(p,"&");
  }
  uint8_t r = _retryPost("control_outputs.php", p,"set_out: ");  
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
uint8_t setInputs(int * di, int * ai)
{
  char p[QUERY_BUFFER_SIZE];
  sprintf(p,"plc_number=%d&operation=set&",PLC_ID);
  for(uint8_t i = 0; i < 6; i ++)
  {
    sprintf(p+strlen(p),"di%d=%d&ai%d=%d",i+1,di[i],i+1,ai[i]);
    if(i != 5) strcat(p,"&");
  }
	uint8_t r = _retryPost("control_inputs.php", p, "set_in: ");
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
  char p[QUERY_BUFFER_SIZE] = "";
  sprintf(p,"plc_number=%d&operation=set&signal_number=%d&signal_type=",PLC_ID,n);
  strcat(p,type == input_Analog ? "ai" : "di");
  strcat(p,"&value=");
  dtostrf(val,3,2,p+strlen(p));
	uint8_t r = _retryPost("viz_graph.php", p, "log_in: ");
	delay(PLC_LOG_INPUT_DELAY_MS);
  if (checkErrors() != Ok)
    return Error;
  return r;
}

/* Send email
 *  pepemanboy.com/plcmonitor/viz_actions.php
 *  Args: plc_number = ID, operation = "email", action_id = x
 *  Returns: error code
 *  
 *  @param action_id action to send
 *  @return error code  
 */
 uint8_t sendEmail(uint8_t action_id)
 {
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q,"plc_number=%d&operation=email&action_id=%d", PLC_ID, action_id);
  uint8_t r = _retryPost("viz_action.php",q, "send_em: ");
  if (r != Ok)
    return r;  
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;

  return Ok;
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
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q,"plc_number=%d&operation=get&arduino=true",PLC_ID);
	uint8_t r = _retryPost("viz_action.php",q, "get_ac: ");
  if (r != Ok)
    return r;  
  if (checkErrors() != Ok)
    return Error;
  if (!checkIntegrity())
    return Error_checksum;

  // Get n
  char * p;
  p = strstr(g_buf,"n(");
  if (!p) 
    return Error;
  p += strlen("n(");
  p = strtok(p,")");
  if (!p)
    return Error;
  int n = strtol(p,0,10);  
  memset(p+strlen(p),')',1); // Restore strtok
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
uint8_t getConfig(uint32_t * dif, uint8_t * dic, uint32_t * aif, float * aig, float * aio)
{
  char q [QUERY_BUFFER_SIZE];
  sprintf(q,"plc_number=%d&operation=get&arduino=true",PLC_ID);
	uint8_t r = _retryPost("config_program.php",q, "get_cfg: ");
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
    memcpy(q,0,sizeof(q));
    sprintf(q,"di%d(",i+1);
    r = _getArray(float_buf,type_float,q,2);
    if (r != Ok)
      return r;
    dif[i] = float_buf[0];
    dic[i] = float_buf[1];

    memcpy(q,0,sizeof(q));
    sprintf(q,"ai%d(",i+1);
    r = _getArray(float_buf,type_float,q,3);
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
  // Disable SD
  pinMode(4,OUTPUT);
  digitalWrite(4,HIGH);
  plcDebug("Connecting to ethernet");
  #ifdef PLC_DYNAMIC_IP
  Ethernet.begin(mac);
  #else
  Ethernet.begin(mac , ip, dns, gateway, subnet); // Without IP, about 20 seconds. With IP, about 1 second.
  #endif
  plcDebug("Connected to ethernet.");
  return Ok;
}

#endif // PLC_ETHERNET_H
