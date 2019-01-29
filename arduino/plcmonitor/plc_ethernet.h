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

#include <SPI.h>
#include <string.h>
#include "plc_common.h"
#include "plc_monitor.h"
#include <ArduinoJson.h>

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
const byte plc_dns[] = PLC_DNS; // DNS para conectar al modem. Eg 8.8.8.8 (Google)
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
const char header_end[] = PLC_SERVER_HEADER_END;

/* Global char buffer */
char g_buf[REPLY_BUFFER_SIZE];

/* Global ethernet client */
EthernetClient client;

/* Power on variable */
uint8_t g_power_on = 1;

/* Read timestamp */
unsigned long g_readTimestamp = 0;

// Allocate JsonBuffer
// Use arduinojson.org/assistant to compute the capacity.
const size_t capacity = 6*JSON_OBJECT_SIZE(2) + 7*JSON_OBJECT_SIZE(3) + 2*JSON_OBJECT_SIZE(6) + 60;
DynamicJsonBuffer g_jsonBuffer(capacity);

/* Ethernet watchdog for consecutive errors*/
void ethernetWatchdog(bool b)
{
  ethernet_error_count = b ? ethernet_error_count + 1 : 0;
  if (ethernet_error_count > PLC_MAX_ERRORS)
  {
    lcdText("Be right back!");
    delay(1000);
    softReset();
  }
}

/* Reset ethernet watchdog */
void ethernetResetWatchdog()
{
  ethernet_error_count = 0;
}

/* Mantain ethernet connection */
res_t ethernetMaintain()
{  
  int8_t res = 0;
  res = Ethernet.maintain();
  if (res != 0)
  {
    switch(res)
    {
      case 1: lcdText("Renew fail"); break;
      case 2: lcdText("Renew success"); break;
      case 3: lcdText("Rebind fail"); break;
      case 4: lcdText("Rebind success"); break;
      default: lcdText("Unknown"); break;
    }
    delay(500);
  }  
  res_t r = res & 0x01 ? Error_maintain : Ok;
  return r; 
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
  char * b;
  char * a;
  char * ending = g_buf + sizeof(g_buf);
  b = strstr(g_buf,key);
  if (!b)
    return Error;
  b = b + strlen(key);
  for(uint8_t i = 0; i < n; ++i)
  {
    char d = i < (n - 1) ? ',' : ')';
    char d_[2] = "";
    strcat_c(d_,d);
        
    if (b > ending)
      return Error;
      
    a = strtok(b,d_);
    if (!a) 
      return Error;
      
    switch(type)
    {
      case type_uint8: ((uint8_t *)arr)[i] = (uint8_t)strtol(a,0,10); break;
      case type_int8: ((int8_t *)arr)[i] = (int8_t)strtol(a,0,10); break;
      case type_uint16: ((uint16_t *)arr)[i] = (uint16_t)strtol(a,0,10); break;
      case type_int16: ((int16_t *)arr)[i] = (int16_t)strtol(a,0,10); break;
      case type_uint32: ((uint32_t *)arr)[i] = (uint32_t)strtol(a,0,10); break;
      case type_int32: ((int32_t *)arr)[i] = (int32_t)strtol(a,0,10); break;
      case type_float: ((float *)arr)[i] = (float)atof(a); break;
    }
    b = a + strlen(a) + 1;
    memset(a+strlen(a),d,1); // Restore token  
  }
  return Ok;
}

/* Wait for client to be available or timeout
 *
 * @return error code
*/
uint8_t _waitClientAvailable()
{
  int16_t i = 0;
  int16_t d = PLC_TIMEOUT_MS/PLC_TIMEOUT_DELAY_MS;
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
  int16_t i = 0;
  int16_t d = PLC_TIMEOUT_MS/PLC_TIMEOUT_DELAY_MS;
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

res_t _postJson(const char * url, const char * params)
{  
  res_t r;
  
  r = ethernetMaintain(); 
  if (r != Ok)
  {
    plcDebug("_postJson: ethernetMaintain()", r);
    return r;  
  } 
    
  _internalUpdate();
  
  // Connect to server
  #ifdef PLC_ETHERNET_VERSION_2
  client.setConnectionTimeout(PLC_TIMEOUT_MS);
  #endif

  int8_t res = client.connect(SERVER, PORT);
  if (res != 1)
  {
    plcDebug("_postJson: client.connect(SERVER, PORT)", res);
    client.stop();
    return Error_connect;
  }

  // Send request
  client.print(F("POST "));
  client.print(F(PLC_WEBSITE_DIRECTORY));
  client.print(url);
  client.println(F(" HTTP/1.0")); // Use 1.0 to avoid chunked data
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
    plcDebug("_postJson: _waitClientAvailable()", r);
    client.stop();
    return r;
  }
  
  // Check HTTP status
  memset(g_buf, 0, sizeof(g_buf));
  client.readBytesUntil('\r', g_buf, sizeof(g_buf));  
  if (strcmp(g_buf, "HTTP/1.1 200 OK") != 0) {
    plcDebug("_postJson: HTTP Status");
    client.stop();
    return Error_connect;
  }

  // Skip HTTP headers
  char endOfHeaders[] = "\r\n\r\n";
  if (!client.find(endOfHeaders)) {
    plcDebug("_postJson: endOfHeaders");
    client.stop();
    return Error_connect;
  }

  // Get message
  memset(g_buf, 0, sizeof(g_buf));
  g_buf[0] = '\0';
  g_readTimestamp = millis();
  while (client.connected())
  {
    while (client.available())
    {
      if ((millis() - g_readTimestamp) > PLC_TIMEOUT_MS)
      {
        plcDebug("_postJson: Read timeout");
        return Error_timeout;
      }
      else if (strlen(g_buf) >= (sizeof(g_buf) - 1))
      {
        plcDebug("_postJson: Overflow error");
        return Error_overflow;
      }
      else        
        strcat_c(g_buf, client.read());      
    }
  }
  
  #ifdef DEBUG_REQUEST
  Serial.println(g_buf);
  #endif
  
  // Wait for server to terminate
  r = _waitClientDisconnect();

  // Disconnect
  client.stop();
  delay(PLC_POST_DELAY);
  return r;
}

/* Send a POST request to the server
 *
 * @param url php file address from /plcmonitor/
 * @param params POST arguments
 * @return error code
*/
uint8_t _post(const char * url, const char * params)
{
  uint8_t r;
  
  r = ethernetMaintain(); 
  if (r != Ok)
    return r;   
    
  _internalUpdate();
  // Connect to server
  #ifdef PLC_ETHERNET_VERSION_2
  client.setConnectionTimeout(PLC_TIMEOUT_MS);
  #endif
  r = client.connect(SERVER, PORT);
  if (r != 1)
  {
    plcDebug("Error de conexion");
    client.stop();
    return Error_connect;
  }

  // Send request
  client.print(F("POST "));
  client.print(F(PLC_WEBSITE_DIRECTORY));
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
      if(strlen(char_buf) >= sizeof(char_buf)-1)
        return Error_overflow;
      char c = client.read();
      strcat_c(char_buf,c);
      #ifdef DEBUG_REQUEST
      Serial.print(c);
      #endif
    }
  }
  
  char b_[REPLY_BUFFER_SIZE] = "";  
  char * c_;

  char *ending; // End of char_buf
  ending = char_buf + strlen(char_buf);

  if(strstr(char_buf,"Transfer-Encoding: chunked"))
  {
    char n_[4] = "";
    int16_t nb;
    int16_t i = 0;

    char *a_;
    
    a_ = strstr(char_buf, header_end) + strlen(header_end);    
    if(!a_)
      return Error;

    while(!isHex(*a_) && a_ <= ending)
      a_++;
    
    while(true)
    {
      // Initialize n_
      memset(n_,0,4);
        
      // Number of following bytes
      while(*a_ != '\r')
      {
        if (a_ > ending)
        {
          client.stop();
          return Error_overflow;
        }
        strcat_c(n_,*a_);
        a_++;
      }
      
      nb = strtol(n_,0,16);
      if (nb == 0) break;
      
      // Extract line of text    
      a_+=2;
      while(*a_ != '\r')
      {
        if (a_ > ending)
        {
          client.stop();
          return Error_overflow;
        }
        strcat_c(b_,*a_);             
        a_++;
      }
      a_+=2;    
    }
    // Remove opening and closing braces
    c_ = b_;  
    c_++;
    if(strlen(c_) < 1)
    {
      client.stop();
      return Error_overflow;
    }
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
    if (p > ending)
    {
      client.stop();
      return Error_overflow;
    }
    
    p = strtok(p,"}");
    
    if (!p)
    {
      client.stop();
      return Error_chunked;
    }
    
    if (strlen(p) > sizeof(b_) - strlen(b_) - 1)
    {
      client.stop();
      return Error_overflow;
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
    char es[10];
    errorString(r,es);
    char lcd_buf[PLC_LCD_BUFFER_SIZE] = "";
    strcat(lcd_buf, msg);
    strcat(lcd_buf, es);
    lcdText(lcd_buf);
    ethernetWatchdog(r != Ok); // Veces totales que puede fallar
  }
  return r;
}

/* Retry json post request until valid */
res_t _retryPostJson(const char * url, const char * params, const char * msg)
{
  res_t r = Error;
  while (r != Ok)
  {
    r = _postJson(url,params);
    char es[10];
    errorString(r,es);
    char lcd_buf[PLC_LCD_BUFFER_SIZE] = "";
    strcat(lcd_buf, msg);
    strcat(lcd_buf, es);
    lcdText(lcd_buf);
    ethernetWatchdog(r != Ok); // Veces totales que puede fallar
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
uint8_t getResets(int32_t * rr)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=reset_counter&plc_number=%d&operation=get&arduino=true", PLC_ID);
  res_t r = _retryPostJson("fase2/modules/post.php", q, "cnt_res: ");
  if (r != Ok)
    return r;
  
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);
  if (!root.success()) {
    plcDebug("Parsing failed!");
    return Error_chunked;
  }
  
  if (strcmp(root["error"].as<char*>(), "OK") != 0) {
    plcDebug("_getResets: Error");
    client.stop();
    return Error_connect;
  }

  for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    rr[i] = (int32_t)strtol(root["resets"][i].as<char*>(),0,10);
  }

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
res_t getDigitalInputs(uint32_t * di)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=control_inputs&plc_number=%d&operation=get&arduino=true", PLC_ID);
  _retryPostJson("fase2/modules/post.php", q, "get_in: ");
  
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);
  if (!root.success()) {
    plcDebug("_getDigitalInputs: Parsing failed!");
    return Error_chunked;
  }
  
  if (strcmp(root["error"].as<char*>(), "OK") != 0) {
    plcDebug("_getDigitalInputs: Error");
    client.stop();
    return Error_connect;
  }

  for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    di[i] = (int32_t)strtol(root["di"][i].as<char*>(),0,10);
  }
  
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
  sprintf(q, "module=control_outputs&plc_number=%d&operation=get&arduino=true", PLC_ID);
  _retryPostJson("fase2/modules/post.php", q, "get_out: ");
  
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);
  if (!root.success()) {
    plcDebug("_getOutputs: Parsing failed!");
    return Error_chunked;
  }
  
  if (strcmp(root["error"].as<char*>(), "OK") != 0) {
    plcDebug("_getOutputs: Error");
    client.stop();
    return Error_connect;
  }

  for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    o[i] = (bool)strtol(root["do"][i].as<char*>(),0,10);
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
  
  for(uint8_t i = 0; i < OUTPUT_COUNT; i ++)
  {
    sprintf(p+strlen(p),"do%d=%d",i+1,dout[i] ? 1 : 0);
    if(i != (OUTPUT_COUNT - 1)) strcat(p,"&");
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
uint8_t setInputs(uint32_t * di, uint32_t * ai)
{
  char p[QUERY_BUFFER_SIZE];
  sprintf(p,"plc_number=%d&operation=set&",PLC_ID);
  for(uint8_t i = 0; i < DIGITAL_INPUT_COUNT; i ++)
  {
    sprintf(p+strlen(p),"di%d=%d&ai%d=%d",i+1,di[i],i+1,ai[i]);
    if(i != (DIGITAL_INPUT_COUNT - 1)) strcat(p,"&");
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
res_t getConfig(uint32_t * dif, uint8_t * dic, uint32_t * aif, float * aig, float * aio)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=config_program&plc_number=%d&operation=get&arduino=true&poweron=%d", PLC_ID, g_power_on);
  res_t r = _retryPostJson("fase2/modules/post.php", q, "get_cfg: ");
  if (r != Ok)
    return r;
  
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);
  if (!root.success()) {
    plcDebug("Parsing failed!");
    return Error_chunked;
  }

  if (strcmp(root["error"].as<char*>(), "OK") != 0) {
    plcDebug("_getConfig: Error");
    client.stop();
    return Error_connect;
  }

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    dif[i] = (uint32_t)strtol(root["di"][i]["f"].as<char*>(),0,10);
    dic[i] = (uint8_t)strtol(root["di"][i]["c"].as<char*>(),0,10);

    aif[i] = (uint32_t)strtol(root["ai"][i]["f"].as<char*>(),0,10);
    aig[i] = (float)atof(root["ai"][i]["g"].as<char*>());
    aio[i] = (float)atof(root["ai"][i]["o"].as<char*>());
  }
  
  g_power_on = 0;
  
  return Ok;
}

/* Initialization.
 * Initialize ethernet module
 *
 * @return error code
*/
res_t initEthernet()
{
  // Disable SD
  pinMode(4,OUTPUT);
  digitalWrite(4,HIGH);
  plcDebug("Connecting to ethernet shield");
  #ifdef PLC_DYNAMIC_IP
  int8_t res = Ethernet.begin(mac);
  if (res != 1)
  {
    plcDebug("DHCP Error", res);
    lcdText("DHCP Error");
    delay(1000);
    softReset();
  }
  #else
  Ethernet.begin(mac , ip, plc_dns, gateway, subnet); // Without IP, about 20 seconds. With IP, about 1 second.
  #endif
  plcDebug("Connected to ethernet shield.");
  return Ok;
}

#endif // PLC_ETHERNET_H
