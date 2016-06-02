=== PWN part ===

1. Use underflow.php to trigger underflow and dump 10-round/12-round pair.
It takes ip/port hardcoded into the source.
It generates those files:
- plaintext.txt is some random text it encrypted
- ciph10.txt and ciph12.txt are 10/12 pair
- flag.txt is encrypted flag

=== CRYPTO part ===

2. Use ./solve to bruteforce possible AES keys using impossible differentials. (source code is aesc.c)
It takes ciph10.txt and ciph12.txt
Works for ~ 5 minutes in average, generates full round key for 11th round (recovered k11). It's 32 hexes.

3. Use ./recover.py to revert k11 into AES key and decrypt the flag.
It depends on z3py and aes.py; takes flag.txt and k11 from ./solve (hardcoded into source).
Outputs the decrypted flag.
