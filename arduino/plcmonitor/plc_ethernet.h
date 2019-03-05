/*
   PLC Server connectivity and communication library

   Do not modify.

   Author: pepemanboy
   Email: pepe_ciro@hotmail.com
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

/* Buffer size */
#define QUERY_BUFFER_SIZE 200
#define REPLY_BUFFER_SIZE 500

/* Global char buffer */
char g_buf[REPLY_BUFFER_SIZE];

/* Global ethernet client */
EthernetClient client;

/* Power on variable */
uint8_t g_power_on = 1;

/* Read timestamp */
unsigned long g_readTimestamp = 0;

/* Global Json buffer */
const size_t capacity = 6 * JSON_OBJECT_SIZE(2) + 7 * JSON_OBJECT_SIZE(3) + 2 * JSON_OBJECT_SIZE(6) + 60; // Use arduinojson.org/assistant to compute the capacity.
DynamicJsonBuffer g_jsonBuffer(capacity);

/* Forward declarations */
res_t initEthernet();

/* Ethernet watchdog for consecutive errors*/
void ethernetWatchdog(bool b)
{
  ethernet_error_count = b ? ethernet_error_count + 1 : 0;
  if (ethernet_error_count > PLC_MAX_ERRORS)
  {
    initEthernet();
    ethernet_error_count = 0;
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
    switch (res)
    {
      case 1: lcdText("Renew fail"); break;
      case 2: lcdText("Renew success"); break;
      case 3: lcdText("Rebind fail"); break;
      case 4: lcdText("Rebind success"); break;
      default: lcdText("Unknown"); break;
    }
    delay(500);
  }
  res_t r = (res & 0x01) ? Error_maintain : Ok;
  return r;
}

/* Wait for client to be available or timeout */
res_t _waitClientAvailable()
{
  unsigned long ts = millis();
  while (!client.available())
  {
    delay(PLC_TIMEOUT_DELAY_MS);
    if ((millis() - ts) >= PLC_TIMEOUT_MS)
      return Error_available;
  }
  return Ok;
}

/* Wait for client to disconnect or timeout */
res_t _waitClientDisconnect()
{
  unsigned long ts = millis();
  while (client.connected())
  {
    delay(PLC_TIMEOUT_DELAY_MS);
    if ((millis() - ts) >= PLC_TIMEOUT_MS)
      return Error_disconnect;
  }
  return Ok;
}

/* Send a POST request to the server, receive JSON.

   @param url php file address from PLC_WEBSITE_DIRECTORY
   @param params POST arguments
   @return error code
*/
res_t _postJson(const char * url, const char * params)
{
  res_t r;

  r = ethernetMaintain();
  if (r != Ok)
    return r;

  _internalUpdate();

  // Connect to server
  client.setConnectionTimeout(PLC_TIMEOUT_MS);
  int8_t res = client.connect(SERVER, PORT);
  if (res != 1)
  {
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
  if (r != Ok)
  {
    client.stop();
    return r;
  }

  // Check HTTP status
  memset(g_buf, 0, sizeof(g_buf));
  client.readBytesUntil('\r', g_buf, sizeof(g_buf));
  if (strcmp(g_buf, "HTTP/1.1 200 OK") != 0)
  {
    client.stop();
    return Error_httpstatus;
  }

  // Skip HTTP headers
  char endOfHeaders[] = "\r\n\r\n";
  if (!client.find(endOfHeaders))
  {
    client.stop();
    return Error_httpheaders;
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
        return Error_timeout;
      else if (strlen(g_buf) >= (sizeof(g_buf) - 1))
        return Error_overflow;
      else
        strcat_c(g_buf, client.read());
    }
  }

#ifdef DEBUG_REQUEST
  Serial.println("------------START POSTJSON-----------");
  Serial.println("REQUEST:");
  Serial.println(params);
  Serial.println("REPLY:");
  Serial.println(g_buf);
  Serial.println("------------ENDS POSTJSON-----------");
#endif

  // Wait for server to terminate
  r = _waitClientDisconnect();

  // Disconnect
  client.stop();
  return r;
}

/* Retry json post request until valid */
res_t _retryPostJson(const char * url, const char * params, const char * msg)
{
  res_t r = Error;
  while (r != Ok)
  {
    r = _postJson(url, params);

#ifdef DEBUG
    char d[30] = "";
    strcat(d, "_postJson ");
    strcat(d, msg);
    PLC_DEBUG(d, r);
#endif

    lcdError(r, msg);
    ethernetWatchdog(r != Ok); // Veces totales que puede fallar
  }
  return r;
}

/* Validate jsonObject reply */
res_t jsonReplyValidate(JsonObject & root)
{
  res_t r = Ok;

  if (!root.success())
  {
    r = Error_chunked;
    return r;
  }

  if (strcmp(root["error"].as<char*>(), "OK") != 0)
  {
    r = Error_jsonerror;
    return r;
  }

  return r;
}

/* Get resets
   Module: reset_counter
   Args: plc_number = ID, operation = "get", arduino = true
   Returns: resets[-1,-1,-1,-1,-1,-1]

   @param rr placeholder for outputs array
   @return error code
*/
res_t getResets(int32_t * rr)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=reset_counter&plc_number=%d&operation=get&arduino=true", PLC_ID);
  _retryPostJson("fase2/modules/post.php", q, "r_cnt_res: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    const char * v = root["resets"][i].as<char*>();
    if (!v)
      return Error_jsonvar;
    rr[i] = (int32_t)strtol(v, 0, 10);
  }

  return Ok;
}

/* Get digital inputs
   Module: control_inputs
   Args: plc_number = ID, operation = "get", arduino = true
   Returns: di[0,0,0,0,0,0]

   @param di placeholder for inputs array
   @return error code
*/
res_t getDigitalInputs(uint32_t * di)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=control_inputs&plc_number=%d&operation=get&arduino=true", PLC_ID);
  _retryPostJson("fase2/modules/post.php", q, "r_get_in: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    const char * v = root["di"][i].as<char*>();
    if (!v)
      return Error_jsonvar;

    di[i] = (uint32_t)strtol(v, 0, 10);
  }

  return Ok;
}

/* Get outputs
   Module: control_outputs
   Args: plc_number = ID, operation = "get", arduino = "true"
   Returns: do[0,0,0,0,0,0,0]

   @param o placeholder for outputs array
   @return error code
*/
res_t getOutputs(bool * o)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=control_outputs&plc_number=%d&operation=get&arduino=true", PLC_ID);
  _retryPostJson("fase2/modules/post.php", q, "r_get_out: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    const char * v = root["do"][i].as<char*>();
    if (!v)
      return Error_jsonvar;

    o[i] = (bool)strtol(v, 0, 10);

  }

  return Ok;
}

/* Set inputs
   Module: control_inputs
   Args: plc_number = ID, operation = "set", di1 = 0, di2 = 1, ... , ai1 = 0, ai2 = 1024, ...
   Returns: error(OK)

   @param di digital input array
   @param analog input array
*/
res_t setInputs(uint32_t * di, uint32_t * ai)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=control_inputs&plc_number=%d&operation=set&arduino=true&", PLC_ID);
  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    sprintf(q + strlen(q), "di%d=%lu&ai%d=%lu", i + 1, di[i], i + 1, ai[i]);
    if (i != (DIGITAL_INPUT_COUNT - 1)) strcat(q, "&");
  }
  _retryPostJson("fase2/modules/post.php", q, "r_set_in: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  return Ok;
}

/* Log input
   Module: viz_graph
   Args: plc_number = ID, signal_number = 1-6, signal_type = di/ai, operation = set, value = val

   @param n input number
   @param type input type
   @param val value
   @return error code
*/
res_t logInput(uint8_t n, uint8_t type, float val)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=viz_graph&plc_number=%d&operation=set&signal_number=%d&signal_type=", PLC_ID, n);
  strcat(q, type == input_Analog ? "ai" : "di");
  strcat(q, "&value=");
  dtostrf(val, 3, 2, q + strlen(q));
  _retryPostJson("fase2/modules/post.php", q, "r_log_in: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  return Ok;
}

/* Get config
   Module: config_program
   Args: plc_number = ID, operation = "get", arduino = "true"
   Returns:di1(freq,count)ai1(freq,gain,ofs)...

   @param dif digital input frequencies
   @param dic digital input counts
   @param aif analog input frequencies
   @param aig analog input gains
   @param aio analog input offsets
   @return error code
*/
res_t getConfig(uint32_t * dif, uint8_t * dic, uint32_t * aif, float * aig, float * aio)
{
  char q [QUERY_BUFFER_SIZE] = "";
  sprintf(q, "module=config_program&plc_number=%d&operation=get&arduino=true&poweron=%d", PLC_ID, g_power_on);
  _retryPostJson("fase2/modules/post.php", q, "r_get_cfg: ");

  g_jsonBuffer.clear();
  JsonObject& root = g_jsonBuffer.parseObject(g_buf);

  res_t r = jsonReplyValidate(root);
  if (r != Ok)
    return r;

  for (uint8_t i = 0; i < DIGITAL_INPUT_COUNT; ++i)
  {
    const char * v_dif = root["di"][i]["f"].as<char*>();
    const char * v_dic = root["di"][i]["c"].as<char*>();
    const char * v_aif = root["ai"][i]["f"].as<char*>();
    const char * v_aig = root["ai"][i]["g"].as<char*>();
    const char * v_aio = root["ai"][i]["o"].as<char*>();

    if (!v_dif || !v_dic || !v_aif || !v_aig || !v_aio)
      return Error_jsonvar;

    dif[i] = (uint32_t)strtol(v_dif, 0, 10);
    dic[i] = (uint8_t)strtol(v_dic, 0, 10);

    aif[i] = (uint32_t)strtol(v_aif, 0, 10);
    aig[i] = (float)atof(v_aig);
    aio[i] = (float)atof(v_aio);
  }

  g_power_on = 0;

  return Ok;
}

/* Initialization.
   Initialize ethernet module

   @return error code
*/
res_t initEthernet()
{
  // Disable SD
  pinMode(4, OUTPUT);
  digitalWrite(4, HIGH);

  PLC_DEBUG("Connecting to ethernet shield", 0);

#ifdef PLC_DYNAMIC_IP
  int8_t res = Ethernet.begin(mac);
  if (res != 1)
  {
    PLC_DEBUG("DHCP Error", res);
    lcdText("DHCP Error");
    delay(1000);
    return Error_shield;
  }
#else // !PLC_DYNAMIC_IP
  Ethernet.begin(mac , ip, plc_dns, gateway, subnet); // Without IP, about 20 seconds. With IP, about 1 second.
#endif // PLC_DYNAMIC_IP

  if (Ethernet.hardwareStatus() == EthernetNoHardware)
  {
    lcdText("Shield not found");
    PLC_DEBUG("Shield not found", 0);
    delay(1000);
    return Error_shield;
  }
  
  Ethernet.setRetransmissionCount(2);
  Ethernet.setRetransmissionTimeout(200);

  client.setTimeout(PLC_TIMEOUT_MS);

  PLC_DEBUG("Connected to ethernet shield.", 0);

  return Ok;
}

#endif // PLC_ETHERNET_H
