# ahyes

## Description

---------- DESCRIPTION -----------
Ah Yes, 100 points
(PWN: vos, CRYPTO: hellman)
 
We trapped our flag in military-grade secure storage!
Thing to look at: http://big.vos.uz/ahyes_bbd593905b57fa1fa72e521955f031a8
Place to rescue from: 11.22.33.44:10120
----------------------------------
 
Unlike our challenges for first Belluminar, this one is single-stage :( it contains only one flag for full 100 points.
 
It features a simple AES encryption/decryption service where client can choose encryption strength.
Clients switch between standard AES variants (128 bits key + 10 rounds, 192 bits + 12 rnd, 256 bits + 14 rnd).
Once mode is selected, client can get encrypted flag, encrypt arbitrary data, but not decrypt.
 
The PWN part is a buffer underflow which sets cipher engine mode to use only 128 bits of the key, but keeps number of rounds intact.
This bug can be used to obtain two ciphertexts: encrypted with 10 rounds of 128 bit AES (standard), and encrypted with 12 rounds of 128 bit AES (using the bug).
 
CRYPTO part: once you have same text encrypted with 10 rounds and 12 rounds, you can treat it as known-plaintext situation for 2-round AES which is obviously weak.
Using impossible differential cryptanalysis, teams recover the AES key, and decrypt the flag.
 
 
Vulnbox with flag: http://big.vos.uz/ahyes_ubuntu.ova
(dhcp, root password is 'toor', service on port 10120)


## Solution

About masking: the S-box was xored with 0xca, but the key schedule was modified to cancel it. That is, the cipher used is the original AES.

/////////////////////////////////
1. Find a way to obtain encryption of 10-20 texts with 10 rounds of AES and with 12 rounds of AES but with the same key schedule as in 10 rounds (use underflow bug);
/////////////////////////////////

Now we have encryptions with 10 and 12 rounds of AES. Note that the last round of AES does not have MixColumns phase. So the first 9 rounds are identical, and then we have two different paths for 10 and 12 round encryptions:

SB - SubBytes - apply the S-Box to each byte
SR - ShiftRows - row with index y is shifted y positions left
MC - MixColumns - each columns is mixed by applying a linear matrix
AK(r) - AddKey - add subkey #r to state

10 round:
[9 rounds] | SB | SR | AK(10) -> C10
12 round:
[9 rounds] | SB | SR | MC | AK(10) | SB | SR | MC | AK(11) | SB | SR | AK(12) -> C12

Let's call a 10 round encryption C10 and a 12 round encryption C12. Then we can apply AK(10) to step back in 10 round ciphertext, so that it becomes a "prefix" in computation of 12 rounds. Thus we obtain a 2-round AES known plaintext pair:

C10 -> AK(10) | MC | AK(10) | SB | SR | MC | AK(11) | SB | SR | AK(12) -> C12

Note that MC is linear so [AK(10) | MC | AK(10)] is equivalent to adding key [K10 xor MixColumn(K10)] to state:

C10 -> AK(?) | SB | SR | MC | AK(11) | SB | SR | AK(12) -> C12

Now we will use impossible differentials to recover the key.

Let's take two texts C10i and C10j (and respectively ciphertexts C12i and C12j).

First, we precompute all possible byte differences before the last SB layer by guessing each key byte of K12 and decrypting C12i and C12j. It is fast, because we bruteforce each key separately. Each byte will have less than 128 possible differences.

Now we concentrate on the point after the MC step. We can bruteforce 4 key bytes from AK(?) and partially encrypt C10i and C10j up to after the MC step, so that at that point we know values in a singl column (we bruteforce a diagonal in the key because of the ShiftRows). Having the values, we can compute the difference and check if it is present in the table.

Now we have a probability around 2^(-4) to discard that initial 4 key byte guess. If we check more than 8 such pairs, we will discard all bad keys. That is, we recover some 4 bytes of that AK(?).

Then we repeat the procedure for all 4 columns and recover the full subkey.

Since it is not an exact subkey, we now partially encrypt all C10 we have up to AK(11). Now each byte is independent, so for each position we have many pairs (sin, sout) such that  sout = sbox[sin ^ K11(x,y)] ^ K12(x,y). For each position we bruteforce all bytes K11, K12 such that all sin, sout pairs match.

Thus we obtain the exact subkey from 11th round. We know can try to reverse the AES key schedule by hand, or better do it using z3. Finally, we obtain a master key. Now we can use any AES implekentation to decrypt the flag. 
