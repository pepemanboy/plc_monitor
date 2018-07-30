/*
 * PLC Configuration.
 * 
 * Modify this file accordingly.
 *
 * Author: pepemanboy
 * Email: pepe_ciro@hotmail.com
*/

#ifndef PLC_CONFIG_H
#define PLC_CONFIG_H

/** PLC ID PREVIOUSLY DEFINED ON THE WEBSITE */
#define PLC_ID 12

/** PLC IP */
#define PLC_IP {192, 168, 1, 42}

/* PLC MAC ADDRESS */
#define PLC_MAC { 0x90, 0xA2, 0xDA, 0x11, 0x10, PLC_ID }

/** MODEM DNS */
#define PLC_DNS { 8, 8, 8, 8}

/** MODEM GATEWAY */
#define PLC_GATEWAY { 192, 168, 1, 254 }

/** MODEM SUBNET MASK */
#define PLC_SUBNET { 255, 255, 255, 0 }

/** SERVER IP */
#define PLC_SERVER {162, 241, 2, 107} /* Es la IP del servidor donde esta la pagina web*/

/** SERVER HTTP PORT */
#define PLC_PORT 80 

/** WEBSITE */
#define PLC_WEBSITE "www.dplastico-scada.com"

/** BASE 64 ENCODED USER:PASS FROM WEBSITE */
#define PLC_WEBSITE_USERPASS "aXZhbnJ2OlRla2xhZG8lMjc1MzA4"

/** HTTP REQUEST HEADER END FROM SERVER */
#define PLC_SERVER_HEADER_END "Connection: close"

#endif // PLC_CONFIG_H
