# ton-address-utils
Scripts for working with TON (The Open Network) addresses.

## Address Formats

There are currently two main formats used:

1. **Raw Format**:
   The raw format is a simple string representation of a TON address in the form `workchain:hex`.
   - `workchain`: The workchain ID (e.g., `-1` for the masterchain, `0` for the basechain).
   - `hex`: A 64-character hexadecimal string representing the address.

   Example:
   `-1:3333333333333333333333333333333333333333333333333333333333333333`

2. **Friendly Format**:
   The friendly format is a user-friendly, base64-encoded representation of the address. It includes additional metadata such as bounceability and network type (mainnet or testnet).
   - Bounceable addresses are intended for smart contracts that can handle bounced messages.
   - Non-bounceable addresses are for wallets or accounts that cannot handle bounced messages.
   - The format also indicates whether the address is for the mainnet or testnet.

   Example:
   `Ef8zMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzMzM0vF`

