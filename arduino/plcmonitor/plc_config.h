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
#define PLC_ID 8

/** PLC IP PATTERN, DIFFERENT FROM EACH NETWORK */
#define PLC_IP {192, 168, 0, (50+PLC_ID)}

#endif // PLC_CONFIG_H
