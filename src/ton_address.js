const ton_address = {};

/**
 * Calculate CRC16-CCITT checksum.
 * @param {Uint8Array} data - Input data.
 * @returns {Uint8Array} - Checksum as a 2-byte array.
 */
ton_address.crc16 = (data) => {
  let reg = 0;
  const message = new Uint8Array(data.length+2);
  message.set(data);
  for (let byte of message) {
    let mask = 0x80;
    while (mask>0) {
      reg <<= 1;
      if (byte&mask) {
        reg += 1;
      }
      mask >>= 1
      if (reg>0xffff) {
        reg &= 0xffff;
        reg ^= 0x1021;
      }
    }
  }
  return new Uint8Array([Math.floor(reg/256),reg%256]);
}

/**
 * Convert raw address to friendly format.
 * @param {string} raw - Raw address in "workchain:hex" format.
 * @param {boolean} [bounceable=true] - Whether to return bounceable or not.
 * @param {boolean} [testnet=false] - Whether for testnet or not.
 * @returns {string} - Friendly address in base64 format.
 */
ton_address.raw2friendly = (raw,bounceable = true,testnet = false) => {
  try {
    raw = raw.split(":");
    const workchain = raw[0];
    raw = raw[1];
    let bytes = [(bounceable?0x11:0x51)|(testnet?0x80:0),workchain=="-1"?0xFF:parseInt(workchain)];
    for (let i=0;i<32;i++) bytes.push(+("0x"+raw[i*2]+raw[i*2+1]));
    const crc = ton_address.crc16(bytes.slice(0,34));
    bytes.push(crc[0],crc[1]);
    return btoa(String.fromCodePoint(...bytes)).replace(/\+/g,"-").replace(/\//g,"_");
  } catch (error) {
    throw new Error("Failed to parse address :(");
  }
}

/**
 * Parse a string into TON address.
 * @param {string} input - String to parse.
 * @param {number} [testnet_flag=0] - Flag to indicate if the address is for the testnet.
 * @returns {Object|string} - Parsed address or an error message.
 */
ton_address.parse = (input,testnet_flag=0) => {
  try {
    let raw_format = false;
    if (input.length>64) {
      input = ton_address.raw2friendly(input,true,testnet_flag);
      raw_format = true;
    }
    const bytes = new Uint8Array([...atob(input.replace(/-/g,"+").replace(/_/g,"/"))].map(c=>c.charCodeAt(0)));
    const workchain = bytes[1]==0xff?-1:bytes[1];
    const testnet = (bytes[0]&0x80)!=0;
    const bounceable = (bytes[0]&0x40)==0;
    const hex = bytes.slice(2,34).reduce((acc,val)=>acc+val.toString(16).padStart(2,"0"),"");
    const crc = ton_address.crc16(bytes.slice(0,34));
    if (crc[0]==bytes[34]&&crc[1]==bytes[35]) {
      const raw = `${workchain}:${hex}`;
      return {
        input_type: `${workchain==-1?"Masterchain":"Basechain "+workchain} ${raw_format?"Raw":(bounceable?"Bounceable":"Unbounceable")} TON Address`,
        workchain,
        testnet,
        raw,
        bounceable: ton_address.raw2friendly(raw,true,testnet),
        unbounceable: ton_address.raw2friendly(raw,false,testnet)
      };
    } else {
      return "Failed to parse address :(";
    }
  } catch (error) {
    return "Failed to parse address :(";
  }
}
